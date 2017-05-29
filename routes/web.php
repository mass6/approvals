<?php

use App\Order;
use App\Workflow;

Route::get('test', function () {

    /** @var Order $order */
    $order = Order::getFiniteModel(1);
    // $order->configureStateMachine();

    $sm = $order->getStateMachine();
    dd($sm);
    $config = $order->getWorkflow()->getConfig();


    $sm->addTransition('approval.level_1.1', 'PND', 'PND');
    $sm->addTransition('approval.level_2.1', 'PND', 'PND');
    $sm->addTransition('approval.level_2.2', 'PND', 'PND');
    $sm->addTransition('approval.level_3.1', 'PND', 'PND');
    $sm->addTransition('approval.level_3.2', 'PND', 'APR');

    dd($order->getStateMachine());
});
Route::get('/', function () {
    return view('welcome');
});
