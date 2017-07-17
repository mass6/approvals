<?php

/*
|--------------------------------------------------------------------------
| Model Factories
|--------------------------------------------------------------------------
|
| Here you may define all of your model factories. Model factories give
| you a convenient way to create models for testing and seeding your
| database. Just tell the factory how a default model should look.
|
*/

/** @var \Illuminate\Database\Eloquent\Factory $factory */
$factory->define(App\User::class, function (Faker\Generator $faker, $data) {
    //dd($data);
    static $password;

    return [
        'name' => $faker->name,
        'email' => $faker->unique()->safeEmail,
        'password' => $password ?: $password = bcrypt('secret'),
        'remember_token' => str_random(10),
    ];
});
$factory->define(App\WorkflowDefinition::class, function (Faker\Generator $faker) {
    static $definition;

    return [
        'name' => $faker->title,
        'definition' => $definition ?: $definition = json_encode(config('business-rules.value_based.rules')),
    ];
});
$factory->define(App\Order::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->word,
        'total' => mt_rand(100, 60000),
    ];
});
$factory->define(App\BusinessRule::class, function (Faker\Generator $faker) {
    return [
        'name' => $faker->word,
        'type' => 'value',
    ];
});
$factory->define(App\ValueRule::class, function (Faker\Generator $faker, $data) {
    $min = array_rand([1, 10000, 50000, 100000], 1);
    $max = mt_rand($min + 10000, ($min + 10000) * 5);
    return [
        'name'             => $faker->word,
        //'business_rule_id' => $faker->word,
        'min_value'        => $min,
        'max_value'        => $max,
        'definition'       => '{[]}',
        'config'           => '{[]}',
    ];
});