<?php

namespace Yoosuf\Document\Documents\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Document extends Model
{
    use HasFactory;

    protected $table = 'documents';

    protected $guarded = [];

    protected $casts = [
        'request_payload_json' => 'array',
    ];
}
