<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|Product scopeFindByIdOrName(string $identifier)
 */
class Product extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'amount'];

    public function transactions()
    {
        return $this->belongsToMany(Transaction::class, 'transaction_products')
                    ->withPivot('quantity');
    }

    //SEARCH PRODUCT BY ID OR NAME
    public function scopeFindByIdOrName($query, $input)
    {
        //ID
        if(is_numeric($input)){
            return $query->where('id', $input);
        }

        //REMOVE EVERYTHING BUT BLANK SPACES, NUMBERS AND LETTERS FROM INPUT -> NORMALIZE TO LOWERCASE AND REMOVE ACCENTS
        $cleanInput = preg_replace('/[^a-zA-Z0-9\s]/u', '', $input);
        $normalized = strtolower(trim(Str::ascii($cleanInput)));

        //PRODUCT NAME
        return $query->whereRaw('LOWER(name) LIKE ?', ["%{$normalized}%"]);
    }
}
