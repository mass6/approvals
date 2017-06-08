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
        factory(User::class, 10)->create();
        $this->call(WorkflowDefinitionSeeder::class);
    }
}
