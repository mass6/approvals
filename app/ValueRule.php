<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ValueRule extends Model
{
    protected $guarded = [];

    public function businessRule()
    {
        return $this->belongsTo(BusinessRule::class);
    }
}