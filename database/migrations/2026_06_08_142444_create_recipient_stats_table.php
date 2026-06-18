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
        Schema::create('vw_recipient_stats', function (Blueprint $table) {
            $table->id();
            $table->string('recipient', 255);
            $table->string('domain', 255);
            $table->date('date');
            $table->integer('total_incoming')->default(0);
            $table->integer('spam_count')->default(0);
            $table->integer('virus_count')->default(0);
            $table->float('avg_spam_score')->default(0);
            $table->float('max_spam_score')->default(0);
            $table->integer('quarantined_count')->default(0);
            $table->timestamps();
            
            $table->unique(['recipient', 'date']);
            $table->index(['domain', 'date']);
            $table->index(['date', 'spam_count']);
            $table->index('recipient');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vw_recipient_stats');
    }
};
