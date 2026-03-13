<?php

namespace App\Http\Controllers;

use App\Models\Client;

class ClientController extends Controller
{
    public function index()
    {
        return response()->json(Client::paginate(25));
    }

    public function show(Client $client)
    {
        return response()->json(
            $client->load([
                'transactions.gateway',
                'transactions.products',
            ])
        );
    }
}
