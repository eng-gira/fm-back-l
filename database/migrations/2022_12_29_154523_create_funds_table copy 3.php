<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('funds', function (Blueprint $table) {
            $table->id();
            $table->string('fundName');
            $table->double('fundPercentage');
            $table->double('balance')->default(0);
            $table->string('lastDeposit')->nullable();
            $table->string('lastWithdrawal')->nullable();
            $table->text('notes')->nullable();
            $table->string('size')->default('Open');
            $table->double('totalDeposits')->default(0);
            $table->double('totalWithdrawals')->default(0);
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
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
        Schema::dropIfExists('funds');
    }
};
