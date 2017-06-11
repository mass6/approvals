<?php

namespace app;

/**
 * Class OrderStateMachineConfig
 * @package app
 */
class OrderStateMachineConfig
{

    /**
     * @var
     */
    private $model;

    /**
     * OrderStateMachineConfig constructor.
     * @param $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * Return the state machine configuration
     *
     * @return array
     */
    public function getStateMachineConfig()
    {
        return [
            'class' => get_class($this->model),
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
                'reject' => ['from' => ['PND'], 'to' => 'DRA', 'properties' => []],
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

