<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Gateway;
use App\Models\Transaction;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\DB;

class TransactionController extends Controller
{
    public function store(Request $request){

        Log::info('Purchase attempt initiated', [
            'input_identifier' => $request->product_id,
            'customer_email'   => $request->email,
            'quantity'         => $request->quantity ?? 1
        ]);

        $request->validate([
            //FIELDS
            'product_id' => 'required',
            'name' => 'required|string|min:1|max:255',
            'email'      => 'required|email',
            'quantity' => 'nullable|integer|min:1',
        ], [
            //CUSTOM MESSAGES
            'name.required' => 'The customer name cannot be empty.',
            'email.required' => 'The customer email cannot be empty.',
            'email.email' => 'The customer email is not valid.',
        ]);


        //DECIDE IF WE'LL LOOK FOR THE PRODUCT BY ID OR NAME
        Log::info('Searching for product', ['identifier' => $request->product_id]);
        $product = Product::findByIdOrName($request->product_id)->firstOrFail();

        if (!$product) {
            Log::error('Product not found', ['identifier' => $request->product_id]);
            return response()->json([
                'message' => "The product '{$request->product_id}' was not found"
            ], 404);
        }

        //TOTAL AMOUNT CALC
        $quantity = $request->quantity ?? 1;
        $totalAmount = $product->amount * $quantity;

        //CHECK FOR ACTIVE GATEWAYS
        $gateways = Gateway::where('is_active', true)
            ->orderBy('priority', 'asc')
            ->get();

        if ($gateways->isEmpty()) {
            Log::critical('CRITICAL: All gateways are disabled or misconfigured');
            return response()->json(['message' => 'Service temporarily unavailable'], 503);
        }

        //ITERATE THROUGH GATEWAYS
        $selectedGateway = null;
        $externalId = null;
        $ccLastNumbers = substr('5569000000006063', -4); //MOCK CC NUMBER, WOULD BE PART OF THE REQUEST IN REAL LIFE

        foreach($gateways as $gateway){
            try{
                //*IF THIS WAS REAL I WOULD VALIDATE THE CC NUMBER WITH LUHN'S ALGORITHM
                //MOCK PAYLOAD
                //TODO MENTION DTOs IF THIS WAS A REAL SCENARIO
                $payload = [ //TODO MAKE PAYLOAD TO EACH GATEWAY
                    'name'  => $request->name,
                    'email' => $request->email,
                    'cardNumber' => '5569000000006063',
                    'cvv' => '010',
                ];

                $client = Http::timeout(5)->connectTimeout(2);

                if($gateway-> name === config('gateways.gateway_1.name')){
                    Log::info("Attempting payment through Gateway 1");
                    $payload['amount'] = $totalAmount;

                    $response = $client->withToken(
                        config('gateways.gateway_1.token'))
                        ->post(config('gateways.gateway_1.url'), $payload);
                }
                elseif ($gateway->name === config('gateways.gateway_2.name')){
                    Log::info("Attempting payment through Gateway 2");
                    $payload['valor'] = $totalAmount;

                    $response = $client->withHeaders([
                        'Gateway-Auth-Token' => config('gateways.gateway_2.token'),
                        'Gateway-Auth-Secret' => config('gateways.gateway_2.secret')
                    ])->post(config('gateways.gateway_2.url'), $payload);
                }

                //SUCCESSFULLY CONNECTED TO GATEWAY, CHECK RESPONSE
                if ($response && $response->successful()) {
                    $selectedGateway = $gateway;
                    $externalId = $response->json('id');
                    break; 
                }

                //FAIL SAFE :D
                $status = $response ? $response->status() : 'Unknown';
                throw new Exception("[GATEWAY] '{$gateway->name}' failed with status: {$status}");

            } catch (ConnectionException $e){
                Log::warning("TIMEOUT: [GATEWAY] '{$gateway->name}' timed out.");
                continue;
            } catch (Exception $e){
                Log::warning("Gateway Failed", [
                    'gateway' => $gateway->name,
                    'priority' => $gateway->priority,
                    'error' => $e->getMessage()
                ]);
                continue;
            }
        }

        if (!$selectedGateway) {
            Log::emergency('SYSTEM FAILURE: All active gateways failed');
            return response()->json(['message' => 'Payment failed. All gateways are unavailable'], 500);
        }

        //CREATE TRANSACTION RECORD AND LINK TO PRODUCTS
        $transaction = DB::transaction(
            function() use ($product, $quantity, $totalAmount, $selectedGateway, $externalId, $ccLastNumbers, $request) {
                $inner_transaction = Transaction::create([
                    'client_id'         => auth('sanctum')->check() ? auth('sanctum')->id() : null,
                    'client_email'      => $request->email,
                    'gateway_id'        => $selectedGateway->id,
                    'external_id'       => $externalId,
                    'status'            => '03 - completed',
                    'amount'            => $totalAmount,
                    'card_last_numbers' => $ccLastNumbers,
                    'product_id'        => $product->id,
                    'quantity'          => $quantity,
                ]);

                $inner_transaction->products()->attach($product->id, ['quantity' => $quantity]);
                return $inner_transaction;
            }
        );

        Log::info('Purchase completed!', [
            'transaction_id' => $transaction->id,
            'gateway' => $selectedGateway->name,
            'amount' => $totalAmount
        ]);
        return response()->json([
            'message'        => 'Yay, purchase successful!',
            'transaction_id' => $transaction->id,
            'gateway'        => $selectedGateway->name
        ], 201);
    }
}
