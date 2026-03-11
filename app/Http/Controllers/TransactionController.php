<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

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

        //REMOVE EVERYTHING BUT BLANK SPACES, NUMBERS AND LETTERS FROM


        //TODO COMPLETE
    }
}
