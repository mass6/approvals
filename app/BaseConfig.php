<?php

namespace App;

class BaseConfig implements WorkflowConfig
{
    /**
     * @var
     */
    private $model;

    /**
     * @var JustInTimeConfig
     */
    private $workflowConfigGenerator;

    /**
     * OrderStateMachineConfig constructor.
     * @param $model
     */
    public function __construct($model)
    {
        $this->model = $model;
        $this->workflowConfigGenerator = new JustInTimeConfig();
    }

    /**
     * Return the state machine configuration
     *
     * @return array
     */
    public function getStateMachineConfig()
    {

        $baseConfig = [
            'class' => get_class($this->model),
            'states' => [
                'draft' => [
                    'type' => 'initial',
                    'properties' => ['name' => 'draft'],
                ],
                'approved' => [
                    'type' => 'final',
                    'properties' => ['name' => 'approved'],
                ],
                'cancelled' => [
                    'type' => 'final',
                    'properties' => ['name' => 'cancelled'],
                ]
            ],
            'transitions' => [
                'cancel' => ['from' => ['draft', 'approved'], 'to' => 'cancelled', 'properties' => []],
                'pre-submit' => ['from' => ['draft'], 'to' => 'draft', 'properties' => []],
            ],
            'callbacks' => [
                'before' => [
                    ['on' => 'pre-submit', 'do' => [$this->model, 'beforePreSubmit']],
                ],
                'after' => [
                    ['on' => 'pre-submit', 'do' => [$this->model, 'afterPreSubmit']],
                    ['on' => 'reject', 'do' => [$this->model, 'afterReject']],
                    ['from' => '', 'to' => 'approved', 'do' => [$this->model, 'afterFinalApproval']],
                ],
            ],
        ];

        if ($this->model->workflows()->count()) {
            \Log::info('Workflow exists; add approvals to base config');
            $workflowConfig = $this->workflowConfigGenerator->generate($this->model);
            $config['class'] = $baseConfig['class'];
            $config['states'] = array_merge(array_get($baseConfig, 'states', []), array_get($workflowConfig, 'states', []));
            $config['transitions'] = array_merge(array_get($baseConfig, 'transitions', []), array_get($workflowConfig, 'transitions', []));
            $config['transitions']['cancel']['from'] = array_merge(array_get($baseConfig, 'transitions.cancel.from', []), array_get($workflowConfig, 'transitions.cancel.from', []));
            $config['callbacks']['before'] = array_merge(array_get($baseConfig, 'callbacks.before', []), array_get($workflowConfig, 'callbacks.before', []));
            $config['callbacks']['after'] = array_merge(array_get($baseConfig, 'callbacks.after', []), array_get($workflowConfig, 'callbacks.after', []));

            // dd($config);
        } else {
            \Log::info('No workflow; return base config.');
            $config = $baseConfig;
        }

        return $config;
    }
}
