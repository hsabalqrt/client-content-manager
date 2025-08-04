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
        Schema::table('users', function (Blueprint $table) {
            $table->enum('role', ['manager', 'content_writer', 'designer', 'hr'])->default('content_writer')->after('password');
            $table->unsignedBigInteger('department_id')->nullable()->after('role');
            $table->boolean('is_active')->default(true)->after('department_id');
            $table->string('avatar')->nullable()->after('is_active');
            $table->string('phone')->nullable()->after('avatar');
            $table->date('hire_date')->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'department_id', 'is_active', 'avatar', 'phone', 'hire_date']);
        });
    }
};
