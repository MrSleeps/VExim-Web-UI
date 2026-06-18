{{-- resources/views/filament/widgets/action-breakdown.blade.php --}}

<div class="filament-widgets-action-breakdown">
    <x-filament::widget>
        <x-filament::card>
            <div class="flex items-center justify-between">
                <h3 class="text-lg font-medium">Message Actions (Last 30 Days)</h3>
                <span class="text-sm text-gray-500">Total: {{ number_format($totalMessages) }} messages</span>
            </div>
            
            <div class="mt-4 space-y-4">
                @foreach($actions as $action => $data)
                    <div>
                        <div class="flex justify-between text-sm mb-1">
                            <span class="capitalize">{{ $action }}</span>
                            <span>{{ number_format($data['count']) }} ({{ $data['percentage'] }}%)</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2">
                            <div class="bg-{{ $data['color'] }}-500 h-2 rounded-full" 
                                 style="width: {{ $data['percentage'] }}%"></div>
                        </div>
                        @if($data['virus_count'] > 0)
                            <div class="text-xs text-gray-500 mt-1">
                                {{ number_format($data['virus_count']) }} contained viruses
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </x-filament::card>
    </x-filament::widget>
</div>
