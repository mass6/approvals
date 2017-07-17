<?php

namespace App;

use Finite\StateMachine\StateMachine;

/**
 * Class WorkflowFactory
 * @package App
 */
class WorkflowFactory
{

    /**
     * @var
     */
    public $model;

    /**
     * @var StateMachine
     */
    public $sm;

    /** @var  Workflow */
    public $workflow;

    /**
     * StateMachineConfigFactory constructor.
     * @param $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * @param StateMachine $sm
     * @return StateMachine
     */
    public function initializeWorkflow(StateMachine $sm)
    {
        $factory = new LevelBasedWorkflowParser();

        return $factory->initializeWorkflow($sm, $this->model);


        if ($this->model->getState() === 'PND') {
            $nextState = 'PND';
            $workflowDefinition = $this->model->getWorkflow()->getDefinition();
            $levels = collect($workflowDefinition['levels'])->keyBy('name')->map(function($l) {
                return collect(range(1, $l['signatories']))->map(function($s,$i) use ($l) {
                    return $l['level'] . '.' . ($i + 1);
                });
            })->flatten();

            $approvals = $this->model->getWorkflow()->approvals;
            if (! $approvals->count()) {
                // set to first approval rule
                $next = $levels->first();
            } else {
                // set to next approval rule
                $lastApproval = str_replace('approve_', '', $approvals->max('rule'));
                $next = $levels->first(function($l) use ($lastApproval) {
                    return $l > $lastApproval;
                });
            }
            $transition = 'approve_' . $next;

            if ($next === $levels->max()) {
                $nextState = 'APR';
            }


            $sm->addTransition($transition, 'PND', $nextState);
            $sm->getDispatcher()->addListener('finite.post_transition.' . $transition, function(\Finite\Event\TransitionEvent $e) {
                $this->afterApprove($e);
            });
        }

        return $sm;
    }

    /**
     *
     */
    protected function getApprovalLevels()
    {
        $workflowDefinition = $this->model->getWorkflow()->getDefinition();

        return collect($workflowDefinition['levels']);
    }

    /**
     * @param \Finite\Event\TransitionEvent $event
     */
    protected function afterApprove(\Finite\Event\TransitionEvent $event)
    {
        $this->model->afterApprove($event);
    }

    /**
     *
     */
    public function getDefinition()
    {
        $workflowDefinition = json_decode($this->model->getWorkflow()->getDefinition(), true);

        return collect($workflowDefinition);
    }
}
