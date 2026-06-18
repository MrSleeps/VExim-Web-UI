<?php

declare(strict_types=1);

use FinityLabs\FinMail\Models\EmailTemplate;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create(config('fin-mail.table_names.sent') ?? 'sent_emails', function (Blueprint $table) {
            $table->id();

            // Template reference
            $table->foreignIdFor(EmailTemplate::class)
                ->nullable()
                ->constrained(config('fin-mail.table_names.templates') ?? 'email_templates')
                ->nullOnDelete();

            // Sender & Recipients
            $table->string('sender', 255);
            $table->json('to');
            $table->json('cc')->nullable();
            $table->json('bcc')->nullable();

            // Content snapshot
            $table->string('subject', 255);
            $table->longText('rendered_body')->nullable();
            $table->json('attachments')->nullable();

            // Status tracking
            $table->unsignedTinyInteger('status')->default(2)->index();
            $table->dateTime('sent_at')->nullable()->index();
            $table->json('metadata')->nullable();

            // Who sent it
            $table->foreignId('sent_by')->nullable()->constrained('users_web')->nullOnDelete();

            // Polymorphic: what model this email relates to
            $table->nullableMorphs('sendable');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('fin-mail.table_names.sent') ?? 'sent_emails');
    }
};
