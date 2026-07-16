<?php

namespace Yoosuf\Document\Documents\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IdempotencyKey extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $table = 'idempotency_keys';

    protected $guarded = [];
}
