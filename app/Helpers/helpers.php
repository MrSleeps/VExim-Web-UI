<?php

if (!function_exists('spamFilterName')) {
    function spamFilterName(): string
    {
        return config('vexim.system.spam_engine') === 'rspamd' 
            ? 'RSpamd' 
            : 'SpamAssassin';
    }
}
