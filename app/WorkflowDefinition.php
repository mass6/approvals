<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class WorkflowDefinition
 * @package App
 * @property string $config
 * @property string $definition
 * @mixin \Eloquent
 */
class WorkflowDefinition extends Model
{

    /**
     * @var string
     */
    protected $table = 'workflow_definitions';

    /**
     * @var array
     */
    protected $guarded = [];

    ///**
    // *
    // */
    //public static function boot()
    //{
    //    parent::boot();
    //    static::creating(function ($model) {
    //        $model->config = json_encode(config('workflows.basic'));
    //    });
    //}

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function workflows()
    {
        return $this->hasMany(Workflow::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'workflows')->withTimestamps();
    }

    /**
     * @return string
     */
    public function getDefinition()
    {
        return $this->definition;
    }

    /**
     * @return string
     */
    public function getConfig()
    {
        return $this->config;
    }
}
