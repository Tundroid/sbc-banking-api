<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BankAccount extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'account_number', 'balance'];

    // A BankAccount belongs to a User
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
