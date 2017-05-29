<?php

namespace app;

use Illuminate\Database\Eloquent\Model;

class TransitionEvent extends Model
{

    /**
     * The attributes that are not mass assignable.
     *
     * @var array
     */
    protected $guarded = [];

    public $statefulModel;

    public $statefulName = 'stateful';

    /**
     * Get all of the owning stateful models.
     */
    public function stateful()
    {
        return $this->morphTo();
    }
}

