<?php

namespace App;

use Illuminate\Support\Collection;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ApprovalLevelsConfig
{
    public function generate($model)
    {
        $def = $this->getDefinition();
        $approvalLevels = $this->getApprovalLevels($def);

        $approvalStates = collect([]);
        $approvalTransitions = collect([]);
        $approvalLevels->each(function($level) use (&$approvalStates, &$approvalTransitions) {
            $approvalStates->push('pending_' . $level);
            $approvalTransitions->push('approve_' . $level);
        });

        $approvalStates->each(function($transition) use (&$config) {
            $config['states'][$transition] = [
                'type' => 'normal',
                'properties' => ['name' => 'pending level ' . $transition . ' approval'],
            ];
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
            if ($transition !== $approvalTransitions->max()) {
                $config['transitions'][$transition] = [
                    'from' => [$approvalStates[$index]],
                    'to' => $approvalStates[$index + 1],
                    'properties' => [
                        'approved' => true,
                        'comment' => null,
                        'final-approval' => false
                    ]
                ];
                $config['callbacks']['after'][] = ['on' => $transition, 'do' => [$model, 'afterApprove']];
            } else {
                $config['transitions'][$transition] = ['from' => [$approvalStates[$index]], 'to' => 'approved', 'properties' => ['final-approval' => true]];
            }
        });

        return $config;
    }


    protected function getDefinition()
    {
        return collect(config('workflows.staged'));
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

}
