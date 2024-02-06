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
        Schema::create('chat_room', function (Blueprint $table) {
            $table->id();
            $table->integer('status')->default(1);
            $table->unsignedBigInteger('freelancer_id');
            $table->unsignedBigInteger('client_id');
            $table->timestamps();
            $table->foreign('freelancer_id')->references('id')->on('freelancer')->onDelete('cascade');
            $table->foreign('client_id')->references('id')->on('client')->onDelete('cascade');
        });
        Schema::create('message', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('room_id');
            $table->string('content');
            $table->string('type_msg');
            $table->integer('status')->default(1);
            $table->timestamps();
            $table->foreign('room_id')->references('id')->on('chat_room')->onDelete('cascade');
        });
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin');
        Schema::dropIfExists('client');
        Schema::dropIfExists('client_job');
        Schema::dropIfExists('freelancer');
    }
};
