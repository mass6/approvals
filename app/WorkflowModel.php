<?php

namespace App;

use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Venturecraft\Revisionable\RevisionableTrait;
use Mass6\LaravelStateWorkflows\StateMachineTrait;
use Mass6\LaravelStateWorkflows\StateAuditingTrait;

abstract class WorkflowModel extends Model
{
    use StateMachineTrait, StateAuditingTrait, RevisionableTrait;

    /** @var  WorkflowConfig */
    protected $workflowManager;

    protected $revisionCreationsEnabled = false;

    protected $dontKeepRevisionOf = [
        'id', 'created_at', 'updated_at'
    ];

    protected $dontKeepTransitionAuditTrailOf = [
        'pre-submit'
    ];

    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
        $this->workflowManager = new WorkflowManager($this);
        $this->initializeWorkflow();
    }

    public static function create(array $attributes = [])
    {
        $model = static::query()->create($attributes);
        $model->initializeWorkflow();
        return $model;
    }

    public function newInstance($attributes = [], $exists = false)
    {
        $model = new static((array) $attributes);
        $model->exists = $exists;
        $model->setConnection(
            $this->getConnectionName()
        );
        $model->initializeWorkflow();

        return $model;
    }

    public function newFromBuilder($attributes = [], $connection = null)
    {
        $instance = parent::newFromBuilder($attributes, $connection);
        $instance->restoreStateMachine();
        return $instance;
    }

    public function initializeWorkflow()
    {
        $this->initStateMachine();
        $this->initAuditTrail([
            'auditTrailClass' => TransitionEvent::class,
            'storeAuditTrailOnFirstAfterCallback' => false,
            'attributes' => [[
                'user_id' => function () {
                    return Auth::id();
                }],
            ]
        ]);
        return $this;
    }

    public function restoreStateMachine()
    {
        $this->initializeWorkflow();
        return $this;
    }

    public static function getStateMachineModel($id)
    {
        $model = static::where('id', $id)->first();
        if ($model) {
            return $model->setStateMachineApprovals();
        }
        return $model;
    }

    protected function getStateMachineConfig()
    {
        return $this->workflowManager->getConfig();
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function getNextApprover()
    {
        return $this->currentWorkflow()->getNextApprover();
    }

    public function businessRules()
    {
        return $this->belongsToMany(BusinessRule::class, 'workflows')->withTimestamps();
    }

    public function transitionEvents()
    {
        return $this->morphMany(TransitionEvent::class, 'stateful');
    }

    public function workflows()
    {
        return $this->hasMany(Workflow::class);
    }

    /**
     * @return Workflow|null
     */
    public function getWorkflow()
    {
        return $this->hasMany(Workflow::class)->whereActive(true)->latest()->first();
    }

    public function getWorkflowConfig()
    {
        if ($this->getWorkflow()) {
            return $this->getWorkflow()->getConfig();
        }
    }

    protected function shouldSaveInitialState() : bool
    {
        return false;
    }

    protected function getExcludedTransitions(): array
    {
        return $this->dontKeepTransitionAuditTrailOf;
    }
}
