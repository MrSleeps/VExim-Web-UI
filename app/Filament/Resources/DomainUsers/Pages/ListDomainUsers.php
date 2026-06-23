<?php

namespace App\Filament\Resources\DomainUsers\Pages;

use Filament\Resources\Pages\ListRecords;
use Illuminate\Http\RedirectResponse;
use App\Filament\Resources\DomainUsers\DomainUserResource;

class ListDomainUsers extends ListRecords
{
    protected static string $resource = DomainUserResource::class;
    
    public function mount(): void
    {
        parent::mount();

        $user = auth()->user();
        if ($user instanceof \VEximweb\Core\Data\Models\EximUser) {
            $this->redirect(DomainUserResource::getUrl('edit', ['record' => $user->getKey()]));
        }
    }
}