<?php

namespace App\Filament\Resources\Settings\Pages;

use App\Filament\Resources\Settings\SettingResource;
use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

class ManageAllSettings extends Page implements HasSchemas
{
    use InteractsWithSchemas;
    
    protected static string $resource = SettingResource::class;
    
    protected string $view = 'filament.resources.settings.pages.manage-all-settings';
    
    public ?array $data = [];
    
    public function mount(): void
    {
        $settings = Setting::all();
        $this->form->fill($settings->pluck('value', 'key')->toArray());
    }
    
    public function form(Schema $schema): Schema
    {
        return $schema
            ->schema($this->getFormSchema())
            ->statePath('data');
    }
    
    protected function getFormSchema(): array
    {
        $settings = Setting::all();
        $sections = [];
        
        $grouped = $settings->groupBy(function ($setting) {
            $parts = explode('_', $setting->key);
            return count($parts) > 1 ? $parts[0] : 'general';
        });
        
        foreach ($grouped as $group => $groupSettings) {
            $fields = [];
            
            foreach ($groupSettings as $setting) {
                $fields[] = $this->createFieldForSetting($setting);
            }
            
            $sections[] = Section::make(ucfirst($group))
                ->schema($fields)
                ->columns(2);
        }
        
        return $sections;
    }
    
    protected function createFieldForSetting(Setting $setting)
    {
        $label = ucwords(str_replace('_', ' ', $setting->key));
        
        return match($setting->type) {
            'boolean' => Toggle::make($setting->key)
                ->label($label)
                ->helperText($setting->description)
                ->default((bool) $setting->value),
                
            'integer' => TextInput::make($setting->key)
                ->label($label)
                ->helperText($setting->description)
                ->numeric()
                ->integer()
                ->default((int) $setting->value),
                
            'string' => $this->getStringField($setting, $label),
            
            default => TextInput::make($setting->key)
                ->label($label)
                ->helperText($setting->description)
                ->default($setting->value),
        };
    }
    
    protected function getStringField(Setting $setting, string $label)
    {
        if ($setting->key === 'crypt_scheme') {
            return Select::make($setting->key)
                ->label($label)
                ->helperText($setting->description)
                ->options([
                    'sha512' => 'SHA512',
                    'bcrypt' => 'Bcrypt',
                ])
                ->default($setting->value);
        }
        
        if (strlen($setting->value) > 100 || str_contains($setting->key, 'welcome')) {
            return Textarea::make($setting->key)
                ->label($label)
                ->helperText($setting->description)
                ->default($setting->value)
                ->rows(5)
                ->columnSpanFull();
        }
        
        return TextInput::make($setting->key)
            ->label($label)
            ->helperText($setting->description)
            ->default($setting->value);
    }
    
    public function save(): void
    {
        try {
            foreach ($this->form->getState() as $key => $value) {
                if (is_bool($value)) {
                    $value = $value ? '1' : '0';
                }
                
                Setting::where('key', $key)->update(['value' => (string) $value]);
            }
            
            Notification::make()
                ->title('All settings saved successfully')
                ->success()
                ->send();
                
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error saving settings')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }
    
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save All Settings')
                ->submit('save')
                ->color('primary'),
                
            Action::make('back')
                ->label('Back to List')
                ->url(SettingResource::getUrl('index'))
                ->color('gray'),
        ];
    }
}