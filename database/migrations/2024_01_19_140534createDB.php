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
        //Bảng độc lập
        Schema::create('admin', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('email')->unique();
            $table->integer('position')->nullable();
            $table->integer('status')->default(1);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('client', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('fullname')->nullable();
            $table->string('phone_num')->nullable();
            $table->string('address')->nullable();
            $table->string('company_name')->nullable();
            $table->string('introduce')->nullable();
            $table->string('avatar_url')->nullable();
            $table->integer('status')->default(1);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
        Schema::create('freelancer', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('date_of_birth')->nullable();
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
        Schema::create('policy_config', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('desc');
            $table->string('value');
            $table->timestamps();
        });
        Schema::create('systerm_config', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('desc');
            $table->string('value');
            $table->timestamps();
        });
        Schema::create('skills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('desc');
            $table->timestamps();
        });
        Schema::create('jobs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->string('title');
            $table->string('desc');
            $table->string('content');
            $table->string('thumbnail');
            $table->float('bids');
            $table->integer('status');
            $table->integer('min_proposals');
            $table->dateTime('deadline');
            $table->timestamps();
            // Ràng buộc khóa ngoại
            $table->foreign('client_id')->references('id')->on('client')->onDelete('cascade');
        });
        Schema::create('skill_job_map', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('skill_id');
            $table->integer('skill_points');
            $table->timestamps();
             // Ràng buộc khóa ngoại tới bảng jobs
             $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');

             // Ràng buộc khóa ngoại tới bảng skills
             $table->foreign('skill_id')->references('id')->on('skills')->onDelete('cascade');
        });
        Schema::create('skill_freelancer_map', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('freelancer_id');
            $table->unsignedBigInteger('skill_id');
            $table->integer('skill_points');
            $table->timestamps();
             // Ràng buộc khóa ngoại tới bảng freelancer
             $table->foreign('freelancer_id')->references('id')->on('freelancer')->onDelete('cascade');

             // Ràng buộc khóa ngoại tới bảng skills
             $table->foreign('skill_id')->references('id')->on('skills')->onDelete('cascade');
        });
        Schema::create('freelancer_job', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('freelancer_id');
            $table->integer('status');
            $table->timestamps();

             // Ràng buộc khóa ngoại tới bảng jobs
             $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');

             // Ràng buộc khóa ngoại tới bảng skills
             $table->foreign('freelancer_id')->references('id')->on('freelancer')->onDelete('cascade');
        });
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->string('name');
            $table->string('desc');
            $table->integer('status');
            $table->integer('confirm_status');
            $table->dateTime('deadline');
            $table->timestamps();

            // Ràng buộc khóa ngoại tới bảng jobs
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
        });
        Schema::create('rate', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('freelancer_id');
            $table->integer('rate_value');
            $table->integer('status');
            $table->timestamps();

             // Ràng buộc khóa ngoại tới bảng jobs
             $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');

             // Ràng buộc khóa ngoại tới bảng skills
             $table->foreign('freelancer_id')->references('id')->on('freelancer')->onDelete('cascade');
        });
        Schema::create('invite', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('freelancer_id');
            $table->integer('status');
            $table->timestamps();

             // Ràng buộc khóa ngoại tới bảng jobs
             $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');

              // Ràng buộc khóa ngoại tới bảng jobs
              $table->foreign('client_id')->references('id')->on('client')->onDelete('cascade');

             // Ràng buộc khóa ngoại tới bảng skills
             $table->foreign('freelancer_id')->references('id')->on('freelancer')->onDelete('cascade');
        });
        Schema::create('reports', function (Blueprint $table) {
            $table->id();
            $table->enum('type_id',[1,2]);
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('freelancer_id');
            $table->string('content');
            $table->string('results');
            $table->integer('status');
            $table->timestamps();

            // Ràng buộc khóa ngoại tới bảng jobs
            $table->foreign('client_id')->references('id')->on('client')->onDelete('cascade');

            // Ràng buộc khóa ngoại tới bảng skills
            $table->foreign('freelancer_id')->references('id')->on('freelancer')->onDelete('cascade');
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('type_user');
            $table->integer('noti_type');
            $table->string('title');
            $table->string('message');
            $table->dateTime('time_push');
            $table->string('image');
            $table->string('linkable');
            $table->integer('is_read');
            $table->timestamps();
        });
        Schema::create('contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('freelancer_id');
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->float('total_amount');
            $table->integer('status');
            $table->timestamps();
            // Ràng buộc khóa ngoại tới bảng jobs
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');

            // Ràng buộc khóa ngoại tới bảng skills
            $table->foreign('freelancer_id')->references('id')->on('freelancer')->onDelete('cascade');
        });
        Schema::create('hash_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->string('hash');
            $table->timestamps();
            // Ràng buộc khóa ngoại tới bảng jobs
            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
        });
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('admin_id');
            $table->float('amount');
            $table->integer('status');
            $table->timestamps();

            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('cascade');
            $table->foreign('admin_id')->references('id')->on('admin')->onDelete('cascade');
        });
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('type_user');
            $table->integer('type_document');
            $table->timestamps();
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
