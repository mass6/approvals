<?php

namespace App;

use Finite\StateMachine\StateMachine;
use Finite\Transition\Transition;
use Illuminate\Support\Collection;
use Symfony\Component\OptionsResolver\OptionsResolver;

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

            $nextApprovalTransition = $this->getNextApprovalTransition($model, $approvalLevels);
            $sm->addTransition($nextApprovalTransition);

            $rejectTransition = $this->getRejectTransition($nextApprovalTransition->getName());
            $sm->addTransition($rejectTransition);

            $sm->getDispatcher()->addListener('finite.post_transition.' . $nextApprovalTransition, function(\Finite\Event\TransitionEvent $e) use ($model) {
                $model->afterApprove($model, $e);
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
        $approvalLevels = collect($workflowConfig)->keyBy('name')->map(function ($level) {
            if ($level['signatories'] >= 1) {
                return collect(range(1, $level['signatories']))->map(function ($s, $i) use ($level) {
                    return $level['level'] . '.' . ($i + 1);
                });
            }
        })->flatten();

        return $approvalLevels;
    }

    protected function getNextApprovalTransition($model, $approvalLevels)
    {
        $nextApprovalLevel = $this->getNextApprovalLevel($model, $approvalLevels);
        $transitionName = 'approve_' . $nextApprovalLevel;
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setDefault('approved', true);
        $optionsResolver->setDefault('comment', null);
        $state = 'PND';

        if ($nextApprovalLevel === $approvalLevels->max()) {
            $optionsResolver->setDefault('final-approval', true);
            $state = 'APR';
        }

        return new Transition($transitionName, 'PND', $state,null,$optionsResolver);
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
     * @param \Finite\Event\TransitionEvent $event
     * @param                               $model
     */
    protected function afterApprove($model, \Finite\Event\TransitionEvent $event)
    {
        $model->afterApprove($model, $event);
    }

    protected function getRejectTransition($approvalLevel)
    {
        $optionsResolver = new OptionsResolver();
        $optionsResolver->setDefault('approval_level', $approvalLevel);
        $optionsResolver->setRequired('comment');

        return new Transition('reject', 'PND', 'DRA', null, $optionsResolver);
    }
}
