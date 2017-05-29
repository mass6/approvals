<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Approval
 * @package App
 */
class Approval extends Model
{

    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function orderWorkflow()
    {
        return $this->belongsTo(Workflow::class);
    }
}
