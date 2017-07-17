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
     * @var WorkflowManager
     */
    private $workflowConfigGenerator;

    /**
     * OrderStateMachineConfig constructor.
     * @param $model
     */
    public function __construct($model)
    {
        $this->model = $model;
        $this->workflowConfigGenerator = new WorkflowManager();
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
    }


}

