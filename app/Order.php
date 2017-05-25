<?php

namespace App;

use App\Events\OrderCreated;
use Illuminate\Database\Eloquent\Model;

/**
 * Class Order
 * @package App
 * @property string $status
 * @mixin \Eloquent
 */
class Order extends Model
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
        static::created(function ($model) {
            $model->createOrderWorkflow();
        });
    }

    /**
     *
     */
    public function createOrderWorkflow()
    {
        $workflow = Workflow::latest()->first();
        $this->workflows()->attach($workflow);
        event(new OrderCreated($this));

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
     * @return OrderWorkflow
     */
    public function currentOrderWorkflow(): OrderWorkflow
    {
        return $this->orderWorkflows()->latest()->first();
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function orderWorkflows()
    {
        return $this->hasMany(OrderWorkflow::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function workflows()
    {
        return $this->belongsToMany(Workflow::class, 'order_workflows')->withTimestamps();
    }
}
