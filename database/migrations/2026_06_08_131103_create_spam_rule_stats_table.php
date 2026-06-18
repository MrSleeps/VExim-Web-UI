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
		Schema::create('vw_spam_rule_stats', function (Blueprint $table) {
			$table->id();
			$table->string('rule_name')->index();
			$table->date('date')->index();
			$table->unsignedInteger('hit_count')->default(0);
			$table->timestamps();

			$table->unique(['rule_name', 'date']);
		});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('vw_spam_rule_stats');
    }
};
