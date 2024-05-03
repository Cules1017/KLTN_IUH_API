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
        Schema::create('majors', function (Blueprint $table) {
            $table->id();
            $table->string('title_major');
            $table->timestamps();
        });
        Schema::create('admin', function (Blueprint $table) {
            $table->id();
            $table->string('username');
            $table->string('email')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone_num')->nullable();
            $table->string('address')->nullable();
            $table->integer('sex')->nullable();
            $table->timestamp('date_of_birth')->nullable();
            $table->string('avatar_url')->nullable();
            $table->integer('position')->nullable();
            $table->integer('status')->default(1);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('google_id')->nullable();
            $table->string('otp')->nullable();
            $table->dateTime('otp_exp')->nullable();
            $table->rememberToken();
            $table->timestamps();

        });
        Schema::create('client', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone_num')->nullable();
            $table->string('address')->nullable();
            $table->integer('sex')->nullable();
            $table->timestamp('date_of_birth')->nullable();
            $table->string('company_name')->nullable();
            $table->text('introduce')->nullable();
            $table->string('avatar_url')->nullable();
            $table->string('citizen_identification_url')->nullable();
            $table->string('citizen_identification_id')->nullable();
            $table->enum('is_completed_profile',[0,1])->default(0);
            $table->integer('status')->default(1);
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('google_id')->nullable();
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
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->string('phone_num')->nullable();
            $table->string('address')->nullable();
            $table->string('position')->nullable();
            $table->integer('sex')->nullable();
            $table->text('intro')->nullable();
            $table->string('avatar_url')->nullable();
            $table->integer('status')->default(1);
            $table->string('citizen_identification_url')->nullable();
            $table->string('citizen_identification_id')->nullable();
            $table->enum('is_completed_profile',[0,1])->default(0);
            $table->string('google_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
        
        Schema::create('systerm_config', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->string('desc');
            $table->string('value');
            $table->unsignedBigInteger('admin_id');
            $table->timestamps();
            $table->foreign('admin_id')->references('id')->on('admin')->onDelete('cascade');
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
            $table->text('content');
            $table->string('content_file');
            $table->string('thumbnail');
            $table->float('bids');
            $table->integer('status');
            $table->dateTime('deadline');
            $table->timestamps();
            // Ràng buộc khóa ngoại
            $table->foreign('client_id')->references('id')->on('client')->onDelete('cascade');
        });
        Schema::create('skill_job_map', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('skill_id');
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
            $table->timestamps();
             // Ràng buộc khóa ngoại tới bảng freelancer
             $table->foreign('freelancer_id')->references('id')->on('freelancer')->onDelete('cascade');

             // Ràng buộc khóa ngoại tới bảng skills
             $table->foreign('skill_id')->references('id')->on('skills')->onDelete('cascade');
        });
        Schema::create('major_freelancer_map', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('freelancer_id');
            $table->unsignedBigInteger('major_id');
            $table->timestamps();
             // Ràng buộc khóa ngoại tới bảng freelancer
             $table->foreign('freelancer_id')->references('id')->on('freelancer')->onDelete('cascade');

             // Ràng buộc khóa ngoại tới bảng skills
             $table->foreign('major_id')->references('id')->on('majors')->onDelete('cascade');
        });
        Schema::create('candidate_apply_job', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('freelancer_id');
            $table->string('attachment_url')->nullable();
            $table->text('cover_letter')->nullable();
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
            $table->integer('priority')->default(0);
            $table->integer('status')->default(0);;
            $table->integer('confirm_status')->default(0);
            $table->dateTime('deadline');
            $table->timestamps();

            // Ràng buộc khóa ngoại tới bảng jobs
            $table->foreign('job_id')->references('id')->on('jobs')->onDelete('cascade');
        });
        Schema::create('invite', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('client_id');
            $table->unsignedBigInteger('freelancer_id');
            $table->string('title')->nullable();
            $table->text('mail_invite')->nullable();
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
            $table->string('content_file');
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
            $table->string('type_user');
            $table->string('title');
            $table->string('message');
            $table->string('image');
            $table->string('linkable');
            $table->integer('is_read');
            $table->timestamps();
        });
        
        Schema::create('feedbacks', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->string('type_user');
            $table->integer('rate');
            $table->text('comment');
            $table->integer('status');
            $table->timestamps();
             // Ràng buộc khóa ngoại tới bảng skills
            // $table->foreign('client_id')->references('id')->on('client')->onDelete('cascade');
        });
        Schema::create('my_list', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('freelancer_id');
            $table->integer('user_id');
            $table->string('type_user');
            $table->integer('status');
            $table->timestamps();
            $table->foreign('freelancer_id')->references('id')->on('freelancer')->onDelete('cascade');
        });
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('task_id');
            $table->enum('type',['text','file']);
            $table->text('content');
            $table->integer('user_id');
            $table->string('type_user');
            $table->timestamps();
            $table->foreign('task_id')->references('id')->on('tasks')->onDelete('cascade');
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
