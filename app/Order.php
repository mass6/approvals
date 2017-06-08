<?php

namespace App;

use App\Events\OrderCreated;
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
    use FiniteStateMachineTrait,  RevisionableTrait;

    protected $guarded = [];
    protected $workflowFactory;
    protected $revisionCreationsEnabled = false;
    protected $dontKeepRevisionOf = [
        'id', 'created_at', 'updated_at'
    ];

    public function __construct($attributes = [])
    {
        //$this->initStateMachine();
        parent::__construct($attributes);
    }

    //public function newFromBuilder($attributes = [], $connection = null)
    //{
    //    $instance = parent::newFromBuilder($attributes, $connection);
    //    $instance->restoreStateMachine();
    //
    //    return $instance;
    //}

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
                'reject' => ['from' => ['PND'], 'to' => 'DRA', 'properties' => []],
                'cancel' => ['from' => ['DRA', 'PND', 'APR'], 'to' => 'CAN', 'properties' => []],
            ],
            'callbacks' => [
                'before' => [
                    ['on' => 'submit', 'do' => [$this->model, 'beforeSubmit']],
                ],
                'after' => [
                    ['on' => 'submit', 'do' => [$this->model, 'afterSubmit']],
                    ['from' => '', 'to' => 'APR', 'do' => [$this->model, 'afterFinalApproval']],
                ],
            ],
        ];
    }



    /** createOrderWorkflow */
    public function createOrderWorkflow()
    {
        $orderValue = $this->total;

        // TODO:: replace with strategy for finding appropriate workflow definition
        /** @var WorkflowDefinition $workflowDefinition */
        $workflowDefinition = WorkflowDefinition::whereName('All Rules')->first();
        $rules = collect(json_decode($workflowDefinition->getDefinition(), true));

        $config = $rules->first(function($rule) use ($orderValue) {
            if (isset($rule['max_value'])) {
                return $orderValue >= $rule['min_value'] && $orderValue <= $rule['max_value'];
            }

            return $orderValue >= $rule['min_value'];
        });
        $this->workflowDefinitions()->attach($workflowDefinition, ['config' => json_encode($config)]);
        //event(new OrderCreated($this));

    }

    public function beforeSubmit($model, $event)
    {
        $model->createOrderWorkflow();
    }

    public function afterSubmit()
    {
        $this->createOrderWorkflow();
        $this->restoreStateMachine();
    }

    public function afterApprove(\Finite\Event\TransitionEvent  $transitionEvent)
    {
        $this->getWorkflow()->saveApproval($transitionEvent, Auth::user());
        $this->restoreStateMachine();
    }

    public function afterFinalApproval(\Finite\Event\TransitionEvent  $transitionEvent)
    {
        event('OrderWasFinalApproved');
        $this->restoreStateMachine();
    }

    //public function restoreStateMachine()
    //{
    //    $this->initStateMachine();
    //    $this->initializeWorkflow();
    //}

    //public function initializeWorkflow()
    //{
    //    $this->getWorkflowFactory()->initializeWorkflow($this->stateMachine);
    //
    //    return $this;
    //}

    //public function getWorkflowFactory()
    //{
    //    return $this->workflowFactory;
    //}

    //public function transitionEvents()
    //{
    //    return $this->morphMany(TransitionEvent::class, 'stateful');
    //}

    //public function getNextApprover()
    //{
    //    return $this->currentWorkflow()->getNextApprover();
    //}

    public function workflowDefinitions()
    {
        return $this->belongsToMany(WorkflowDefinition::class, 'workflows')->withTimestamps();
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
