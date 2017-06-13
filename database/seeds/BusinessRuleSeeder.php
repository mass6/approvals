<?php

use App\ValueRule;
use App\BusinessRule;
use Illuminate\Database\Seeder;

class BusinessRulesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $rules = collect(config('business-rules.value_based.rules'));
        $businessRule = factory(BusinessRule::class)->create([
            'name' => 'Value Based',
            'type' => 'value',
        ]);
        $valueRules = $rules->each(function($rule) use ($businessRule) {
            factory(ValueRule::class)->create([
                'business_rule_id' => $businessRule->id,
                'name'             => $rule['name'],
                'min_value'        => $rule['min_value'],
                'max_value'        => $rule['max_value'],
                'definition'       => json_encode($rule),
                'config'           => json_encode($rule['levels']),
            ]);
        });

    }
}