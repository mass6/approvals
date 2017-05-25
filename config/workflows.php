<?php

return [
    'basic' => [
        'type' => 'direct',
        'approvers' => [1=>7,2=>9],
    ],
    'staged' => [
        'levels' => [
            ['level' => 1, 'name' => 'level1', 'limit' => 10000, 'signatories' => 1, 'allow-level-override' => false, 'allow-cumulative-override' => false, 'allow-global-override' => false],
            ['level' => 2, 'name' => 'level2', 'limit' => 50000, 'signatories' => 2, 'allow-level-override' => false, 'allow-cumulative-override' => false, 'allow-global-override' => false],
            ['level' => 3, 'name' => 'level3', 'limit' => 100000, 'signatories' => 2, 'allow-level-override' => false, 'allow-cumulative-override' => false, 'allow-global-override' => false],
        ],
        'level-override-enabled' => false,
        'cumulative-override-enabled' => false,
        'global-override-enabled' => false,
    ],
];