<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Transaction extends Model
{
    use HasFactory;
    protected $fillable = [
        'client_id',
        'client_email',
        'gateway_id',
        'external_id',
        'status',
        'amount',
        'card_last_numbers',
        'product_id',
        'quantity',
        'idempotency_hash',
    ];

    //THIS IS SO COOL
    protected $hidden = [
        'idempotency_hash',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function products()
    {
        return $this->belongsToMany(Product::class, 'transaction_products')
        ->withPivot('quantity')
        ->withTimestamps();
    }

    public function gateway()
    {
        return $this->belongsTo(Gateway::class);
    }
}
