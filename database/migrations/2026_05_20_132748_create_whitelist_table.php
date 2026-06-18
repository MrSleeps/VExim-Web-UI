<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('whitelist_senders', function (Blueprint $table) {
            $table->id();

            // 0 = global whitelist, otherwise maps to vexim domains.domain_id
            $table->unsignedInteger('domain_id')->default(0);

            // NULL = domain-wide rule, otherwise per-user (localpart)
            $table->string('localpart', 64)->nullable();

            // sender email address to whitelist
            $table->string('sender', 255);

            $table->string('comment')->nullable();

            $table->timestamps();

            // Performance indexes for Exim lookup patterns
            $table->index('domain_id');
            $table->index(['domain_id', 'localpart']);
            $table->index('sender');

            // Critical composite index for your ACL queries
            $table->index(['domain_id', 'localpart', 'sender']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('whitelist_senders');
    }
};
