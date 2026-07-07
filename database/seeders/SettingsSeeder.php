<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Setting;

class SettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $settings = [
            // IMAP & Quota
            ['key' => 'imap_quota_server', 'value' => '{mail.CHANGE.com:143/imap/notls}', 'type' => 'string', 'description' => 'IMAP server used to check user quotas','category'=>'servers'],
            ['key' => 'check_quota_via_imap', 'value' => '0', 'type' => 'integer', 'description' => 'Whether to check quota via IMAP','category'=>'servers'],
            ['key' => 'default_imap_server', 'value' => 'imap.your.domain', 'type' => 'string', 'description' => 'IMAP server clients connect to','category'=>'servers'],
            ['key' => 'default_imap_port', 'value' => '993', 'type' => 'integer', 'description' => 'IMAP server port clients connect to','category'=>'servers'],
            ['key' => 'default_smtp_server', 'value' => 'smtp.your.domain', 'type' => 'string', 'description' => 'SMTP server clients connect to','category'=>'servers'],
            ['key' => 'default_smtp_port', 'value' => '465', 'type' => 'integer', 'description' => 'SMTP server port clients connect to','category'=>'servers'],
            ['key' => 'use_domain_for_servers', 'value' => '0', 'type' => 'integer', 'description' => 'Use users domain for server hostnames','category'=>'servers'],
            ['key' => 'custom_imap_host', 'value' => '', 'type' => 'string', 'description' => 'Custom IMAP hostname (ie mail.)','category'=>'servers'],
            ['key' => 'custom_smtp_host', 'value' => '', 'type' => 'string', 'description' => 'Custom SMTP hostname (ie mail.)','category'=>'servers'],
            
            // Default domain admin account settings
            ['key' => 'default_max_domains', 'value' => '0', 'type' => 'integer', 'description' => 'Maximum number of domains a domain admin can have','category'=>'accounts'],
            ['key' => 'default_max_alias_domains', 'value' => '0', 'type' => 'integer', 'description' => 'Maximum number of alias domains a domain admin can have','category'=>'accounts'],
            ['key' => 'default_max_email_accounts', 'value' => '0', 'type' => 'integer', 'description' => 'Maximum number of email accounts a domain admin can have','category'=>'accounts'],
            ['key' => 'default_max_alias_accounts', 'value' => '0', 'type' => 'integer', 'description' => 'Maximum number of alias accounts a domain admin can have','category'=>'accounts'],
            ['key' => 'default_max_quota', 'value' => '0', 'type' => 'integer', 'description' => 'Maximum quota for all accounts','category'=>'accounts'],

            // Default domain settings (AV, Spam, Pipe, Blocklist)
            ['key' => 'default_av_setting', 'value' => '1', 'type' => 'boolean', 'description' => 'Enable AV scanning by default when adding a new domain','category'=>'domain'],
            ['key' => 'default_spam_setting', 'value' => '1', 'type' => 'boolean', 'description' => 'Enable SpamAssassin by default when adding a new domain','category'=>'domain'],
            ['key' => 'default_pipe_setting', 'value' => '0', 'type' => 'boolean', 'description' => 'Enable pipe support by default when adding a new domain','category'=>'domain'],
            ['key' => 'default_blocklist_setting', 'value' => '1', 'type' => 'boolean', 'description' => 'Enable blocklist by default when adding a new domain','category'=>'domain'],
            ['key' => 'default_whitelist_setting', 'value' => '1', 'type' => 'boolean', 'description' => 'Enable whitelist by default when adding a new domain','category'=>'domain'],
            ['key' => 'default_max_message_size', 'value' => '0', 'type' => 'integer', 'description' => 'Default max message size when adding a new domain (0 for no limit)','category'=>'domain'],
            
            // Login & Access
            ['key' => 'allow_user_login', 'value' => '1', 'type' => 'integer', 'description' => 'Allow non-admin users to login (0 = admins only)','category'=>'website'],
            
            // Security & Passwords
            ['key' => 'crypt_scheme', 'value' => 'sha512', 'type' => 'string', 'description' => 'Password hash scheme (sha512/bcrypt)','category'=>'servers'],
            
            // Domain Guessing
            ['key' => 'domain_guess_enabled', 'value' => '0', 'type' => 'integer', 'description' => 'Guess domain from hostname','category'=>'website'],
            ['key' => 'domain_guess_left_trim', 'value' => 'mail|vexim', 'type' => 'string', 'description' => 'String to trim left from hostname for domain guess','category'=>'website'],
            
            // UID/GID
            ['key' => 'default_uid', 'value' => env('VEXIM_UID', '1000'), 'type' => 'integer', 'description' => 'Default UID for new domains (numeric)','category'=>'servers'],
            ['key' => 'default_gid', 'value' => env('VEXIM_GID', '1000'), 'type' => 'integer', 'description' => 'Default GID for new domains (numeric)','category'=>'servers'],
            ['key' => 'allow_domainadmin_uid_gid', 'value' => env('VEXIM_SYSADMIN_SET_GUID', true), 'type' => 'integer', 'description' => 'Allow postmasters to define their own UID/GID','category'=>'domain'],
            ['key' => 'siteadmin_manage_domains', 'value' => '1', 'type' => 'integer', 'description' => 'Allow siteadmin user to manage domains','category'=>'website'],
            
            // Mail Storage
            ['key' => 'mail_root', 'value' => env('VEXIM_MAIL_ROOT','/var/vmail/'), 'type' => 'string', 'description' => 'Location of mailstore for new domains','category'=>'servers'],
            ['key' => 'check_mail_root_exists', 'value' => '1', 'type' => 'integer', 'description' => 'Check if mailstore exists when creating domain','category'=>'servers',
            
            // SpamAssassin
            ['key' => 'spam_tag_threshold', 'value' => '2', 'type' => 'string', 'description' => 'Default SpamAssassin/RSpamd tagging threshold','category'=>'spam'],
            ['key' => 'spam_refuse_threshold', 'value' => '5', 'type' => 'string', 'description' => 'Default SpamAssassin/RSpamd refuse/drop threshold','category'=>'spam'],
            
            // Welcome Messages
            ['key' => 'send_domain_welcome_email', 'value' => "1", 'type' => 'integer', 'description' => 'Send welcome message to new Email accounts','category'=>'emailmessages'],
            ['key' => 'send_new_user_welcome_email', 'value' => "1", 'type' => 'integer', 'description' => 'Send welcome message to new website user accounts','category'=>'emailmessages'],
            ['key' => 'send_new_email_welcome_email', 'value' => "1", 'type' => 'integer', 'description' => 'Send welcome message to new email accounts','category'=>'emailmessages'],
            ['key' => 'welcome_message', 'value' => "Welcome, {realname} !\n\nYour new E-mail account is all ready for you.\n\nHere are some settings you might find useful:\n\nUsername: {localpart}@{domain}\nPOP3 server: mail.{domain}\nSMTP server: mail.{domain}\n", 'type' => 'string', 'description' => 'Welcome message sent to new POP/IMAP accounts','category'=>'emailmessages'],
            
            ['key' => 'welcome_new_domain_message', 'value' => "Welcome, and thank you for registering your e-mail domain\n{domain} with us.\n\nIf you have any questions, please\ndon't hesitate to ask your account representative.\n", 'type' => 'string', 'description' => 'Welcome message sent to new domains','category'=>'emailmessages'],
            
        ];
        
        foreach ($settings as $setting) {
            Setting::firstOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'type' => $setting['type'],
                    'description' => $setting['description']
                ]
            );
        }
    }
}