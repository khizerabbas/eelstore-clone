<x-filament-panels::page>
    @php $backups = $this->getBackups(); @endphp

    <x-filament::section>
        <x-slot name="heading">How backups work</x-slot>
        <div class="text-sm space-y-1">
            <p>Every night at 01:30 the database and all uploaded files (product images, customers' quote files) are saved as a zip.
               Old backups are removed automatically (all kept for 7 days, then daily / weekly / monthly ones).</p>
            <p><strong>Download a backup at least once a week and keep it somewhere safe off the server</strong> (e.g. Google Drive).
               If something goes wrong with the backup, the owner gets an email.</p>
        </div>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">Available backups ({{ count($backups) }})</x-slot>

        @if(empty($backups))
            <p class="text-sm text-gray-500">No backups yet. They are created every night (the cron job must be running), or use "Back up now".</p>
        @else
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                    <tr class="text-left text-gray-500 border-b border-gray-200 dark:border-white/10">
                        <th class="py-2 pr-4 font-medium">Created</th>
                        <th class="py-2 pr-4 font-medium">File</th>
                        <th class="py-2 pr-4 font-medium">Size</th>
                        <th class="py-2"></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($backups as $backup)
                        <tr class="border-b border-gray-100 dark:border-white/5">
                            <td class="py-2 pr-4">{{ $backup['date']->format('d M Y, h:i A') }} <span class="text-gray-500">({{ $backup['date']->diffForHumans() }})</span></td>
                            <td class="py-2 pr-4 font-mono text-xs">{{ $backup['name'] }}</td>
                            <td class="py-2 pr-4">{{ $backup['size'] }}</td>
                            <td class="py-2 text-right">
                                <x-filament::button size="sm" color="gray" icon="heroicon-o-arrow-down-tray"
                                                    wire:click="download('{{ $backup['path'] }}')">
                                    Download
                                </x-filament::button>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </x-filament::section>
</x-filament-panels::page>
