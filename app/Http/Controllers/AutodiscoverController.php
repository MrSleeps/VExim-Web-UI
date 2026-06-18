<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Repositories\Interfaces\SettingRepositoryInterface;

class AutodiscoverController extends Controller
{
    /**
     * @var SettingRepositoryInterface
     */
    protected SettingRepositoryInterface $settingRepository;
    
    /**
     * Constructor.
     */
    public function __construct(
        SettingRepositoryInterface $settingRepository
    ) {
        $this->settingRepository = $settingRepository;
    }    
    
    public function showAutodiscoverXml(Request $request) {
        $imapServer = $this->settingRepository->get('default_imap_server', 'imap.your.domain');
        $imapPort = $this->settingRepository->get('default_imap_port', 'imap.your.domain');
        $smtpServer = $this->settingRepository->get('default_smtp_server', 'smtp.your.domain');
        $email = $request->input('Email', '');
        $domain = substr($email, strrpos($email, '@') + 1);
        
        // If no email is provided, try to guess from the request host.
        if (!$domain) {
            $domain = $request->getHost();
        }

        return response()->view('autodiscover.autodiscover', [
            'email' => $email,
            'imapServer' => $imapServer,
            'smtpServer' => $smtpServer,
        ])->withHeaders([
            'Content-Type' => 'application/xml',
        ]);        
    }
}
