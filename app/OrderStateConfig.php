<?php

namespace app;

class OrderStateConfig
{

    /**
     * @var Requisition
     */
    private $requisition;

    public function __construct($model)
    {
        $this->model = $model;
    }

    //public function setConfig()
    //{
    //
    //}

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
                ]
            ],
            'transitions' => [
                'submit' => ['from' => ['DRA'], 'to' => 'PND', 'properties' => []],
                'approve' => ['from' => ['PND', 'APR'], 'to' => 'APR', 'properties' => []],
            ],
            'callbacks' => [
                'before' => [
                    ['on' => 'approve', 'do' => [$this->model, 'beforeApprove']],
                    //['from' => 's2', 'to' => 's3', 'do' => function ($myStatefulInstance, $transitionEvent) {
                    //    echo "Before callback from 's2' to 's3'";// debug
                    //}],
                    //['from' => '-s3', 'to' => ['s3' ,'s1'], 'do' => [$this, 'fromStatesS1S2ToS1S3']],
                ],
                //'after' => [
                //    ['from' => 'all', 'to' => 'all', 'do' => [$this, 'afterAllTransitions']],
                //],
            ],
        ];
    }


}

