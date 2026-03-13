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
        //ID
        if(is_numeric($input)){
            return $query->where('id', $input);
        }

        //* I WOULD ALSO LOWERCASE AND REMOVE ACCENTS, BUT MYSQL IS CASE INSENSITE, SO...
        //CLEAR UNWANTED CHARACTERS
        $cleanInput = preg_replace('/[^\p{L}\p{N}\s]/u', '', $input);
        return $query->where('name', 'LIKE', '%' . trim($cleanInput) . '%');
    }
}
