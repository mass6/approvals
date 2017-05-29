<?php

use App\Workflow;

Route::get('test', function () {

    $w = Workflow::find(3);
    dd($w);




    $order = \App\Order::find(1);
    $sm = $order->getStateMachine();

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
