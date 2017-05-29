<?php

namespace App;

use Finite\State\State;
use Finite\State\StateInterface;
use Finite\StateMachine\StateMachine;

class StateMachineConfigFactory
{

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
        $this->sm = new StateMachine($model);
    }

    public function getConfig()
    {
        $config = json_decode($this->model->getWorkflow()->getConfig(), true);

        $levels = collect($config['levels']);
    }

    /**
     *
     */
    public function getStateMachine()
    {
        $sm = $this->sm;

        //$config = $this->getConfig();
        //\Log::info($config);

        $sm->addState(new State('DRA', StateInterface::TYPE_INITIAL));
        $sm->addState('PND');
        $sm->addState('APR');
        $sm->addState(new State('CAN', StateInterface::TYPE_FINAL));

        $sm->addTransition('submit', 'DRA', 'PND');
        $sm->addTransition('cancel', ['DRA', 'PND', 'APR'], 'CAN');

        $sm->initialize();

        return $sm;
    }
}
