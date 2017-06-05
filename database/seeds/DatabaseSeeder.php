<?php

use App\Order;
use App\User;
use App\WorkflowDefinition;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
         $users = factory(User::class, 10)->create();
         $user = $users->first();
         factory(WorkflowDefinition::class, 1)->create(['name' => 'basic']);
         factory(WorkflowDefinition::class, 1)->create(['name' => 'standard']);
         factory(WorkflowDefinition::class, 1)->create(['name' => 'staged']);
         //factory(Order::class, 10)->create(['user_id' => $user->id]);
    }
}
