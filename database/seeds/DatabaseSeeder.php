<?php

use App\User;
use App\WorkflowDefinition;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{

    protected $tables = ['approvals', 'orders', 'password_resets', 'revisions',
                         'users', 'workflow_definitions', 'workflows'
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
        factory(WorkflowDefinition::class, 1)->create(['name' => 'basic']);
        factory(WorkflowDefinition::class, 1)->create(['name' => 'standard']);
        factory(WorkflowDefinition::class, 1)->create(['name' => 'staged']);
        //factory(Order::class, 10)->create(['user_id' => $user->id]);
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
