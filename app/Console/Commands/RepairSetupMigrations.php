<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RepairSetupMigrations extends Command
{
    protected $signature = 'vw:repair-setup-migrations';

    protected $description = 'Reconcile partially completed setup migrations before resuming installation';

    public function handle(): int
    {
        if (! Schema::hasTable('migrations')) {
            return self::SUCCESS;
        }

        $definitions = [
            '2026_05_21_143400_create_email_themes_table' => [
                'table' => config('fin-mail.table_names.themes', 'email_themes'),
                'columns' => ['id', 'name', 'colors', 'is_default', 'created_at', 'updated_at'],
            ],
            '2026_05_21_143401_create_email_templates_table' => [
                'table' => config('fin-mail.table_names.templates', 'email_templates'),
                'columns' => [
                    'id', 'key', 'name', 'category', 'tags', 'subject', 'preheader', 'body',
                    'view_path', 'from', 'email_theme_id', 'is_active', 'is_locked',
                    'created_at', 'updated_at', 'deleted_at',
                ],
            ],
            '2026_05_21_143402_create_email_template_versions_table' => [
                'table' => config('fin-mail.table_names.versions', 'email_template_versions'),
                'columns' => [
                    'id', 'email_template_id', 'version', 'subject', 'preheader', 'body',
                    'created_by', 'created_at', 'updated_at',
                ],
            ],
            '2026_05_21_143403_create_sent_emails_table' => [
                'table' => config('fin-mail.table_names.sent', 'sent_emails'),
                'columns' => [
                    'id', 'email_template_id', 'sender', 'to', 'cc', 'bcc', 'subject',
                    'rendered_body', 'attachments', 'status', 'sent_at', 'metadata',
                    'sent_by', 'sendable_type', 'sendable_id', 'created_at', 'updated_at',
                ],
            ],
            '2026_05_21_143404_add_reply_to_on_email_templates_table' => [
                'table' => config('fin-mail.table_names.templates', 'email_templates'),
                'columns' => ['reply_to'],
            ],
        ];

        $batch = ((int) DB::table('migrations')->max('batch')) + 1;
        $reconciled = 0;

        foreach ($definitions as $migration => $definition) {
            if (DB::table('migrations')->where('migration', $migration)->exists()) {
                continue;
            }

            if (! Schema::hasTable($definition['table'])) {
                continue;
            }

            if (! Schema::hasColumns($definition['table'], $definition['columns'])) {
                $this->components->error(
                    "Cannot reconcile {$migration}: {$definition['table']} exists but does not match the expected FinMail schema."
                );

                return self::FAILURE;
            }

            DB::table('migrations')->insert([
                'migration' => $migration,
                'batch' => $batch,
            ]);

            $this->components->info(
                "Reconciled {$migration}; {$definition['table']} already exists with the expected schema."
            );

            $reconciled++;
        }

        if ($reconciled === 0) {
            $this->components->info('No partial FinMail migrations needed reconciliation.');
        }

        return self::SUCCESS;
    }
}
