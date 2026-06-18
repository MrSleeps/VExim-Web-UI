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
        Schema::create('vw_email_stats', function (Blueprint $table) {
            $table->id();
            $table->timestamp('hour')->index();
            $table->string('action')->index();        // no action, greylist, add header, rewrite subject, soft reject, reject, discard
            $table->unsignedInteger('count')->default(0);
            $table->boolean('has_virus')->default(false);
            $table->timestamps();

            $table->unique(['hour', 'action', 'has_virus']);
            $table->index(['hour', 'action']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vw_email_stats');
    }
};
