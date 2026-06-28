<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class School extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'logo',
    ];

    protected $dates = [
        'email_verified_at',
    ];
}
