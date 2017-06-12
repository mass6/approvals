<?php

namespace App;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Mass6\LaravelStateWorkflows\StateAuditingTrait;
use Mass6\LaravelStateWorkflows\StateMachineTrait;
use Venturecraft\Revisionable\RevisionableTrait;

class Order extends WorkflowModel
{
    /**
     * @var array
     */
    protected $guarded = [];

    /**
     *
     */
    public function createOrderWorkflow()
    {
        // TODO:: replace with strategy for finding appropriate workflow definition
        /** @var WorkflowDefinition $workflowDefinition */
        $workflowDefinition = WorkflowDefinition::latest('id')->first();
        $this->workflowDefinitions()->attach($workflowDefinition, ['definition' => $workflowDefinition->getDefinition()]);
        event(new OrderCreated($this));

    }

    /**
     * @return bool
     */
    public function afterSubmit()
    {
        $this->createOrderWorkflow();
        $this->restoreStateMachine();
    }

    /**
     * @param $transitionEvent
     *
     * @return bool
     */
    public function afterApprove($model, \Finite\Event\TransitionEvent  $transitionEvent)
    {
        $this->getWorkflow()->saveApproval($transitionEvent, Auth::user());
    }

    /**
     * @param $transitionEvent
     *
     * @return bool
     */
    public function afterFinalApproval(\Finite\Event\TransitionEvent  $transitionEvent)
    {
        event('OrderWasFinalApproved');
        $this->restoreStateMachine();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
