{{-- resources/views/filament/modals/spam-stats-details.blade.php --}}

<div class="p-6">
    <div class="space-y-6">
        <!-- Header Section -->
        <div class="border-b border-gray-200 dark:border-gray-700 pb-4">
            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                Spam Statistics Details
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Detailed information for {{ $record->domain }} on {{ $record->date }}
            </p>
        </div>

        <!-- Statistics Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Message Statistics -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Message Statistics</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Total Incoming:</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($record->incoming_count) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Spam Messages:</span>
                        <span class="text-sm font-semibold text-red-600 dark:text-red-400">{{ number_format($record->spam_count) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Virus Messages:</span>
                        <span class="text-sm font-semibold text-yellow-600 dark:text-yellow-400">{{ number_format($record->virus_count) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Clean Messages:</span>
                        <span class="text-sm font-semibold text-green-600 dark:text-green-400">
                            {{ number_format($record->incoming_count - $record->spam_count) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Spam Analysis -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Spam Analysis</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Spam Rate:</span>
                        <span class="text-sm font-semibold" style="color: {{ $record->spam_percentage > 50 ? '#dc2626' : ($record->spam_percentage > 20 ? '#f59e0b' : '#10b981') }}">
                            {{ round($record->spam_percentage, 2) }}%
                        </span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Average Score:</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($record->avg_spam_score, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Maximum Score:</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($record->max_spam_score, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Risk Level:</span>
                        <span class="text-sm font-semibold">
                            @php
                                $riskLevel = $record->spam_percentage < 5 ? 'Low' : ($record->spam_percentage < 15 ? 'Moderate' : ($record->spam_percentage < 30 ? 'High' : 'Critical'));
                                $riskColor = $record->spam_percentage < 5 ? 'text-green-600' : ($record->spam_percentage < 15 ? 'text-yellow-600' : ($record->spam_percentage < 30 ? 'text-orange-600' : 'text-red-600'));
                            @endphp
                            <span class="{{ $riskColor }}">{{ $riskLevel }}</span>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Storage Information -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Storage Information</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Total Size:</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ $record->formatted_size ?? \App\Filament\Resources\SpamStats\Tables\SpamStatsTable::formatBytesStatic($record->total_size_bytes) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Average Size:</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            @php
                                $avgSize = $record->incoming_count > 0 ? $record->total_size_bytes / $record->incoming_count : 0;
                            @endphp
                            {{ \App\Filament\Resources\SpamStats\Tables\SpamStatsTable::formatBytesStatic($avgSize) }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Quick Stats -->
            <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
                <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Quick Stats</h4>
                <div class="space-y-2">
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Spam per Day:</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($record->spam_count) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Virus per Day:</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">{{ number_format($record->virus_count) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-sm text-gray-600 dark:text-gray-300">Spam Score Range:</span>
                        <span class="text-sm font-semibold text-gray-900 dark:text-white">
                            {{ number_format($record->avg_spam_score, 2) }} - {{ number_format($record->max_spam_score, 2) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Progress Bar for Spam Rate -->
        <div class="bg-gray-50 dark:bg-gray-800 rounded-lg p-4">
            <h4 class="text-sm font-medium text-gray-500 dark:text-gray-400 mb-3">Spam Rate Visualization</h4>
            <div class="relative pt-1">
                <div class="flex mb-2 items-center justify-between">
                    <div>
                        <span class="text-xs font-semibold inline-block py-1 px-2 uppercase rounded-full text-teal-600 bg-teal-200 dark:bg-teal-800 dark:text-teal-100">
                            Spam Rate: {{ round($record->spam_percentage, 2) }}%
                        </span>
                    </div>
                    <div class="text-right">
                        <span class="text-xs font-semibold inline-block text-teal-600">
                            {{ $record->spam_count }} / {{ $record->incoming_count }}
                        </span>
                    </div>
                </div>
                <div class="overflow-hidden h-2 mb-4 text-xs flex rounded bg-gray-200 dark:bg-gray-700">
                    <div style="width: {{ min($record->spam_percentage, 100) }}%;" 
                         class="shadow-none flex flex-col text-center whitespace-nowrap text-white justify-center 
                                @if($record->spam_percentage > 50) bg-red-500
                                @elseif($record->spam_percentage > 20) bg-yellow-500
                                @else bg-green-500 @endif">
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
