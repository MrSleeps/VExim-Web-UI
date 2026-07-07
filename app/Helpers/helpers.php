<?php

if (!function_exists('spamFilterName')) {
    function spamFilterName(): string
    {
        return config('vexim.system.spam_engine') === 'rspamd' 
            ? 'RSpamd' 
            : 'SpamAssassin';
    }
}

if (!function_exists('pwa_debug')) {
    function pwa_debug($message, $data = null)
    {
        if (config('app.debug')) {
            if ($data !== null) {
                return "console.log('{$message}', " . json_encode($data) . ");";
            }
            return "console.log('{$message}');";
        }
        return '';
    }
}