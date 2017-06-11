<?php

namespace App;

use Finite\StateMachine\StateMachine;

/**
 * Class WorkflowFactory
 * @package App
 */
class WorkflowFactory
{

    /**
     * @var
     */
    public $model;

    /**
     * @var StateMachine
     */
    public $sm;

    /** @var  Workflow */
    public $workflow;

    /**
     * StateMachineConfigFactory constructor.
     * @param $model
     */
    public function __construct($model)
    {
        $this->model = $model;
    }

    /**
     * @param StateMachine $sm
     * @return StateMachine
     */
    public function initializeWorkflow(StateMachine $sm)
    {
        $factory = new LevelBasedWorkflowFactory();

        return $factory->initializeWorkflow($sm, $this->model);

    }
}
