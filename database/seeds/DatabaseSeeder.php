<?php

use App\BusinessRule;
use App\User;
use App\ValueRule;
use App\WorkflowDefinition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{

    protected $tables = ['approvals', 'business_rules', 'orders', 'password_resets', 'revisions', 'transition_events',
                         'users', 'value_rules', 'workflow_definitions', 'workflows'
    ];
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->cleanDatabase();

        factory(User::class, 10)->create();
        $this->call(WorkflowDefinitionSeeder::class);
        $this->call(BusinessRulesSeeder::class);
    }

    /**
     *
     */
    private function cleanDatabase()
    {
        $databaseType = getenv('DB_TYPE');
        $this->disableForeignKeyConstraints($databaseType);
        foreach ($this->tables as $table)
        {
            DB::table($table)->truncate();
        }
        $this->enableForeignKeyConstraints($databaseType);
    }

    private function disableForeignKeyConstraints($databaseType)
    {
        $databaseType === 'sqlite'
            ? DB::statement("PRAGMA foreign_keys = OFF")
            : DB::statement("SET foreign_key_checks = 0");
    }
    private function enableForeignKeyConstraints($databaseType)
    {
        $databaseType === 'sqlite'
            ? DB::statement("PRAGMA foreign_keys = ON")
            : DB::statement("SET foreign_key_checks = 1");
    }
}
