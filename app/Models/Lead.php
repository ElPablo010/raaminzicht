<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable([
    'type',
    'name',
    'email',
    'phone',
    'subject',
    'appointment_at',
    'message',
    'source_url',
    'attachments',
])]
class Lead extends Model
{
    protected function casts(): array
    {
        return [
            'attachments' => 'array',
            'appointment_at' => 'datetime',
        ];
    }
}
