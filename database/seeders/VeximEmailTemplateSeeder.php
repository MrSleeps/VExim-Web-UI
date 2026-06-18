<?php

declare(strict_types=1);

namespace Database\Seeders;

use FinityLabs\FinMail\Models\EmailTemplate;
use FinityLabs\FinMail\Models\EmailTheme;
use FinityLabs\FinMail\Settings\GeneralSettings;
use Illuminate\Database\Seeder;

class VeximEmailTemplateSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedDefaultTheme();
        $this->seedTemplates();
    }

    protected function seedDefaultTheme(): void
    {
        EmailTheme::firstOrCreate(
            ['name' => 'Default'],
            [
                'colors' => EmailTheme::defaultColors(),
                'is_default' => true,
            ]
        );
    }

    protected function seedTemplates(): void
    {
        $theme = EmailTheme::where('is_default', true)->first();
        $configuredLocales = $this->getConfiguredLocales();
        $templates = $this->getTemplateDefinitions();

        foreach ($templates as $data) {
            $filtered = $data;

            foreach (['name', 'subject', 'preheader', 'body'] as $field) {
                if (isset($data[$field])) {
                    $filtered[$field] = array_intersect_key(
                        $data[$field],
                        array_flip($configuredLocales)
                    );

                    // Fallback: ensure at least one locale has content
                    if (empty($filtered[$field])) {
                        $fallbackLocale = $configuredLocales[0] ?? 'en';
                        $filtered[$field] = [
                            $fallbackLocale => $data[$field]['en'] ?? reset($data[$field]),
                        ];
                    }
                }
            }

            $templateData = array_merge($filtered, [
                'email_theme_id' => $theme?->id,
                'is_active' => $data['is_active'] ?? true,
                'is_locked' => $data['is_locked'] ?? false,
            ]);

            // Add from and reply_to if they exist
            if (isset($data['from'])) {
                $templateData['from'] = $data['from'];
            }
            
            if (isset($data['reply_to'])) {
                $templateData['reply_to'] = $data['reply_to'];
            }

            EmailTemplate::firstOrCreate(
                ['key' => $filtered['key']],
                $templateData
            );
        }
    }

    /**
     * @return array<int, string>
     */
    protected function getConfiguredLocales(): array
    {
        try {
            $languages = app(GeneralSettings::class)->languages;

            return array_column($languages, 'code');
        } catch (\Throwable) {
            return [app()->getLocale()];
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    protected function getTemplateDefinitions(): array
    {
        return [
            $this->newEmailAccountTemplate(),
            $this->welcomeTemplate(),
            $this->verifyEmailTemplate(),
            $this->passwordResetTemplate(),
            $this->passwordChangedTemplate(),
            $this->generalNotificationTemplate(),
            $this->outOfDateAppTemplate(),
            $this->newDomainAccountTemplate(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function newEmailAccountTemplate(): array
    {
        return [
            'key' => 'new-email-account',
            'is_locked' => false,
            'is_active' => true,
            'name' => [
                'en' => 'New Account',
            ],
            'category' => 'system',
            'tags' => [],
            'subject' => [
                'en' => 'Welcome {{ user.name }}!',
            ],
            'preheader' => [
                'en' => 'Your new email account is ready to go!',
            ],
            'body' => [
                'en' => '<h2>Hello <span data-type="mergeTag" data-id="user.name"></span>!</h2><p><strong>Welcome to your new email!</strong><br><br>Some information for your records:<br><br><strong>SMTP (outgoing) server:</strong> <span data-type="mergeTag" data-id="system.smtp_server"></span></p><p><strong>IMAP (incoming) server:</strong> <span data-type="mergeTag" data-id="system.imap_server"></span></p><p>Any problems, please email <span data-type="mergeTag" data-id="support_email"></span></p>',
            ],
            'from' => [
                'address' => null,
                'name' => null,
            ],
            'reply_to' => [
                'address' => null,
                'name' => null,
            ],
            'token_schema' => [
                ['token' => 'user.name', 'description' => 'The users name', 'example' => 'John Doe'],
                ['token' => 'user.email', 'description' => 'The users email address', 'example' => 'them@example.com'],
                ['token' => 'system.smtp_server', 'description' => 'The address of the SMTP server', 'example' => 'smtp.yourdomain.com'],
                ['token' => 'system.imap_server', 'description' => 'The address of the IMAP server', 'example' => 'imap.yourdomain.com'],
                ['token' => 'system.pop3_server', 'description' => 'The address of the POP3 server', 'example' => 'pop3.yourdomain.com'],
                ['token' => 'support_email', 'description' => 'Support email address', 'example' => 'support@yourdomain.com'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function welcomeTemplate(): array
    {
        return [
            'key' => 'user-welcome',
            'is_locked' => true,
            'is_active' => true,
            'name' => [
                'en' => 'Welcome New User',
            ],
            'category' => 'system',
            'subject' => [
                'en' => 'Welcome to {{ config.app.name }}, {{ user.name }}!',
            ],
            'preheader' => [
                'en' => "We're glad you're here.",
            ],
            'body' => [
                'en' => <<<'HTML'
<h2>Welcome aboard, {{ user.name }}!</h2>
<p>Thanks for joining <strong>{{ config.app.name }}</strong>. We're excited to have you.</p>
<p>Here are a few things you can do to get started:</p>
<ul>
    <li>Complete your profile</li>
    <li>Explore the dashboard</li>
    <li>Check out our documentation</li>
</ul>
<p>If you have any questions, just reply to this email — we're here to help.</p>
<p>Cheers,<br>The {{ config.app.name }} Team</p>
HTML,
            ],
            'token_schema' => [
                ['token' => 'user.name', 'description' => 'Full name of the registered user', 'example' => 'John Doe'],
                ['token' => 'user.email', 'description' => 'Email address of the user', 'example' => 'john@example.com'],
                ['token' => 'config.app.name', 'description' => 'Application name', 'example' => 'MyApp'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function verifyEmailTemplate(): array
    {
        return [
            'key' => 'user-verify-email',
            'is_locked' => true,
            'is_active' => true,
            'name' => [
                'en' => 'Verify Email Address',
            ],
            'category' => 'system',
            'subject' => [
                'en' => 'Verify your email address',
            ],
            'preheader' => [
                'en' => 'Please confirm your email to activate your account.',
            ],
            'body' => [
                'en' => <<<'HTML'
<h2>Verify your email address</h2>
<p>Hi {{ user.name }},</p>
<p>Please click the button below to verify your email address.</p>
<div data-type="customBlock" data-id="emailButton" data-config="{&quot;label&quot;:&quot;Verify Email Address&quot;,&quot;url&quot;:&quot;{{ url }}&quot;,&quot;align&quot;:&quot;center&quot;}"></div>
<p>If you did not create an account, no further action is required.</p>
<p>Thanks,<br>{{ config.app.name }}</p>
HTML,
            ],
            'token_schema' => [
                ['token' => 'user.name', 'description' => 'User name', 'example' => 'John Doe'],
                ['token' => 'url', 'description' => 'Verification URL', 'example' => 'https://example.com/verify/...'],
                ['token' => 'config.app.name', 'description' => 'Application name', 'example' => 'MyApp'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function passwordResetTemplate(): array
    {
        return [
            'key' => 'user-password-reset',
            'is_locked' => true,
            'is_active' => true,
            'name' => [
                'en' => 'Password Reset Request',
            ],
            'category' => 'system',
            'subject' => [
                'en' => 'Reset your password',
            ],
            'preheader' => [
                'en' => 'You requested a password reset.',
            ],
            'body' => [
                'en' => <<<'HTML'
<h2>Reset your password</h2>
<p>Hi {{ user.name }},</p>
<p>We received a request to reset your password. Click the button below to choose a new one.</p>
<div data-type="customBlock" data-id="emailButton" data-config="{&quot;label&quot;:&quot;Reset Password&quot;,&quot;url&quot;:&quot;{{ url }}&quot;,&quot;align&quot;:&quot;center&quot;}"></div>
<p>This link will expire in 60 minutes.</p>
<p>If you didn't request this, you can safely ignore this email.</p>
<p>Thanks,<br>{{ config.app.name }}</p>
HTML,
            ],
            'token_schema' => [
                ['token' => 'user.name', 'description' => 'User name', 'example' => 'John Doe'],
                ['token' => 'url', 'description' => 'Password reset URL', 'example' => 'https://example.com/reset/...'],
                ['token' => 'config.app.name', 'description' => 'Application name', 'example' => 'MyApp'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function passwordChangedTemplate(): array
    {
        return [
            'key' => 'user-password-changed',
            'is_locked' => true,
            'is_active' => true,
            'name' => [
                'en' => 'Password Changed Confirmation',
            ],
            'category' => 'system',
            'subject' => [
                'en' => 'Your password has been changed',
            ],
            'preheader' => [
                'en' => 'Your account password was updated.',
            ],
            'body' => [
                'en' => <<<'HTML'
<h2>Password changed</h2>
<p>Hi {{ user.name }},</p>
<p>Your password for <strong>{{ config.app.name }}</strong> was successfully changed.</p>
<p>If you did not make this change, please contact us immediately.</p>
<p>Thanks,<br>{{ config.app.name }}</p>
HTML,
            ],
            'token_schema' => [
                ['token' => 'user.name', 'description' => 'User name', 'example' => 'John Doe'],
                ['token' => 'config.app.name', 'description' => 'Application name', 'example' => 'MyApp'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function generalNotificationTemplate(): array
    {
        return [
            'key' => 'general-notification',
            'is_locked' => false,
            'is_active' => true,
            'name' => [
                'en' => 'General Notification',
            ],
            'category' => 'notification',
            'subject' => [
                'en' => '{{ subject | "Notification from " }}{{ config.app.name }}',
            ],
            'preheader' => [
                'en' => '',
            ],
            'body' => [
                'en' => <<<'HTML'
<p>Hi {{ user.name | "there" }},</p>
<p>{{ message }}</p>
<p>Thanks,<br>{{ config.app.name }}</p>
HTML,
            ],
            'token_schema' => [
                ['token' => 'user.name', 'description' => 'Recipient name (optional)', 'example' => 'John'],
                ['token' => 'subject', 'description' => 'Email subject (optional)', 'example' => 'Important Update'],
                ['token' => 'message', 'description' => 'The notification message body', 'example' => 'Your report is ready.'],
                ['token' => 'config.app.name', 'description' => 'Application name', 'example' => 'MyApp'],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function outOfDateAppTemplate(): array
    {
        return [
            'key' => 'out-of-date-app',
            'is_locked' => false,
            'is_active' => true,
            'name' => [
                'en' => 'VExim web requires update',
            ],
            'category' => 'system',
            'tags' => [],
            'subject' => [
                'en' => '[{{ update_priority }}] {{ config.app.name }} requires update to {{ latest_version }}',
            ],
            'preheader' => [
                'en' => 'Version {{ latest_version }} is now available (Priority: {{ update_priority|upper }})',
            ],
            'body' => [
                'en' => <<<'HTML'
<p>Your version of <strong>{{ config.app.name }}</strong> is out of date.</p>
<p><strong>Current Version:</strong> {{ current_version }}<br>
<strong>Latest Version:</strong> <strong>{{ latest_version }}</strong><br>
<strong>Priority:</strong> {{ update_priority|upper }}</p>
<p><strong>Update Message:</strong><br>{{ update_message }}</p>
<p>To get more information, please visit the repository at:<br>
<a href="{{ config.vexim.package.url }}">{{ config.vexim.package.url }}</a></p>
<p>Check performed at: {{ check_time }}</p>
<hr>
<p>This notification was sent automatically by the application health monitoring system.</p>
HTML,
            ],
            'from' => [
                'address' => null,
                'name' => null,
            ],
            'reply_to' => [
                'address' => null,
                'name' => null,
            ],
            'token_schema' => [],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function newDomainAccountTemplate(): array
    {
        return [
            'key' => 'new-domain-account',
            'is_locked' => false,
            'is_active' => true,
            'name' => [
                'en' => 'Domain welcome email',
            ],
            'category' => 'notification',
            'tags' => [],
            'subject' => [
                'en' => 'Welcome to {{ config.app.name }}',
            ],
            'preheader' => [
                'en' => "We're glad you joined us!",
            ],
            'body' => [
                'en' => <<<'HTML'
<h2>Welcome to {{ config.app.name }}!</h2>
<h3>Hello {{ user.name }}.</h3>
<p>Your domain <strong><span data-type="mergeTag" data-id="domainname"></span></strong> has been added to our system and you can now login and add your email accounts.</p>
<h3><strong>The important stuff:</strong></h3>
<p>Our outgoing (SMTP) server: <strong><span data-type="mergeTag" data-id="smtpserver"></span></strong> on port <strong><span data-type="mergeTag" data-id="smtpport"></span></strong></p>
<p>Our incoming (IMAP) server: <strong><span data-type="mergeTag" data-id="imapserver"></span></strong> on port <strong><span data-type="mergeTag" data-id="imapport"></span></strong></p>
<p>You can login to our web interface by visiting <a href="{{ config.app.url }}">{{ config.app.url }}</a></p>
<p>Any questions, please get in contact with us.</p>
<p>Thanks</p>
<p>{{ config.app.name }}</p>
HTML,
            ],
            'from' => [
                'address' => null,
                'name' => null,
            ],
            'reply_to' => [
                'address' => null,
                'name' => null,
            ],
            'token_schema' => [
                ['token' => 'domainname', 'description' => 'The domain name added', 'example' => 'theirdomain.com'],
                ['token' => 'user.name', 'description' => 'The name of the user', 'example' => 'John Doe'],
                ['token' => 'imapserver', 'description' => 'The IMAP server address', 'example' => 'mail.your.domain'],
                ['token' => 'smtpserver', 'description' => 'The SMTP server address', 'example' => 'mail.your.domain'],
                ['token' => 'imapport', 'description' => 'The IMAP server port', 'example' => '993'],
                ['token' => 'smtpport', 'description' => 'The SMTP server port', 'example' => '465'],
            ],
        ];
    }
}