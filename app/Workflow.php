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


    /**
     * @author Sam Ciaramilaro <sam.ciaramilaro@tattoodo.com>
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function approvals()
    {
        return $this->hasMany(Approval::class);
    }

    /**
     * @author   Sam Ciaramilaro <sam.ciaramilaro@tattoodo.com>
     *
     * @param User   $user
     * @param string $rule
     * @param bool   $final
     * @param bool   $approved
     * @param null   $comment
     * @return Model
     */
    public function saveApproval(User $user, string $rule, bool $final, bool $approved, $comment = null)
    {
        return $this->approvals()->create([
            'user_id'  => $user->id,
            'rule'     => $rule,
            'final'    => $final,
            'approved' => $approved,
            'comment'  => $comment
        ]);
    }

    /**
     * @author   Sam Ciaramilaro <sam.ciaramilaro@tattoodo.com>
     *
     * @param User   $user
     * @param string $rule
     * @param string $comment
     * @return Model
     */
    public function saveRejection(User $user, string $rule, string $comment)
    {
        return $this->saveApproval($user, $rule, false, false, $comment);
    }


}
