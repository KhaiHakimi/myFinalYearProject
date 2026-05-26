<?php

namespace App\Console\Commands;

use App\Services\ChannelManagerService;
use Illuminate\Console\Command;

class SyncChannels extends Command
{
    protected $signature = 'channels:sync {--operator= : Sync a specific operator by code}';
    protected $description = 'Sync schedules and bookings from external operator APIs';

    public function handle(ChannelManagerService $channelManager): int
    {
        $operatorCode = $this->option('operator');

        if ($operatorCode) {
            $operator = \App\Models\Operator::where('code', $operatorCode)->first();

            if (! $operator) {
                $this->error("Operator '{$operatorCode}' not found.");
                return self::FAILURE;
            }

            $this->info("Syncing operator: {$operator->name}...");
            $result = $channelManager->syncOperator($operator);

            $this->info("Schedules synced: {$result['synced']}");
            $this->info("Bookings imported: {$result['bookings']}");

            if (! empty($result['errors'])) {
                $this->warn('Errors encountered:');
                foreach ($result['errors'] as $error) {
                    $this->warn("  - {$error}");
                }
            }

            return self::SUCCESS;
        }

        $this->info('Syncing all enabled operators...');
        $results = $channelManager->syncAll();

        if (empty($results)) {
            $this->info('No operators with sync enabled.');
            return self::SUCCESS;
        }

        foreach ($results as $code => $result) {
            $this->info("[{$code}] Schedules: {$result['synced']}, Bookings: {$result['bookings']}");

            if (! empty($result['errors'])) {
                foreach ($result['errors'] as $error) {
                    $this->warn("  ↳ {$error}");
                }
            }
        }

        $this->info('Channel sync complete.');
        return self::SUCCESS;
    }
}
