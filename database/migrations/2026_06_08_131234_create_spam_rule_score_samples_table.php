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
		Schema::create('vw_spam_rule_score_samples', function (Blueprint $table) {
			$table->id();
			$table->string('rule_name')->index();
			$table->float('score');
			$table->timestamp('received_at')->index();

			$table->index(['rule_name', 'received_at']);
		});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vw_spam_rule_score_samples');
    }
};
