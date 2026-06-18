<?php

namespace App\Services;

use App\Repositories\SettingRepository;
use App\Models\Domain;

class EmailServerSettingsService
{
    protected SettingRepository $settingRepository;

    public function __construct(SettingRepository $settingRepository)
    {
        $this->settingRepository = $settingRepository;
    }

    /**
     * Get email server settings for a domain
     *
     * @param Domain|null $domain
     * @return array
     */
    public function getServerSettings(?Domain $domain = null): array
    {
        $useDomainForServers = (bool) $this->settingRepository->get('use_domain_for_servers', false);
        
        if ($useDomainForServers && $domain) {
            $customImapHost = $this->settingRepository->get('custom_imap_host', 'mail');
            $customSmtpHost = $this->settingRepository->get('custom_smtp_host', 'mail');
            $customImapPort = $this->settingRepository->get('custom_imap_port', 993);
            $customSmtpPort = $this->settingRepository->get('custom_smtp_port', 587);
            
            return [
                'smtp_server' => $this->buildServerHost($customSmtpHost, $domain->domain),
                'smtp_port' => $customSmtpPort,
                'imap_server' => $this->buildServerHost($customImapHost, $domain->domain),
                'imap_port' => $customImapPort,
                'use_domain_specific' => true,
            ];
        }
        
        return [
            'smtp_server' => $this->settingRepository->get('default_smtp_server', 'mail.example.com'),
            'smtp_port' => $this->settingRepository->get('default_smtp_port', 587),
            'imap_server' => $this->settingRepository->get('default_imap_server', 'mail.example.com'),
            'imap_port' => $this->settingRepository->get('default_imap_port', 993),
            'use_domain_specific' => false,
        ];
    }

    /**
     * Build the full server hostname by combining prefix with domain
     *
     * @param string $prefix
     * @param string $domain
     * @return string
     */
    protected function buildServerHost(string $prefix, string $domain): string
    {
        if (filter_var($prefix, FILTER_VALIDATE_DOMAIN) || filter_var($prefix, FILTER_VALIDATE_IP)) {
            return $prefix;
        }
        
        $prefix = rtrim($prefix, '.');
        
        return $prefix . '.' . $domain;
    }

    /**
     * Get all email-related settings (for admin panel)
     *
     * @return array
     */
    public function getAllEmailSettings(): array
    {
        return [
            'use_domain_for_servers' => $this->settingRepository->get('use_domain_for_servers', false),
            'custom_imap_host' => $this->settingRepository->get('custom_imap_host', 'mail'),
            'custom_imap_port' => $this->settingRepository->get('custom_imap_port', 993),
            'custom_smtp_host' => $this->settingRepository->get('custom_smtp_host', 'mail'),
            'custom_smtp_port' => $this->settingRepository->get('custom_smtp_port', 587),
            'default_imap_server' => $this->settingRepository->get('default_imap_server', 'mail.example.com'),
            'default_imap_port' => $this->settingRepository->get('default_imap_port', 993),
            'default_smtp_server' => $this->settingRepository->get('default_smtp_server', 'mail.example.com'),
            'default_smtp_port' => $this->settingRepository->get('default_smtp_port', 587),
        ];
    }

    /**
     * Update email server settings
     *
     * @param array $settings
     * @return void
     */
    public function updateEmailSettings(array $settings): void
    {
        foreach ($settings as $key => $value) {
            $this->settingRepository->set($key, $value);
        }
    }
}