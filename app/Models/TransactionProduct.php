<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class TransactionProduct extends Model
{
    use HasFactory;
    protected $fillable = ['transaction_id', 'product_id', 'quantity'];
}
