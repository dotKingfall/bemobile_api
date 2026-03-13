<?php

namespace App\Http\Controllers;

use Exception;

use App\Services\PaymentService;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

use App\Models\Product;
use App\Models\Gateway;
use App\Models\Transaction;
use App\Models\Client;

use App\Rules\LuhnRule;

class TransactionController extends Controller
{
    //IMPLEMENT PAYMENT SERVICE THROUGH DEPENDENCY INJECTION
    protected $paymentService;
    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    public function index(){
        $transactions = Transaction::with(['gateway', 'products'])
            ->orderBy('created_at', 'desc')
            ->paginate(25);

        return response()->json($transactions);
    }

    public function show(Transaction $transaction){
        return response()->json($transaction->load(['gateway', 'products']));
    }

    public function store(Request $request){

        //VALIDATION FOR DATA, GATEWAY, PRODUCT AND IDEMPOTENCY CHECK =============================================
        $this->validateAndLogRequest($request);
        $product = $this->findProduct($request);
        if ($product instanceof JsonResponse) return $product;

        $totalAmount = $product->amount * ($request->quantity ?? 1);
        $gateways = Gateway::where('is_active', true)->orderBy('priority', 'asc')->get();

         if ($gateways->isEmpty()) {
            Log::critical('CRITICAL: All gateways are disabled or misconfigured');
            return response()->json(['message' => 'Service temporarily unavailable'], 503);
        }

        $hash = $this->generateIdempotencyHash($request, $totalAmount);
        if ($existing = $this->findExistingTransaction($hash)) return $existing;

        //END VALIDATION FOR DATA, GATEWAY, PRODUCT AND IDEMPOTENCY CHECK =============================================
        
        //LOCK IF THE SAME TRANSACTION HAPPENED IN THE LAST 10 SECONDS
        return Cache::lock($hash, 10)->get(function () use ($hash, $request, $product, $totalAmount, $gateways) {

            $result = $this->paymentService->processPurchase($request, $gateways, $totalAmount);

            if (!$result) {
                return response()->json(['message' => 'Payment failed. All gateways unavailable'], 502);
            }

            $transaction = $this->createTransactionRecord($request, $product, $result, $totalAmount, $hash);

            return response()->json([
                'message'        => 'Yay, purchase successful!',
                'transaction_id' => $transaction->id,
                'gateway'        => $result['gateway']->name
            ], 201);
        }) ?? response()
                ->json(['message' => 'A payment for this order is already being processed. Please wait'], 409);
    }

    public function refund(Transaction $transaction){
        Log::info('Refund attempt initiated', ['transaction_id' => $transaction->id]);

        //*I MEAN, IN REAL LIFE WE'D PROBABLY HAVE A LOOKUP TABLE, SO I'LL JUST LEAVE IT HERE FOR NOW
        $statusList = [
            '01 - pending', '02 - processing', '03 - completed', 
            '04 - failed', '05 - chargeback', '06 - refunded', '07 - partially refunded'
        ];

        //CHECK IF TRANSACTION WAS ALREADY REFUNDED
        if ($transaction->status === $statusList[5]) {
            return response()->json(['message' => 'This transaction has already been refunded.'], 409); //CONFLICT
        }

        //IGNORE ALL TRANSACTIONS THAT WERE NOT COMPLETED
        if ($transaction->status !== $statusList[2]) {
            return response()->json([
                'message' => "Transactions with status '{$transaction->status}' cannot be refunded."
            ], 422);
        }

        $response = $this->paymentService->processRefund($transaction);

        if ($response && $response->successful()) {
            $transaction->update(['status' => $statusList[5]]); //UPDATE TO REFUNDED
            
            Log::info("Transaction {$transaction->id} refunded successfully");
            return response()->json(['message' => 'Refund successful']);
        }

        Log::error("Refund failed for transaction {$transaction->id}");
        return response()->json(['message' => 'Gateway failed refund'], 502);
    }

    private function validateAndLogRequest(Request $request){
        Log::info('Purchase attempt initiated', [
            'input_identifier' => $request->product_id,
            'customer_email'   => $request->email,
            'quantity'         => $request->quantity ?? 1,
            'card_last_numbers' => substr($request->cardNumber, -4)
        ]);

        $request->validate([
            //FIELDS
            'product_id' => 'required',
            'name' => 'required|string|min:1|max:255',
            'email'      => 'required|email',
            'quantity' => 'nullable|integer|min:1',
            'cardNumber' => ['required', 'string', 'size:16', new LuhnRule], 
            'cvv' => 'required|string|between:3,4',
        ], [
            //CUSTOM MESSAGES
            'name.required' => 'The customer name cannot be empty.',
            'email.required' => 'The customer email cannot be empty.',
            'email.email' => 'The customer email is not valid.',
            'cardNumber.size' => 'Please insert a valid card number.',
            'cvv.between' => 'Please insert a valid CVV (3 or 4 digits).',
        ]);
    }

    private function findProduct(Request $request): Product|JsonResponse{
        //DECIDE IF WE'LL LOOK FOR THE PRODUCT BY ID OR NAME
        Log::info('Searching for product', ['identifier' => $request->product_id]);
        $product = Product::findByIdOrName($request->product_id)->first();

        if (!$product) {
            Log::error('Product not found', ['identifier' => $request->product_id]);
            return response()->json([
                'message' => "The product '{$request->product_id}' was not found"
            ], 404);
        }

        return $product;
    }

    private function createTransactionRecord($request, $product, $result, $totalAmount, $hash){
        return DB::transaction(function() use ($request, $product, $result, $totalAmount, $hash){

            //CHECK IF CLIENT EXISTS, OTHERWISE DO NOT ASSIGN CLIENT_ID
            $client = Client::where('email', $request->email)->first();

            $t = Transaction::create([
                'client_id'         => $client?->id,
                'client_email'      => $request->email,
                'gateway_id'        => $result['gateway']->id,
                'external_id'       => $result['external_id'],
                'status'            => '03 - completed',
                'amount'            => $totalAmount,
                'card_last_numbers' => substr($request->cardNumber, -4),
                'product_id'        => $product->id,
                'quantity'          => $request->quantity ?? 1,
                'idempotency_hash'  => $hash,
            ]);

            //STORE TO PIVOT TABBLE || TRANSACTION_PRODUCTS
            $t->products()->attach($product->id, ['quantity' => $request->quantity ?? 1]);
            return $t;
        });
    }

    //========================================================================================
    //HELPER METHODS FOR IDEMPOTENCY AND CACHE LOCK
    //========================================================================================

    private function generateIdempotencyHash($request, $amount){
        return md5($request->email . $request->product_id . $amount . $request->cardNumber);
    }

    private function findExistingTransaction($hash){
        Log::info('Checking for existing transaction with hash', ['hash' => $hash]);

        $found = Transaction::where('idempotency_hash', $hash)->where('created_at', '>=', now()->subMinutes(5))->first();
        return $found 
            ? response()->json(['message' => 'Processing...', 'transaction_id' => $found->id], 409) //PURCHASE ALREADY COMPLETED
            : null;
    }

}
