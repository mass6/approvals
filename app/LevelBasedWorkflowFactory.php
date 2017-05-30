<?php

namespace App;

use Finite\StateMachine\StateMachine;
use Illuminate\Support\Collection;

class LevelBasedWorkflowFactory
{

    /**
     * @param StateMachine $sm
     * @param              $model
     * @return StateMachine
     */
    public function initializeWorkflow(StateMachine $sm, $model)
    {
        if ($model->getState() === 'PND') {
            $workflowConfig = $model->getWorkflow()->getConfig();
            $approvalLevels    = $this->getApprovalLevels($workflowConfig);

            $nextApprovalLevel = $this->getNextApprovalLevel($model, $approvalLevels);
            $nextApprovalTransition = 'approve_' . $nextApprovalLevel;
            $stateAfterNextApproval = $this->getNextState($nextApprovalLevel, $approvalLevels);

            $sm->addTransition($nextApprovalTransition, 'PND', $stateAfterNextApproval);
            $sm->getDispatcher()->addListener('finite.post_transition.' . $nextApprovalTransition, function(\Finite\Event\TransitionEvent $e) use ($model) {
                $this->afterApprove($e, $model);
            });
        }

        return $sm;
    }

    /**
     * @param $workflowConfig
     * @return Collection
     */
    protected function getApprovalLevels($workflowConfig): Collection
    {
        $approvalLevels = collect($workflowConfig['levels'])->keyBy('name')->map(function ($l) {
            return collect(range(1, $l['signatories']))->map(function ($s, $i) use ($l) {
                return $l['level'] . '.' . ($i + 1);
            });
        })->flatten();

        return $approvalLevels;
    }

    /**
     * @param \Finite\Event\TransitionEvent $event
     * @param                               $model
     */
    protected function afterApprove(\Finite\Event\TransitionEvent $event, $model)
    {
        $model->afterApprove($event);
    }

    /**
     * @param $nextApprovalLevel
     * @param $approvalLevels
     * @return string
     */
    protected function getNextState($nextApprovalLevel, $approvalLevels): string
    {
        if ($nextApprovalLevel === $approvalLevels->max()) {
            $stateAfterNextApproval = 'APR';
        } else {
            $stateAfterNextApproval = 'PND';
        }

        return $stateAfterNextApproval;
    }

    /**
     * @param $model
     * @param $approvalLevels
     * @return mixed
     */
    protected function getNextApprovalLevel($model, $approvalLevels)
    {
        $pastApprovals = $model->getWorkflow()->approvals;
        if ( ! $pastApprovals->count()) {
            $nextApprovalLevel = $approvalLevels->first();
        } else {
            // set to next approval rule
            $lastApproval      = str_replace('approve_', '', $pastApprovals->max('rule'));
            $nextApprovalLevel = $approvalLevels->first(function ($l) use ($lastApproval) {
                return $l > $lastApproval;
            });
        }

        return $nextApprovalLevel;
    }
}
