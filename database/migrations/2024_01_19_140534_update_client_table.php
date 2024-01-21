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
        Schema::table('client', function (Blueprint $table) {
            $table->dropColumn('fullname');
        });
        Schema::table('client', function (Blueprint $table) {
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            // Hoặc bạn có thể sử dụng 'before' để đặt cột mới trước cột khác
            // $table->string('new_column')->before('other_column');
        });

        Schema::table('freelancer', function (Blueprint $table) {
            $table->dropColumn('fullname');
        });
        Schema::table('freelancer', function (Blueprint $table) {
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            // Hoặc bạn có thể sử dụng 'before' để đặt cột mới trước cột khác
            // $table->string('new_column')->before('other_column');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
