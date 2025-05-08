<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id')->unique();
            $table->decimal('amount', 10, 2);
            $table->decimal('stripe_fee', 10, 2)->nullable(0);
            $table->decimal('platform_fee', 10, 2)->nullable();
            $table->boolean('captured')->default(false);
            $table->string('customer_id')->nullable();
            $table->string('connect_account_id')->nullable();
            $table->string('connect_account_name')->nullable();
            $table->string('connect_account_email')->nullable();
            $table->string('session_url')->nullable();
            $table->string('status')->nullable();
            $table->decimal('refunded_amount', 10, 2)->nullable();
            $table->timestamp('refunded_at')->nullable();
            $table->text('metadata')->nullable();
            $table->text('json_data')->nullable();
            $table->timestamp('transaction_date')->nullable();
            $table->decimal('base_price', 10, 2)->nullable();
            $table->string('session_instance_id')->nullable();
            $table->string('session_owner_id')->nullable();
            $table->string('session_owner_name')->nullable();
            $table->string('session_for')->nullable();
            $table->string('userId')->nullable();
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
        Schema::dropIfExists('transactions');
    }
}
