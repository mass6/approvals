<?php

namespace App;

use App\Events\OrderCreated;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Venturecraft\Revisionable\RevisionableTrait;

/**
 * Class Order
 * @package App
 * @property string $status
 * @property-read WorkflowDefinition $workflowDefinitions
 * @property-read Workflow $workflows
 * @property-read Workflow $workflow
 * @mixin \Eloquent
 */
class Order extends Model
{
    use FiniteStateMachineTrait, RevisionableTrait;

    protected $guarded = [];
    protected $workflowManager;
    protected $revisionCreationsEnabled = false;
    protected $dontKeepRevisionOf = [
        'id', 'created_at', 'updated_at'
    ];

    public function __construct($attributes = [])
    {
        $this->initStateMachine();
        parent::__construct($attributes);
        $this->workflowManager = new WorkflowManager($this);
    }

    public function newFromBuilder($attributes = [], $connection = null)
    {
        $instance = parent::newFromBuilder($attributes, $connection);
        $instance->restoreStateMachine();

        return $instance;
    }

    protected function getStateMachineConfig()
    {
        return [
            'class' => get_class($this),
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
                //'reject' => ['from' => ['PND'], 'to' => 'DRA', 'properties' => []],
                'cancel' => ['from' => ['DRA', 'PND', 'APR'], 'to' => 'CAN', 'properties' => []],
            ],
            'callbacks' => [
                'before' => [
                    //['on' => 'submit', 'do' => [$this, 'beforeSubmit']],
                ],
                'after' => [
                    ['on' => 'submit', 'do' => [$this, 'afterSubmit']],
                    ['on' => 'reject', 'do' => [$this, 'afterReject']],
                    ['from' => '', 'to' => 'APR', 'do' => [$this, 'afterFinalApproval']],
                ],
            ],
        ];
    }



    /** createOrderWorkflow */
    public function createOrderWorkflow()
    {
        $orderValue = $this->total;

        // TODO:: replace with strategy for finding appropriate business rule
        $businessRule = BusinessRule::first();

        $defintition = $businessRule->rules->first(function($rule) use ($orderValue) {
            if (isset($rule['max_value'])) {
                return $orderValue >= $rule['min_value'] && $orderValue <= $rule['max_value'];
            }
            return $orderValue >= $rule['min_value'];
        });

        $this->businessRules()->attach($businessRule, ['config' => $defintition->config]);
        //event(new OrderCreated($this));

    }

    //public function beforeSubmit($model, $event)
    //{
    //    return false;
    //    $model->createOrderWorkflow();
    //}

    public function afterSubmit($model, $event)
    {
        $this->createOrderWorkflow();
        $this->restoreStateMachine();
    }

    public function afterApprove($model, \Finite\Event\TransitionEvent  $transitionEvent)
    {
        $this->getWorkflow()->saveApproval(
                Auth::user(),
                $transitionEvent->getTransition()->getName(),
                $transitionEvent->get('final-approval', false),
                true,
                $transitionEvent->get('comment', null)
        );
        $this->restoreStateMachine();
    }

    public function afterFinalApproval(\Finite\Event\TransitionEvent  $transitionEvent)
    {
        event('OrderWasFinalApproved');
        $this->restoreStateMachine();
    }

    public function afterReject($model, $event)
    {
        $this->getWorkflow()->saveRejection(Auth::user(), $event->get('approval_level'), $event->get('comment'));
        $this->restoreStateMachine();
    }

    public function restoreStateMachine()
    {
        $this->initStateMachine();
        $this->initializeWorkflow();
    }

    public function initializeWorkflow()
    {
        $this->getWorkflowManager()->initializeWorkflow($this->stateMachine);

        return $this;
    }

    public function getWorkflowManager()
    {
        return $this->workflowManager;
    }

    public function businessRules()
    {
        return $this->belongsToMany(BusinessRule::class, 'workflows')->withTimestamps();
    }

    public function workflows()
    {
        return $this->hasMany(Workflow::class);
    }

    public function getWorkflow()
    {
        return $this->hasMany(Workflow::class)->latest()->first();
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getStatus()
    {
        return $this->status;
    }
}
