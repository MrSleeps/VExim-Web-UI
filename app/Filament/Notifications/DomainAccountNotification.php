<?php

namespace App\Filament\Notifications;

use FinityLabs\FinMail\Mail\TemplateMail;
use App\Models\Domain;
use App\Models\User;
use App\Services\EmailServerSettingsService;
use App\Services\DomainAdminService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class DomainAccountNotification
{
    protected EmailServerSettingsService $emailSettingsService;
    protected DomainAdminService $domainAdminService;

    public function __construct(
        EmailServerSettingsService $emailSettingsService,
        DomainAdminService $domainAdminService
    ) {
        $this->emailSettingsService = $emailSettingsService;
        $this->domainAdminService = $domainAdminService;
    }

    /**
     * Send domain account notification to a specific user
     *
     * @param Domain $domain
     * @param User $user
     * @param array $additionalData
     * @return bool
     */
	public function sendToUser(Domain $domain, User $user, array $additionalData = []): bool
	{
		try {
			$recipientEmail = $user->email;

			if (!$recipientEmail) {
				throw new \Exception('User has no email address');
			}

			$serverSettings = $this->emailSettingsService->getServerSettings($domain);

			// Get the first exim user for example email
			$firstEmailAccount = $domain->eximUsers()->first();

			// Prepare all data for token replacement
			$templateData = [
				'user.name' => $user->name ?: explode('@', $user->email)[0] ?? 'User',
				'domainname' => $domain->domain,
				'smtpserver' => $serverSettings['smtp_server'],
				'smtpport' => $serverSettings['smtp_port'],
				'imapserver' => $serverSettings['imap_server'],
				'imapport' => $serverSettings['imap_port'],
				'webmail_url' => config('app.url') . '/webmail',
				'example_email' => $firstEmailAccount ? $firstEmailAccount->username : "yourname@{$domain->domain}",
				'app_name' => config('app.name'),
				'app_url' => config('app.url'),
			];

			Mail::to($recipientEmail)->send(
				TemplateMail::make('new-domain-account')
					->models($templateData)
			);

			$domain->update(['domain_notified_at' => now()]);

			return true;
		} catch (\Exception $e) {
			\Illuminate\Support\Facades\Log::error('Failed to send domain notification', [
				'domain' => $domain->domain,
				'user_email' => $user->email ?? 'unknown',
				'error' => $e->getMessage()
			]);

			return false;
		}
	}

    /**
     * Send domain account notification to ALL domain administrators
     *
     * @param Domain $domain
     * @param array $additionalData
     * @return array Result with success and failed lists
     */
    public function sendToAllAdmins(Domain $domain, array $additionalData = []): array
    {
        $admins = $this->domainAdminService->getDomainAdmins($domain);
        
        if ($admins->isEmpty()) {
            return [
                'success' => [],
                'failed' => [],
                'message' => 'No domain administrators found for this domain'
            ];
        }
        
        $results = ['success' => [], 'failed' => []];
        
        foreach ($admins as $admin) {
            $sent = $this->sendToUser($domain, $admin, $additionalData);
            
            if ($sent) {
                $results['success'][] = $admin->email;
            } else {
                $results['failed'][] = $admin->email;
            }
        }
        
        return $results;
    }

    /**
     * Send notification to primary domain admin only
     *
     * @param Domain $domain
     * @param array $additionalData
     * @return bool
     */
    public function sendToPrimaryAdmin(Domain $domain, array $additionalData = []): bool
    {
        $primaryAdmin = $this->domainAdminService->getPrimaryDomainAdmin($domain);
        
        if (!$primaryAdmin) {
            \Illuminate\Support\Facades\Log::warning('No primary domain admin found', [
                'domain' => $domain->domain
            ]);
            return false;
        }
        
        return $this->sendToUser($domain, $primaryAdmin, $additionalData);
    }

    /**
     * Prepare template variables for the email
     *
     * @param Domain $domain
     * @param User $user
     * @param array $serverSettings
     * @param array $additionalData
     * @return array
     */
	protected function prepareTemplateData(
		Domain $domain, 
		User $user, 
		array $serverSettings,
		array $additionalData = []
	): array {
		$firstEmailAccount = $domain->eximUsers()->first();

		return [
			'username'    => $user->name ?: explode('@', $user->email)[0] ?? 'User',
			'domainname'  => $domain->domain,
			'smtpserver'  => $serverSettings['smtp_server'],
			'smtpport'    => $serverSettings['smtp_port'],
			'imapserver'  => $serverSettings['imap_server'],
			'imapport'    => $serverSettings['imap_port'],
			'example_email' => $firstEmailAccount ? $firstEmailAccount->username : "yourname@{$domain->domain}",
			'webmail_url' => config('app.url') . '/webmail',
		];
	}

    /**
     * Send bulk notifications for multiple domains
     *
     * @param Collection $domains
     * @param string $sendType 'primary' or 'all'
     * @return array
     */
    public function sendBulk(Collection $domains, string $sendType = 'primary'): array
    {
        $results = [
            'total_domains' => $domains->count(),
            'successful' => [],
            'failed' => []
        ];
        
        foreach ($domains as $domain) {
            try {
                if ($sendType === 'all') {
                    $result = $this->sendToAllAdmins($domain);
                    $success = !empty($result['success']);
                } else {
                    $success = $this->sendToPrimaryAdmin($domain);
                }
                
                if ($success) {
                    $results['successful'][] = $domain->domain;
                } else {
                    $results['failed'][] = [
                        'domain' => $domain->domain,
                        'reason' => 'No admin found or send failed'
                    ];
                }
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'domain' => $domain->domain,
                    'reason' => $e->getMessage()
                ];
            }
        }
        
        return $results;
    }
}