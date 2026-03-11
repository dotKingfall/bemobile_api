<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Log;
use Exception;
use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Gateway;
use App\Models\Transaction;

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
        $product = Product::findByIdOrName($request->product_id);

        if (!$product) {
            Log::error('Product not found', ['identifier' => $request->product_id]);
            return response()->json([
                'message' => "The product '{$request->product_id}' was not found."
            ], 404);
        }

        //TODO COMPLETE
    }
}
