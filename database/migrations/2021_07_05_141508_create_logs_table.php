<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('logs', function (Blueprint $table) {
            $table->id();
            $table->text('mobile')->nullable();
            $table->text('account_number')->nullable();
            $table->text('message')->nullable();
            $table->text('transaction_type')->nullable();
            $table->text('millipede_error')->nullable();
            $table->text('response')->nullable();
            $table->string('http_method')->nullable();
            $table->string('sku')->nullable();
            $table->string('status_code')->nullable();
            $table->dateTime('date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('logs');
    }
}
