<?php

namespace App;

use Illuminate\Support\Facades\Auth;

class Order extends WorkflowModel
{
    /**
     * @var array
     */
    protected $guarded = [];
    protected $stateColumn = 'status';

    /**
     *
     */
    public function createOrderWorkflow()
    {
        // TODO:: replace with strategy for finding appropriate workflow definition
        /** @var WorkflowDefinition $workflowDefinition */
        $workflowDefinition = WorkflowDefinition::latest('id')->first();
        $this->workflowDefinitions()->attach($workflowDefinition, [
            'definition' => $workflowDefinition->getDefinition(),
            'active' => true,
        ]);
        // event(new OrderCreated($this));

    }

    /**
     * @return bool
     */
    public function beforePreSubmit()
    {
        $this->createOrderWorkflow();
        // $this->save();
        $this->restoreStateMachine();
    }

    /**
     * @return bool
     */
    public function afterPreSubmit()
    {
        \Log::info('Applying Submit');
        $this->apply('submit');
        // $this->restoreStateMachine();
    }

    public function afterApprove($model, \Finite\Event\TransitionEvent  $transitionEvent)
    {
        $this->getWorkflow()->saveApproval(
            Auth::user(),
            $transitionEvent->getTransition()->getName(),
            $transitionEvent->get('final-approval', false),
            true,
            $transitionEvent->get('comment', null)
        );
        $this->restoreStateMachine();
    }

    public function afterFinalApproval(\Finite\Event\TransitionEvent  $transitionEvent)
    {
        event('OrderWasFinalApproved');
        $this->restoreStateMachine();
    }

    public function afterReject($model, $event)
    {
        $workflow = $this->getWorkflow();
        $workflow->saveRejection(Auth::user(), $event->get('approval_level'), $event->get('comment'));
        $workflow->deactivate();
        //$this->restoreStateMachine();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
