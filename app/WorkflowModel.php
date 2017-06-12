<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Venturecraft\Revisionable\RevisionableTrait;
use Mass6\LaravelStateWorkflows\StateMachineTrait;
use Mass6\LaravelStateWorkflows\StateAuditingTrait;

abstract class WorkflowModel extends Model
{
    use StateMachineTrait, RevisionableTrait;

    /** @var  WorkflowConfig */
    protected $stateMachineConfig;

    /**
     * @var WorkflowFactory
     */
    protected $workflowFactory;

    /**
     * Whether or not a revision entry will be created
     * when a new requisition is first created.
     *
     * @var bool
     */
    protected $revisionCreationsEnabled = false;

    protected $dontKeepRevisionOf = [
        'id', 'created_at', 'updated_at'
    ];

    /**
     * Requisition constructor.
     *
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
        $this->stateMachineConfig = new OrderStateMachineConfig($this);
    }

    /**
     * @author Sam Ciaramilaro <sam.ciaramilaro@tattoodo.com>
     *
     * @return $this
     */
    public function initializeWorkflow()
    {
        $this->initStateMachine();
        //$this->initAuditTrail([
        //    'auditTrailClass' => TransitionEvent::class,
        //    'storeAuditTrailOnFirstAfterCallback' => false,
        //    'attributes' => [
        //        [
        //            'user_id' => function () {
        //                return Auth::id();
        //            },
        //        ],
        //    ],
        //]);
        //$this->getWorkflowFactory()->initializeWorkflow($this->stateMachine);

        return $this;
    }

    public function newFromBuilder($attributes = [], $connection = null)
    {
        $instance = parent::newFromBuilder($attributes, $connection);
        $instance->restoreStateMachine();
        return $instance;
    }

    public function restoreStateMachine()
    {
        $this->initStateMachine();
    }


    /**
     * @author Sam Ciaramilaro <sam.ciaramilaro@tattoodo.com>
     *
     * @param $id
     * @return mixed
     */
    public static function getStateMachineModel($id)
    {
        $model = static::where('id', $id)->first();
        if ($model) {
            return $model->setStateMachineApprovals();
        }
        return $model;
    }

    /**
     * @return array
     */
    protected function getStateMachineConfig()
    {
        return $this->stateMachineConfig->getStateMachineConfig();
    }

    /**
     * @return mixed
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return WorkflowFactory
     */
    public function getWorkflowFactory()
    {
        return $this->workflowFactory;
    }

    /**
     * @return mixed
     */
    public function getNextApprover()
    {
        return $this->currentWorkflow()->getNextApprover();
    }


    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function workflowDefinitions()
    {
        return $this->belongsToMany(WorkflowDefinition::class, 'workflows')->withTimestamps();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function workflows()
    {
        return $this->hasMany(Workflow::class);
    }

    /**
     * @return mixed
     */
    public function getWorkflow()
    {
        return $this->hasMany(Workflow::class)->latest()->first();
    }
}
