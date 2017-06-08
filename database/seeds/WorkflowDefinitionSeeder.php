<?php

use App\WorkflowDefinition;
use Illuminate\Database\Seeder;

class WorkflowDefinitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rules = collect(config('business-rules.value_based.rules'));
        factory(WorkflowDefinition::class, 1)->create([
            'name' => 'All Rules',
            'definition' => json_encode($rules),
        ]);
        $rules->each(function($rule) {
            factory(WorkflowDefinition::class, 1)->create([
                'name' => $rule['name'],
                'definition' => json_encode($rule),
            ]);
        });

    }
}
