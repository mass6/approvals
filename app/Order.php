<?php

namespace App;

use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Venturecraft\Revisionable\RevisionableTrait;
use Mass6\LaravelStateWorkflows\StateMachineTrait;
use Mass6\LaravelStateWorkflows\StateAuditingTrait;

class Order extends WorkflowModel
{
    use StateMachineTrait, StateAuditingTrait, RevisionableTrait;

    /** @var  WorkflowConfig */
    protected $workflowManager;
    protected $guarded = [];
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

    protected function getStateMachineConfig()
    {
        if ($definition = $this->getWorkflowDefinition()) {
            return $this->getWorkflowConfig($definition);
        } else {
            return $this->getBaseConfig();
        }
    }

    protected function mergeWorkflowConfig($workflowConfig)
    {
        $baseConfig =  $this->getBaseConfig();
        $config = [];
        $config['class'] = $baseConfig['class'];
        $config['states'] = array_merge(array_get($baseConfig, 'states', []), array_get($workflowConfig, 'states', []));
        $config['transitions'] = array_merge(array_get($baseConfig, 'transitions', []), array_get($workflowConfig, 'transitions', []));
        $config['transitions']['cancel']['from'] = array_merge(array_get($baseConfig, 'transitions.cancel.from', []), array_get($workflowConfig, 'transitions.cancel.from', []));
        $config['callbacks']['before']           = array_merge(array_get($baseConfig, 'callbacks.before', []), array_get($workflowConfig, 'callbacks.before', []));
        $config['callbacks']['after']            = array_merge(array_get($baseConfig, 'callbacks.after', []), array_get($workflowConfig, 'callbacks.after', []));

        return $config;
    }

    protected function getBaseConfig(): array
    {
        return [
            'class'       => get_class($this),
            'stateColumn' => 'status',
            'states'      => [
                'draft'     => [
                    'type'       => 'initial',
                    'properties' => ['name' => 'draft'],
                ],
                'approved'  => [
                    'type'       => 'final',
                    'properties' => ['name' => 'approved'],
                ],
                'cancelled' => [
                    'type'       => 'final',
                    'properties' => ['name' => 'cancelled'],
                ]
            ],
            'transitions' => [
                'cancel'     => ['from' => ['draft', 'approved'], 'to' => 'cancelled', 'properties' => []],
                'pre-submit' => ['from' => ['draft'], 'to' => 'draft', 'properties' => []],
            ],
            'callbacks'   => [
                'before' => [
                    ['on' => 'pre-submit', 'do' => [$this, 'beforePreSubmit']],
                ],
                'after'  => [
                    ['on' => 'pre-submit', 'do' => [$this, 'afterPreSubmit']],
                    ['on' => 'reject', 'do' => [$this, 'afterReject']],
                    ['from' => 'all', 'to' => 'approved', 'do' => [$this, 'afterFinalApproval']],
                ],
            ],
        ];
    }

    public function restoreStateMachine()
    {
        $this->initializeWorkflow();
        return $this;
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

    public function getWorkflowDefinition()
    {
        if ($this->getWorkflow()) {
            return $this->getWorkflow()->getDefinition();
        }
    }

    public function getWorkflowConfig($definition)
    {
        $workflowGenerator = new ApprovalLevelsConfig();
        $workflowConfig = $workflowGenerator->generate($this, $definition);

        return $this->mergeWorkflowConfig($workflowConfig);
    }

    protected function shouldSaveInitialState() : bool
    {
        return false;
    }

    protected function getExcludedTransitions(): array
    {
        return $this->dontKeepTransitionAuditTrailOf;
    }
    /**
     *
     */
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
        $this->businessRules()->attach($businessRule, [
            'definition' => $defintition->config,
            'config' => '',
            'active' => true,
        ]);
        // $this->getWorkflow()->setWorkflowConfig();
        //event(new OrderCreated($this));
    }

    /**
     * @return bool
     */
    public function beforePreSubmit()
    {
        $this->createOrderWorkflow();
        $this->restoreStateMachine();
    }

    /**
     * @return bool
     */
    public function afterPreSubmit()
    {
        $this->apply('submit');
    }

    public function afterApprove($model, \Finite\Event\TransitionEvent  $transitionEvent)
    {
        $this->getWorkflow()->logApproval(
            Auth::user(),
            $transitionEvent->getTransition()->getName(),
            $transitionEvent->get('final-approval', false),
            true,
            $transitionEvent->get('comment', null)
        );
    }

    public function afterFinalApproval($model, \Finite\Event\TransitionEvent  $transitionEvent)
    {
        event('OrderWasFinalApproved');
    }

    public function afterReject($model, $transitionEvent)
    {
        $workflow = $this->getWorkflow();
        $workflow->logRejection(Auth::user(), $transitionEvent->get('comment'));
        $workflow->deactivate();
        $this->restoreStateMachine();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
