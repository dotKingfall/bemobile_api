<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Gateway extends Model
{
    use HasFactory;
    protected $fillable = ['name', 'is_active', 'priority'];

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }
}
