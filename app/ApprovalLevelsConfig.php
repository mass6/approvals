<?php

namespace App;

use Illuminate\Support\Collection;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApprovalLevelsConfig
{
    public function generate($model)
    {
        $workflowConfig = $model->getWorkflow()->getConfig();
        $approvalLevels = $this->getApprovalLevels($workflowConfig);

        list($approvalStates, $config) = $this->getApprovalStates($approvalLevels);

        $approvalTransitions = collect([]);
        $approvalLevels->each(function($level) use (&$approvalTransitions) {
            $approvalTransitions->push('approve_' . $level);
        });



        $config['transitions']['cancel'] = ['from' => $approvalStates->toArray(), 'to' => 'cancelled', 'properties' => []];
        $config['transitions']['submit'] = ['from' => ['draft'], 'to' => $approvalStates->first(), 'properties' => []];
        $config['transitions']['reject'] = [
            'from' => $approvalStates->toArray(),
            'to' => 'draft',
            'properties' => [],
            'configure_properties' => function (OptionsResolver $resolver) {
                $resolver->setRequired(['approval_level', 'comment']);
            }
        ];
        $approvalTransitions->each(function($transition, $index) use (&$config, $approvalStates, $approvalTransitions, $model) {

            if ($transition === $approvalTransitions->max()) {
                $to = 'approved';
                $finalApproval = true;
                $config['callbacks']['after'][] = ['on' => $transition, 'do' => [$model, 'afterFinalApproval']];
            } else {
                $to = $approvalStates[$index + 1];
                $finalApproval = false;
            }

            $config['transitions'][$transition] = [
                'from' => [$approvalStates[$index]],
                'to' => $to,
                'properties' => [
                    'approved' => true,
                    'comment' => null,
                    'final-approval' => $finalApproval
                ]
            ];
            $config['callbacks']['after'][] = ['on' => $transition, 'do' => [$model, 'afterApprove']];
        });


        return $config;
    }


    protected function getDefinition($model)
    {
        //return collect(config('workflows.staged'));
        return $model->getWorkflow()->getConfig();
    }

    /**
     * @param $workflowConfig
     * @return Collection
     */
    protected function getApprovalLevels($workflowConfig): Collection
    {
        $approvalLevels = collect($workflowConfig)
            ->keyBy('name')
            ->map(function ($level) {
                if ($level['signatories'] >= 1) {
                    return collect(range(1, $level['signatories']))->map(function ($s, $i) use ($level) {
                        return $level['level'] . '.' . ($i + 1);
                    });
                }
            })->reject(function($level) {
                return is_null($level);
            })
            ->flatten();

        return $approvalLevels;
    }

    /**
     * @param $approvalLevels
     * @return array
     */
    protected function getApprovalStates($approvalLevels): array
    {
        $approvalStates = collect([]);
        $approvalLevels->each(function ($level) use (&$approvalStates) {
            $approvalStates->push('pending_' . $level);
        });
        $approvalStates->each(function ($state) use (&$config) {
            $config['states'][$state] = [
                'type'       => 'normal',
                'properties' => ['name' => $state . ' approval'],
            ];
        });

        return $config;
    }

}