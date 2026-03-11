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
            'quantity' => 'nullable|integer|min:1',
        ]);

        //REMOVE EVERYTHING BUT BLANK SPACES, NUMBERS AND LETTERS FROM


        //TODO COMPLETE
    }
}
