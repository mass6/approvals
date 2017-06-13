<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Venturecraft\Revisionable\RevisionableTrait;
use Mass6\LaravelStateWorkflows\StateMachineTrait;

abstract class WorkflowModel extends Model
{
    use StateMachineTrait, RevisionableTrait;

    /** @var  WorkflowConfig */
    protected $stateMachineConfig;

    protected $revisionCreationsEnabled = false;

    protected $dontKeepRevisionOf = [
        'id', 'created_at', 'updated_at'
    ];

    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
        $this->stateMachineConfig = new LevelBasedWorkflowConfig($this);
    }

    public static function create(array $attributes = [])
    {
        $model = static::query()->create($attributes);
        $model->initializeWorkflow();
        \Log::info("Order: " . $model->id);
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
        return $this;
    }

    public function restoreStateMachine()
    {
        $this->initStateMachine();
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
        return $this->stateMachineConfig->getConfig();
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

    public function workflows()
    {
        return $this->hasMany(Workflow::class);
    }

    public function getWorkflow()
    {
        return $this->hasMany(Workflow::class)->whereActive(true)->latest()->first();
    }
}
