<?php

return [
    'value_based' => [
        'rules' => [
            [
                'name' => 'Under 100',
                'min_value' => 1,
                'max_value' => 10000,
                'levels' => [
                    ['level'                     => 1,
                     'name'                      => 'level1',
                     'signatories'               => 1,
                    ],
                    ['level'                     => 2,
                     'name'                      => 'level2',
                     'signatories'               => 1,
                    ],
                    ['level'                     => 3,
                     'name'                      => 'level3',
                     'signatories'               => 0,
                    ],
                ],
            ],
            [
                'name' => 'Between 100 and 500',
                'min_value' => 10001,
                'max_value' => 50000,
                'levels' => [
                    ['level'                     => 1,
                     'name'                      => 'level1',
                     'signatories'               => 2,
                    ],
                    ['level'                     => 2,
                     'name'                      => 'level2',
                     'signatories'               => 1,
                    ],
                    ['level'                     => 3,
                     'name'                      => 'level3',
                     'signatories'               => 0,
                    ],
                ],
            ],
            [
                'name' => 'Over 500',
                'min_value' => 50001,
                'max_value' => null,
                'levels' => [
                    ['level'                     => 1,
                     'name'                      => 'level1',
                     'signatories'               => 2,
                    ],
                    ['level'                     => 2,
                     'name'                      => 'level2',
                     'signatories'               => 1,
                    ],
                    ['level'                     => 3,
                     'name'                      => 'level3',
                     'signatories'               => 1,
                    ],
                ],
            ],
        ],

    ],
];