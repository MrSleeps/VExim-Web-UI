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
        Schema::create(config('fin-mail.table_names.versions') ?? 'email_template_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(EmailTemplate::class)
                ->constrained(config('fin-mail.table_names.templates') ?? 'email_templates')
                ->cascadeOnDelete();
            $table->unsignedInteger('version');
            $table->json('subject');
            $table->json('preheader')->nullable();
            $table->json('body');
            $table->foreignId('created_by')->nullable()->constrained('users_web')->nullOnDelete();
            $table->timestamps();

            $table->unique(['email_template_id', 'version']);
            $table->index(['email_template_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists(config('fin-mail.table_names.versions') ?? 'email_template_versions');
    }
};
