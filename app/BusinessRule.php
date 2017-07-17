<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class BusinessRule extends Model
{
    protected $guarded = [];

    public function rules()
    {
        return $this->{$this->type.'Rules'}();
    }

    public function valueRules()
    {
        return $this->hasMany(ValueRule::class);
    }

    public function workflows()
    {
        return $this->hasMany(Workflow::class);
    }

    public function orders()
    {
        return $this->belongsToMany(Order::class, 'workflows')->withTimestamps();
    }
}