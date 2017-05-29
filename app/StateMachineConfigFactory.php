<?php

namespace App;

use Finite\State\State;
use Finite\State\StateInterface;
use Finite\StateMachine\StateMachine;
use Finite\Transition\Transition;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
    }

    // public function initializeStateMachine()
    // {
    //     $sm = new StateMachine($this->model);
    //     $this->configureStateMachine($sm);
    //     $this->sm = $sm;
    // }

    public function getStateMachine()
    {
        return $this->sm;
    }

    public function configureTransitions(StateMachine $sm)
    {
        $config = $this->model->getWorkflow()->getConfig();
        $levels = collect($config['levels']);
        \Log::info($levels);
        $approvals = $this->model->getWorkflow()->approvals;
        if ($approvals) {
            $max = $approvals->max('rule');
            \Log::info($max);
        }





        // dd($levels);
        // $sm = $this->sm;
        // $resolver = new OptionsResolver();
        // $resolver->setAllowedValues('properties',['user']);
        // $transition = new Transition('approve.level_1.1', 'PND', 'PND', null, $resolver);
        // $sm->addTransition($transition);
        $sm->addTransition('approve.level_1.1', 'PND', 'PND');
        $sm->getDispatcher()->addListener('finite.post_transition.approve.level_1.1', function(\Finite\Event\TransitionEvent $e) {
            $this->afterApprove($e);
        });
        $sm->addTransition('approve.level_2.1', 'PND', 'PND');
        $sm->getDispatcher()->addListener('finite.post_transition.approve.level_2.1', function(\Finite\Event\TransitionEvent $e) {
            $this->afterApprove($e);
        });
        $sm->addTransition('approve.level_2.2', 'PND', 'PND');
        $sm->getDispatcher()->addListener('finite.post_transition.approve.level_2.2', function(\Finite\Event\TransitionEvent $e) {
            $this->afterApprove($e);
        });
        $sm->addTransition('approve.level_3.1', 'PND', 'PND');
        $sm->getDispatcher()->addListener('finite.post_transition.approve.level_3.1', function(\Finite\Event\TransitionEvent $e) {
            $this->afterApprove($e);
        });
        $sm->addTransition('approve.level_3.2', 'PND', 'APR');

        return $sm;
    }

    protected function afterApprove(\Finite\Event\TransitionEvent $event)
    {
        $this->model->afterApprove($event);
    }

    public function getConfig()
    {
        $config = json_decode($this->model->getWorkflow()->getConfig(), true);

        $levels = collect($config['levels']);
    }

    /**
     *
     */
    public function setStateMachine()
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
