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
        Schema::create('quarantined_emails', function (Blueprint $table) {
            $table->id();

            // Envelope
            $table->string('queue_id')->nullable()->index();
            $table->string('message_id')->nullable()->index();
            $table->string('mail_from')->nullable();
            $table->string('mime_from')->nullable();
            $table->json('rcpt_to');                          // all envelope recipients
            $table->string('mime_to')->nullable();
            $table->string('subject')->nullable();

            // Rspamd verdict
            $table->string('action')->index();                // reject, discard, etc.
            $table->float('spam_score')->index();
            $table->float('required_score')->default(5.0);
            $table->boolean('has_virus')->default(false)->index();
            $table->json('symbols')->nullable();

            // Connection
            $table->string('ip_address')->nullable()->index();
            $table->string('helo')->nullable();
            $table->unsignedBigInteger('size')->default(0);

            // Raw storage
            $table->longText('raw_content')->nullable();
            $table->boolean('raw_stored')->default(false);

            // Quarantine management
            $table->string('status')->default('quarantined')->index();
            // quarantined | released | deleted | expired
            $table->timestamp('received_at')->index();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('expires_at')->nullable()->index();
            $table->foreignId('released_by')->nullable()->constrained('users_web');
            $table->string('release_method')->nullable();
            // smtp_forward | imap_inject | manual
            $table->text('release_notes')->nullable();

            $table->timestamps();

            $table->index(['mail_from', 'received_at']);
            $table->index(['status', 'received_at']);
            $table->index(['has_virus', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quarantined_emails');
    }
};
