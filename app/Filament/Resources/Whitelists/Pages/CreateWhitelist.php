<?php

namespace App\Filament\Resources\Whitelists\Pages;

use App\Filament\Resources\Whitelists\WhitelistResource;
use App\Models\EximUser;
use Filament\Resources\Pages\CreateRecord;

class CreateWhitelist extends CreateRecord
{
    protected static string $resource = WhitelistResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        \Illuminate\Support\Facades\Log::info('=== mutateFormDataBeforeCreate called ===');
        \Illuminate\Support\Facades\Log::info('Data received:', $data);
        
        if (isset($data['domain_wide']) && $data['domain_wide']) {
            $data['localpart'] = null;
            \Illuminate\Support\Facades\Log::info('Domain wide - setting localpart to null');
        }
        
        if (isset($data['user_id']) && !empty($data['user_id']) && empty($data['localpart'])) {
            $eximUser = EximUser::where('user_id', $data['user_id'])->first();
            if ($eximUser) {
                $data['localpart'] = $eximUser->localpart;
                \Illuminate\Support\Facades\Log::info('Looked up localpart from user_id', [
                    'user_id' => $data['user_id'],
                    'localpart' => $data['localpart']
                ]);
            }
        }
        
        if (auth()->user()->isDomainUser()) {
            $eximUser = EximUser::where('username', auth()->user()->email)->first();
            if ($eximUser) {
                $data['localpart'] = $eximUser->localpart;
                \Illuminate\Support\Facades\Log::info('Set localpart for domain user', [
                    'localpart' => $data['localpart']
                ]);
            }
        }
        
        unset($data['domain_wide']);
        unset($data['user_id_auto']);
        unset($data['localpart_auto']);
        unset($data['localpart_display']);
        
        \Illuminate\Support\Facades\Log::info('Final data after mutation:', $data);
        
        return $data;
    }
}