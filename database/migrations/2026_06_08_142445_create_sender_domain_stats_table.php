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
        Schema::create('vw_sender_domain_stats', function (Blueprint $table) {
            $table->id();
            $table->string('sender_domain', 255);
            $table->date('date');
            $table->integer('total_emails')->default(0);
            $table->integer('spam_count')->default(0);
            $table->integer('virus_count')->default(0);
            $table->float('avg_spam_score')->default(0);
            $table->float('max_spam_score')->default(0);
            $table->json('top_recipient_domains')->nullable();
            $table->timestamps();
            
            $table->unique(['sender_domain', 'date']);
            $table->index(['date', 'spam_count']);
            $table->index('sender_domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vw_sender_domain_stats');
    }
};
