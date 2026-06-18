<div>
    <x-filament-panels::page>
        <form wire:submit="save">
            {{ $this->form }}
            
            <div class="mt-6 flex gap-3 justify-end">
                <x-filament::button type="submit" color="primary" icon="heroicon-o-check">
                    Save All Settings
                </x-filament::button>
                
                <x-filament::button 
                    href="{{ \App\Filament\Resources\Settings\SettingResource::getUrl('index') }}"
                    tag="a"
                    color="gray"
                    icon="heroicon-o-arrow-left"
                >
                    Back to List
                </x-filament::button>
            </div>
        </form>
    </x-filament-panels::page>
</div>
