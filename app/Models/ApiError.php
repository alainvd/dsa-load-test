<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ApiError extends Model
{
    use HasFactory;

    protected $fillable = [
        'url',
        'method',
        'request_payload',
        'status_code',
        'error_message',
        'response_body',
    ];
}
