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
        Schema::create('vw_domain_stats', function (Blueprint $table) {
            $table->id();
            $table->string('domain', 255);
            $table->date('date');
            $table->enum('action', ['reject', 'discard', 'quarantine', 'deliver'])
                  ->default('deliver');
            $table->integer('incoming_count')->default(0);
            $table->integer('spam_count')->default(0);
            $table->integer('virus_count')->default(0);
            $table->float('avg_spam_score')->default(0);
            $table->float('max_spam_score')->default(0);
            $table->bigInteger('total_size_bytes')->default(0);
            $table->timestamps();
            
            $table->unique(['domain', 'date', 'action']);
            $table->index(['domain', 'date']);
            $table->index(['date', 'spam_count']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vw_domain_stats');
    }
};
