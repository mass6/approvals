<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateTransitionEventsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transition_events', function (Blueprint $table) {
            $table->increments('id');
            $table->string('event')->nullable();
            $table->string('from')->nullable();
            $table->string('to');
            $table->string('user_id')->nullable();
            $table->uuid('stateful_id')->nullable();
            $table->string('stateful_type')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('transition_events');
    }
}
