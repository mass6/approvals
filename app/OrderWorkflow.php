<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class OrderWorkflow
 * @package App
 */
class OrderWorkflow extends Model
{

    /**
     * @var string
     */
    protected $table = 'order_workflows';

    /**
     * @var array
     */
    protected $guarded = [];


    /**
     * @param User $user
     */
    public function setNextApprover(User $user = null)
    {
        if ($user) {
            //$this->nextApprover()->associate($user);
            $this->next_approver = $user->id;
            $this->save();
        }
    }

    public function getNextApprover()
    {
        return $this->nextApprover;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function workflow()
    {
        return $this->belongsTo(Workflow::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function nextApprover()
    {
        return $this->belongsTo(User::class, 'next_approver');
    }
}
