<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Workflow
 * @package App
 * @property integer $next_approver
 * @property string $config
 * @property-read \App\User $nextApprover
 * @mixin \Eloquent
 */
class Workflow extends Model
{
    /**
     * @var string
     */
    protected $table = 'workflows';

    /**
     * @var array
     */
    protected $guarded = [];
    
    /**
     * @param User $user
     */
    public function setNextApprover(User $user = null)
    {
        if ($user) {
            //$this->nextApprover()->associate($user);
            $this->next_approver = $user->id;
            $this->save();
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * @return string
     */
    public function getConfig()
    {
        return json_decode($this->config,true);
    }

    /**
     * @param string $config
     */
    public function setConfig(string $config)
    {
        $this->config = $config;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function workflowDefinition()
    {
        return $this->belongsTo(WorkflowDefinition::class);
    }

    /**
     * @return mixed
     */
    public function getNextApprover()
    {
        return $this->nextApprover;
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function nextApprover()
    {
        return $this->belongsTo(User::class, 'next_approver');
    }
}
