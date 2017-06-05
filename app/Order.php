<?php

namespace App;

use App\Events\OrderCreated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Mass6\LaravelStateWorkflows\StateAuditingTrait;
use Mass6\LaravelStateWorkflows\StateMachineTrait;
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
class Order extends WorkflowModel
{

    /**
     * @var array
     */
    protected $guarded = [];

    //protected $saveInitialState = true;
    /**
     * Whether or not a revision entry will be created
     * when a new requisition is first created.
     *
     * @var bool
     */
    //protected $revisionCreationsEnabled = false;

    /**
     * Columns to be excluded from revision history
     *
     * @var array
     */
    //protected $dontKeepRevisionOf = [
    //    'id', 'created_at', 'updated_at'
    //];

    /**
     *  Instance that stores that state machine configuration
     *
     * @var RequisitionStateMachineConfig
     */
    //protected $stateMachineConfig;
    /**
     * @var WorkflowFactory
     */
    //protected $workflowFactory;

    public static function boot()
    {
        parent::boot();
        //static::created(function($model) {
        //   return $model->createOrderWorkflow();
        //});
    }

    /**
     * Requisition constructor.
     *
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
        $this->stateMachineConfig = new OrderStateMachineConfig($this);
        $this->workflowFactory    = new WorkflowFactory($this);
        //$this->initStateMachine();
        //$this->initAuditTrail([
        //    'auditTrailClass' => TransitionEvent::class,
        //    'storeAuditTrailOnFirstAfterCallback' => false,
        //    'attributes' => [[
        //        'user_id' => function () {
        //            return Auth::id();
        //        }],
        //    ]
        //]);
    }




    /**
     * @author Sam Ciaramilaro <sam.ciaramilaro@tattoodo.com>
     *
     * @param $id
     * @return mixed
     */
    //public static function getStateMachineModel($id)
    //{
    //    $model = static::where('id', $id)->first();
    //    if ($model) {
    //        return $model->setStateMachineApprovals();
    //    }
    //    return $model;
    //}


    /**
     * @return array
     */
    //protected function getStateMachineConfig()
    //{
    //    return $this->stateMachineConfig->getStateMachineConfig();
    //}

    /**
     * @author Sam Ciaramilaro <sam.ciaramilaro@tattoodo.com>
     *
     * @return $this
     */
    //public function initializeWorkflow()
    //{
    //    $this->getWorkflowFactory()->initializeWorkflow($this->stateMachine);
    //
    //    return $this;
    //}

    /**
     *
     */
    //public function reinitializeStateMachine()
    //{
    //    $this->initStateMachine();
    //    $this->initializeWorkflow();
    //}

    /**
     * @return WorkflowFactory
     */
    //public function getWorkflowFactory()
    //{
    //    return $this->workflowFactory;
    //}

    /**
     *
     */
    public function createOrderWorkflow()
    {
        // TODO:: replace with strategy for finding appropriate workflow definition
        /** @var WorkflowDefinition $workflowDefinition */
        $workflowDefinition = WorkflowDefinition::latest('id')->first();
        $this->workflowDefinitions()->attach($workflowDefinition, ['definition' => $workflowDefinition->getDefinition()]);
        event(new OrderCreated($this));

    }

    /**
     * @param $transition
     * @return $this
     */
    //public function applyTransition($transition)
    //{
    //    if ($this->can($transition)) {
    //        $this->apply($transition);
    //
    //        return $this;
    //    }
    //
    //}

    /**
     * Relation: Requisition has many transition events
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    //public function transitionEvents()
    //{
    //    return $this->morphMany(TransitionEvent::class, 'stateful');
    //}

    /**
     * @return bool
     */
    public function afterSubmit()
    {
        $this->createOrderWorkflow();
        $this->reinitializeStateMachine();
    }

    /**
     * @param $transitionEvent
     *
     * @return bool
     */
    public function afterApprove($model, \Finite\Event\TransitionEvent  $transitionEvent)
    {
        $this->getWorkflow()->saveApproval($transitionEvent, Auth::user());
        //$this->reinitializeStateMachine();
    }

    /**
     * @param $transitionEvent
     *
     * @return bool
     */
    public function afterFinalApproval(\Finite\Event\TransitionEvent  $transitionEvent)
    {
        event('OrderWasFinalApproved');
        $this->reinitializeStateMachine();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    /**
     * @return mixed
     */
    //public function getStatus()
    //{
    //    return $this->status;
    //}

    /**
     * @return mixed
     */
    //public function getNextApprover()
    //{
    //    return $this->currentWorkflow()->getNextApprover();
    //}


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    //public function workflowDefinitions()
    //{
    //    return $this->belongsToMany(WorkflowDefinition::class, 'workflows')->withTimestamps();
    //}

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    //public function workflows()
    //{
    //    return $this->hasMany(Workflow::class);
    //}

    /**
     * @return mixed
     */
    //public function getWorkflow()
    //{
    //    return $this->hasMany(Workflow::class)->latest()->first();
    //}

    /**
     * Whether of not to save the initial state before any transitions are applied
     *
     * @return boolean
     */
    //public function shouldSaveInitialState(): bool
    //{
    //    return true;
    //}
}
