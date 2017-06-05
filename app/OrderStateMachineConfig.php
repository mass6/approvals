<?php

namespace App;

/**
 * Class OrderStateMachineConfig
 * @package app
 */
class OrderStateMachineConfig implements WorkflowConfig
{

    /**
     * @var
     */
    private $model;

    /**
     * @var LevelBasedWorkflowConfig
     */
    private $workflowConfigGenerator;

    /**
     * OrderStateMachineConfig constructor.
     * @param $model
     */
    public function __construct($model)
    {
        $this->model = $model;
        $this->workflowConfigGenerator = new LevelBasedWorkflowConfig();
    }

    /**
     * Return the state machine configuration
     *
     * @return array
     */
    public function getStateMachineConfig()
    {
        $config = $this->workflowConfigGenerator->generate();
        collect($config['transitions'])->except(['submit', 'reject', 'cancel'])->each(function($transition, $key) use (&$config) {
            $config['callbacks']['after'][] =
                ['on' => $key, 'do' => [$this->model, 'afterApprove']];
        });

        //dd($config);

        return array_merge(
            [
                'class' => get_class($this->model),
                'stateColumn' => 'status',
                'callbacks' => [
                    'after' => [
                        //['on' => 'submit', 'do' => [$this->model, 'afterSubmit']],
                        ['from' => '', 'to' => 'APR', 'do' => [$this->model, 'afterFinalApproval']],
                    ],
                ],
            ], $config);

        return [
            'class' => get_class($this->model),
            'stateColumn' => 'status',
            'states' => [
                'DRA' => [
                    'type' => 'initial',
                    'properties' => ['name' => 'draft'],
                ],
                'PND' => [
                    'type' => 'normal',
                    'properties' => ['name' => 'pending approval'],
                ],
                'APR' => [
                    'type' => 'final',
                    'properties' => ['name' => 'approved'],
                ],
                'CAN' => [
                    'type' => 'final',
                    'properties' => ['name' => 'cancelled'],
                ]
            ],
            'transitions' => [
                'submit' => ['from' => ['DRA'], 'to' => 'PND', 'properties' => []],
                'cancel' => ['from' => ['DRA', 'PND', 'APR'], 'to' => 'CAN', 'properties' => []],
            ],
            'callbacks' => [
                'after' => [
                    ['on' => 'submit', 'do' => [$this->model, 'afterSubmit']],
                    ['from' => '', 'to' => 'APR', 'do' => [$this->model, 'afterFinalApproval']],
                ],
            ],
        ];
    }


}

