<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Sentinel;
use Illuminate\Console\Command;

class SandboxCommand extends Command
{
    protected $signature = 'terminal:sandbox
        {action=status : install | restore | status}
        {--force : rebuild the pristine snapshot from scratch}';

    protected $description = 'Manage the hackable sandbox (install / restore / status)';

    public function handle(Sentinel $sentinel): int
    {
        return match ($this->argument('action')) {
            'install' => $this->install($sentinel),
            'restore' => $this->restore($sentinel),
            default => $this->status($sentinel),
        };
    }

    private function install(Sentinel $sentinel): int
    {
        $this->info('building pristine snapshot…');
        $result = $sentinel->install(force: (bool) $this->option('force'));
        $this->line(sprintf(
            '  pristine ready · live tree %d files · %d anomalies',
            $result['files'],
            $result['anomalies']
        ));

        return self::SUCCESS;
    }

    private function restore(Sentinel $sentinel): int
    {
        $result = $sentinel->restore('cli');
        $this->info(sprintf(
            'restored in %dms · %d anomalies repaired · %d files',
            $result['ms'],
            $result['anomalies'],
            $result['files']
        ));

        return self::SUCCESS;
    }

    private function status(Sentinel $sentinel): int
    {
        $status = $sentinel->status();

        $this->line('installed   : '.($status['installed'] ? 'yes' : 'no'));
        $this->line('sandbox db  : '.($status['db'] ? 'yes' : 'no'));
        $this->line('watching    : '.$status['watching'].' files');
        $this->line('live files  : '.$status['files']);
        $this->line('anomalies   : '.$status['anomalies']);

        if ($status['log']) {
            $this->newLine();
            $this->line('recent log:');
            foreach ($status['log'] as $line) {
                $this->line('  '.$line);
            }
        }

        return self::SUCCESS;
    }
}
