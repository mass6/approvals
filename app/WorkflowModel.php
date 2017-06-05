<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Venturecraft\Revisionable\RevisionableTrait;
use Mass6\LaravelStateWorkflows\StateMachineTrait;
use Mass6\LaravelStateWorkflows\StateAuditingTrait;

abstract class WorkflowModel extends Model
{
    use StateMachineTrait, StateAuditingTrait, RevisionableTrait;

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
     * @author Sam Ciaramilaro <sam.ciaramilaro@tattoodo.com>
     *
     * @return $this
     */
    public function initializeWorkflow()
    {
        $this->initStateMachine();
        $this->initAuditTrail([
            'auditTrailClass' => TransitionEvent::class,
            'storeAuditTrailOnFirstAfterCallback' => false,
            'attributes' => [
                [
                    'user_id' => function () {
                        return Auth::id();
                    },
                ],
            ],
        ]);
        //$this->getWorkflowFactory()->initializeWorkflow($this->stateMachine);

        return $this;
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
     *
     */
    public function reinitializeStateMachine()
    {
        $this->initStateMachine();
        $this->initializeWorkflow();
    }

    /**
     * Relation: Requisition has many transition events
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function transitionEvents()
    {
        return $this->morphMany(TransitionEvent::class, 'stateful');
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

    /**
     * Whether of not to save the initial state before any transitions are applied
     *
     * @return boolean
     */
    public function shouldSaveInitialState(): bool
    {
        if (property_exists($this, 'saveInitialState')) {
            return $this->saveInitialState;
        }
        return false;
    }
}
