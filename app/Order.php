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
        $orderValue = $this->total;
        // TODO:: replace with strategy for finding appropriate business rule
        $businessRule = BusinessRule::first();
        $defintition = $businessRule->rules->first(function($rule) use ($orderValue) {
            if (isset($rule['max_value'])) {
                return $orderValue >= $rule['min_value'] && $orderValue <= $rule['max_value'];
            }
            return $orderValue >= $rule['min_value'];
        });
        $this->businessRules()->attach($businessRule, [
            'definition' => $defintition->config,
            'config' => '',
            'active' => true,
        ]);
        $this->getWorkflow()->setWorkflowConfig();
        //event(new OrderCreated($this));
    }

    /**
     * @return bool
     */
    public function beforePreSubmit()
    {
        $this->createOrderWorkflow();
        $this->restoreStateMachine();
    }

    /**
     * @return bool
     */
    public function afterPreSubmit()
    {
        $this->apply('submit');
    }

    public function afterApprove($model, \Finite\Event\TransitionEvent  $transitionEvent)
    {
        $this->getWorkflow()->logApproval(
            Auth::user(),
            $transitionEvent->getTransition()->getName(),
            $transitionEvent->get('final-approval', false),
            true,
            $transitionEvent->get('comment', null)
        );
    }

    public function afterFinalApproval($model, \Finite\Event\TransitionEvent  $transitionEvent)
    {
        event('OrderWasFinalApproved');
    }

    public function afterReject($model, $transitionEvent)
    {
        $workflow = $this->getWorkflow();
        $workflow->logRejection(Auth::user(), $transitionEvent->get('comment'));
        $workflow->deactivate();
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
