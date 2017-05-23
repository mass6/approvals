<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Class Workflow
 * @package App
 */
class Workflow extends Model
{

    /**
     * @var array
     */
    protected $guarded = [];

    /**
     *
     */
    public static function boot()
    {
        parent::boot();
        static::creating(function ($model) {
            $model->config = json_encode(config('workflows.basic'));
        });
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function orders()
    {
        return $this->belongsToMany(Order::class, 'order_workflows')->withTimestamps();
    }


}
