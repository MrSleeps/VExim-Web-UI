<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add the category column
        Schema::table('vw_settings', function (Blueprint $table) {
            $table->string('category')->nullable()->after('type');
        });

        // Define the category mappings
        $categoryMappings = [
            // Servers category
            'imap_quota_server' => 'servers',
            'check_quota_via_imap' => 'servers',
            'default_imap_server' => 'servers',
            'default_imap_port' => 'servers',
            'default_smtp_server' => 'servers',
            'default_smtp_port' => 'servers',
            'use_domain_for_servers' => 'servers',
            'custom_imap_host' => 'servers',
            'custom_smtp_host' => 'servers',
            'crypt_scheme' => 'servers',
            'default_uid' => 'servers',
            'default_gid' => 'servers',
            'mail_root' => 'servers',
            'check_mail_root_exists' => 'servers',
            
            // Accounts category
            'default_max_domains' => 'accounts',
            'default_max_alias_domains' => 'accounts',
            'default_max_email_accounts' => 'accounts',
            'default_max_alias_accounts' => 'accounts',
            'default_max_quota' => 'accounts',
            
            // Domain category
            'default_av_setting' => 'domain',
            'default_spam_setting' => 'domain',
            'default_pipe_setting' => 'domain',
            'default_blocklist_setting' => 'domain',
            'default_whitelist_setting' => 'domain',
            'default_max_message_size' => 'domain',
            'allow_domainadmin_uid_gid' => 'domain',
            
            // Website category
            'allow_user_login' => 'website',
            'domain_guess_enabled' => 'website',
            'domain_guess_left_trim' => 'website',
            'siteadmin_manage_domains' => 'website',
            
            // Mailman category
            'mailman_root' => 'mailman',
            
            // Spam category
            'spam_tag_threshold' => 'spam',
            'spam_refuse_threshold' => 'spam',
            
            // Emailmessages category
            'send_domain_welcome_email' => 'emailmessages',
            'send_new_user_welcome_email' => 'emailmessages',
            'send_new_email_welcome_email' => 'emailmessages',
            'welcome_message' => 'emailmessages',
            'welcome_new_domain_message' => 'emailmessages',
        ];

        // Update each record with its category
	DB::transaction(function () use ($categoryMappings) {
		foreach ($categoryMappings as $key => $category) {
			DB::table('vw_settings')
			->where('key', $key)
			->update(['category' => $category]);
		}
	});
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('vw_settings', function (Blueprint $table) {
            $table->dropColumn('category');
        });
    }
};
