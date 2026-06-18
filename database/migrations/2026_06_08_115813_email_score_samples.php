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
        Schema::create('vw_email_score_samples', function (Blueprint $table) {
            $table->id();
            $table->string('action')->index();
            $table->float('score');
            $table->float('required_score');
            $table->boolean('has_virus')->default(false);
            $table->timestamp('received_at')->index();

            $table->index(['action', 'received_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vw_email_score_samples');
    }
};
