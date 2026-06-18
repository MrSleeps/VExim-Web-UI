<x-filament-widgets::widget>
    <x-filament::card>
        <div class="flex items-center justify-between">
            <h2 class="text-lg font-bold">Recent Login Activity</h2>
        </div>
        
        <div class="mt-4 flow-root">
            <div class="-mx-4 -my-2 overflow-x-auto sm:-mx-6 lg:-mx-8">
                <div class="inline-block min-w-full py-2 align-middle sm:px-6 lg:px-8">
                    <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-600">
                        <thead>
                            <tr>
                                <th class="py-3.5 pl-4 pr-3 text-left text-sm font-semibold sm:pl-0">Event</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold">User</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold">IP Address</th>
                                <th class="px-3 py-3.5 text-left text-sm font-semibold">Time</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                            @forelse($activities as $activity)
                                <tr>
                                    <td class="whitespace-nowrap py-4 pl-4 pr-3 text-sm sm:pl-0">
                                        @if($activity->event === 'login')
                                            <span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20 dark:bg-green-500/10 dark:text-green-400">
                                                Login
                                            </span>
                                        @elseif($activity->event === 'logout')
                                            <span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-600/20 dark:bg-gray-500/10 dark:text-gray-400">
                                                Logout
                                            </span>
                                        @elseif($activity->event === 'failed_login')
                                            <span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/20 dark:bg-red-500/10 dark:text-red-400">
                                                Failed Login
                                            </span>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        @if($activity->causer)
                                            {{ $activity->causer->name }} ({{ $activity->causer->email }})
                                        @elseif($activity->properties['email'] ?? false)
                                            {{ $activity->properties['email'] }}
                                        @else
                                            Unknown
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        {{ $activity->properties['ip'] ?? 'N/A' }}
                                    </td>
                                    <td class="whitespace-nowrap px-3 py-4 text-sm">
                                        {{ $activity->created_at->diffForHumans() }}
                                        <br>
                                        <span class="text-xs text-gray-500">{{ $activity->created_at->format('Y-m-d H:i:s') }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="whitespace-nowrap py-4 pl-4 pr-3 text-sm text-gray-500 text-center sm:pl-0">
                                        No login activity recorded yet.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </x-filament::card>
</x-filament-widgets::widget>
