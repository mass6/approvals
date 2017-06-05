<?php

namespace App;

use Illuminate\Support\Collection;

class LevelBasedWorkflowConfig
{
    public function generate()
    {
        $def = $this->getDefinition();
        $approvalLevels = $this->getApprovalLevels($def);

        $config['states'] = $baseStates = [
            'draft' => [
                'type'       => 'initial',
                'properties' => ['name' => 'draft'],
            ],
            'approved' => [
                    'type'       => 'final',
                    'properties' => ['name' => 'approved'],
            ],
            'cancelled' => [
                    'type'       => 'final',
                    'properties' => ['name' => 'cancelled'],
            ],
        ];
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

        $config['transitions']['cancel'] = ['from' => collect($baseStates)->except('cancelled')->keys()->merge($approvalStates)->toArray(), 'to' => 'CAN', 'properties' => []];
        $config['transitions']['submit'] = ['from' => ['draft'], 'to' => $approvalStates->first(), 'properties' => []];
        $config['transitions']['reject'] = ['from' => $approvalStates->toArray(), 'to' => 'draft', 'properties' => []];
        $approvalTransitions->each(function($transition, $index) use (&$config, $approvalStates, $approvalTransitions) {
            if ($transition !== $approvalTransitions->max()) {
                $config['transitions'][$transition] = ['from' => [$approvalStates[$index]], 'to' => $approvalStates[$index + 1], 'properties' => ['final-approval' => false]];
            } else {
                $config['transitions'][$transition] = ['from' => [$approvalStates[$index]], 'to' => 'approved', 'properties' => ['final-approval' => true]];
            }
        });


        //dd($config);
        //$config['callbacks'] = [
        //    'after' => [
        //        ['on' => 'submit', 'do' => ['App\Order', 'afterSubmit']],
        //        ['from' => '', 'to' => 'APR', 'do' => ['App\Order', 'afterFinalApproval']],
        //    ],
        //];


        //dd($config);

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
