<?php

use App\Order;
use App\User;
use App\Workflow;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;

Route::get('test', function () {
    Artisan::call('db:seed');
    /** @var User $user */
    $user = User::find(1);
    Auth::setUser($user);
    /** @var Order $order */
    $order = $user->createOrder(['name' => 'Test order', 'total' => 5000]);
    $order = new Order(['user_id' => $user->id, 'name' => 'Test order', 'total' => 5000]);
    $order->save();
    $order->apply('pre-submit');

    //$order = Order::find($order->id);
    return $order->getWorkflow();
    return $order->getTransitions();
    return $order;
});




//Route::get('test', function () {
//    //$lastApproval = '2.3';
//    /** @var Order $order */
//    $order = Order::find(1);
//    $order->initializeWorkflow();
//    dd($order->getTransitions());
//
//    if ($order->getState() === 'PND') {
//
//        $config = $order->getWorkflow()->getConfig();
//        $levels = collect($config['levels'])->keyBy('name')->map(function($l) {
//            return collect(range(1, $l['signatories']))->map(function($s,$i) use ($l) {
//                return $l['level'] . '.' . ($i + 1);
//            });
//        })->flatten();
//
//        $approvals = $order->getWorkflow()->approvals;
//        if (! $approvals->count()) {
//            // set to first approval rule
//            $next = $levels->first();
//        } else {
//            // set to next approval rule
//            $lastApproval = $approvals->max('rule');
//            $next = $levels->first(function($l) use ($lastApproval) {
//                return $l > $lastApproval;
//            });
//        }
//
//        $sm = $order->getStateMachine();
//        $sm->addTransition($next, 'PND', 'PND');
//        $sm->getDispatcher()->addListener('finite.post_transition.' . $next, function(\Finite\Event\TransitionEvent $e) {
//            $this->afterApprove($e);
//        });
//
//        dd($order->getTransitions());
//    }
//
//
//
//
//
//
//
//
//
//
//
//    $levels->first(function($l) use ($lastApproval) {
//       return $l > $lastApproval;
//    });
//
//    dd($next);
//
//    //if ($required->first >= $required->max());
//
//    list($lastLevel,$lastApproval) = explode('.', $last);
//
//    if ($order->getState() === 'PND') {
//        if ($lastApproval < $levels[$lastLevel]['signatories']) {
//            $nextApproval = $lastLevel . '.' . ++$lastApproval;
//        } else if ($lastLevel < $levels->count()) {
//            $nextApproval = ++$lastLevel . '.1';
//        }
//    }
//
//    dd($nextApproval);
//
//    $sm = $order->getStateMachine();
//    dd($sm);
//    $config = $order->getWorkflow()->getConfig();
//
//
//    $sm->addTransition('approval.level_1.1', 'PND', 'PND');
//    $sm->addTransition('approval.level_2.1', 'PND', 'PND');
//    $sm->addTransition('approval.level_2.2', 'PND', 'PND');
//    $sm->addTransition('approval.level_3.1', 'PND', 'PND');
//    $sm->addTransition('approval.level_3.2', 'PND', 'APR');
//
//    dd($order->getStateMachine());
//});
Route::get('/', function () {
    return view('welcome');
});
