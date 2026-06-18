<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vw_domain_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users_web')->onDelete('cascade');
            $table->unsignedInteger('domain_id');  // Matches domains.domain_id type
            $table->string('role')->default('domain_admin');
            $table->timestamps();
            
            // Foreign key constraint to your domains table
            $table->foreign('domain_id')
                  ->references('domain_id')
                  ->on('domains')
                  ->onDelete('cascade');
            
            // Prevent duplicate assignments
            $table->unique(['user_id', 'domain_id']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('vw_domain_user');
    }
};
