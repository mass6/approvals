<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class OrderListener
{

    /**
     * Handle the event.
     *
     * @param  OrderCreated  $event
     * @return void
     */
    public function handle(OrderCreated $event)
    {
        $workflow = $event->order->getWorkflow();
        $workflow->setNextApprover(User::find(8));
    }
}
