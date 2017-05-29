<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\User;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

class StateMachineListener
{

    /**
     * Handle the event.
     *
     * @return void
     */
    public function handle()
    {
        \Log::info('State Machine Listener');
    }
}
