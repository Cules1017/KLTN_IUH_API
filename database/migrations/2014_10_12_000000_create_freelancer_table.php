<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('freelancer', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('fullname')->nullable();
            $table->string('phone_num')->nullable();
            $table->string('address')->nullable();
            $table->string('position')->nullable();
            $table->integer('sex')->nullable();
            $table->string('intro')->nullable();
            $table->string('avatar_url')->nullable();
            $table->float('expected_salary')->nullable();
            $table->float('available_proposal')->nullable();
            $table->integer('status')->default(1);
            $table->string('google_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('freelancer');
    }
};
