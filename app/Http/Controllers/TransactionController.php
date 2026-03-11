<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Gateway;
use App\Models\Transaction;

class TransactionController extends Controller
{
    public function store(Request $request){
        $request->validate([
            //FIELDS
            'product_id' => 'required|exists:products,id',
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
        $product = Product::findByIdOrName($request->product_id);

        //TODO COMPLETE
    }
}
