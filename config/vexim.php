<?php

return [
	'system' => [
		'default_gid' => env('VEXIM_GID','1000'),
		'default_uid' => env('VEXIM_UID','1000'),
		'allow_postmaster_uid_gid' => env('VEXIM_UID',true),
		'mailman_enabled' => env('VEXIM_MAILMAN_ENABLED', false),
		'spam_engine' => strtolower(env('VEXIM_SPAM_ENGINE', 'rspamd') ?? 'rspamd')
	],
	'website' => [
		'admin_enforce_2fa' => env('VEXIM_ENFORCE_2FA',false)
	],
	'widgets' => [
		'spam_stats' => strtolower(env('VEXIM_SPAM_ENGINE', 'rspamd')) === 'rspamd'
	],
    'package' => [
        'org' => 'MrSleeps',
        'name' => 'VExim-Web-UI',
        'url' => 'https://github.com/MrSleeps/VExim-Web-UI'
    ],
    'communications' => [
        'email_reports_to' => env('HEALTH_TO_ADDRESS', ''),
    ]
];