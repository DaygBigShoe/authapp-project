<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client_user_auth extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'client_user_unique_identifier',
        'auth_key',
        'pin'
    ];
}
