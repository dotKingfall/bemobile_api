<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

/**
 * @method static \Illuminate\Database\Eloquent\Builder|Product scopeFindByIdOrName(string $identifier)
 */
class Product extends Model
{

    use SoftDeletes;
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
        $trimmedInput = trim($input);

        //ID
        if(is_numeric($trimmedInput)){
            //* I DON'T QUITE LIKE THIS SOLUTION, BUT IT'S A HASSLE TO IMPLEMENT A SEPARATE THING FOR ID SEARCH
            //* BESIDES, SOMETHING IS REALLY WRONG IF THERE ARE ONLY NUMBERS ON A PRODUCT NAME. I'D PROBABLY DO SOMETHING DIFFERENT IN A REAL-WORLD SCENARIO
            return $query->where('id', (int)$trimmedInput);
        }

        //* I WOULD ALSO LOWERCASE AND REMOVE ACCENTS, BUT MYSQL IS CASE INSENSITE, SO...
        //CLEAR UNWANTED CHARACTERS
        $cleanInput = preg_replace('/[^\p{L}\p{N}\s]/u', '', $trimmedInput);
        return $query->where('name', 'LIKE', '%' . $cleanInput . '%');
    }
}
