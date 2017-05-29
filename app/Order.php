<?php

namespace App;

use App\Events\OrderCreated;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
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
    use FiniteStateMachineTrait, FiniteAuditTrailTrait, RevisionableTrait;

    /**
     * @var array
     */
    protected $guarded = [];

    /**
     * Whether or not a revision entry will be created
     * when a new requisition is first created.
     *
     * @var bool
     */
    protected $revisionCreationsEnabled = true;

    /**
     * Columns to be excluded from revision history
     *
     * @var array
     */
    protected $dontKeepRevisionOf = [
        'id', 'created_at', 'updated_at'
    ];

    /**
     *  Instance that stores that state machine configuration
     *
     * @var RequisitionStateMachineConfig
     */
    protected $stateMachineConfig;

    /**
     * Requisition constructor.
     *
     * @param array $attributes
     */
    public function __construct($attributes = [])
    {
        parent::__construct($attributes);
        $this->initStateMachine();
        //$this->stateMachineConfig = new OrderStateConfig($this);
        $this->initAuditTrail([
            'auditTrailClass' => TransitionEvent::class,
            'storeAuditTrailOnFirstAfterCallback' => false,
            'attributes' => [[
                'user_id' => function () {
                    return Auth::id();
                }],
            ]
        ]);
    }

    /**
     *
     */
    public static function boot()
    {
        parent::boot();
        static::created(function ($model) {
            $model->createOrderWorkflow();
        });
    }

    /**
     *
     */
    public function createOrderWorkflow()
    {
        // TODO:: replace with strategy for finding appropriate workflow definition
        /** @var WorkflowDefinition $workflowDefinition */
        $workflowDefinition = WorkflowDefinition::latest('id')->first();
        $this->workflowDefinitions()->attach($workflowDefinition, ['config' => $workflowDefinition->getConfig()]);
        event(new OrderCreated($this));

    }

    /**
     * @param $transition
     * @return $this
     */
    public function applyTransition($transition)
    {
        if ($this->can($transition)) {
            $this->apply($transition);

            return $this;
        }

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
     * @return array
     */
    protected function stateMachineConfig()
    {
        return $this->stateMachineConfig->getStateMachineConfig();
    }

    /**
     * @param $requisition
     * @param $transitionEvent
     *
     * @return bool
     */
    public function beforeApprove($requisition, $transitionEvent)
    {
        return false;
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
    public function getStatus()
    {
        return $this->status;
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
