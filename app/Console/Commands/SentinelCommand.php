<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Sentinel;
use Illuminate\Console\Command;

/**
 * The watchdog. Run it in the background and the sandbox is restored on a
 * fixed interval no matter what the visitor did to it.
 *
 *   php artisan sentinel              # restore every 8 seconds, forever
 *   php artisan sentinel --once       # a single pulse
 *   php artisan sentinel --interval=5 # custom interval
 */
class SentinelCommand extends Command
{
    protected $signature = 'sentinel
        {--interval=8 : seconds between pulses}
        {--once : run a single pulse and exit}
        {--quiet-pulses : only report when something was repaired}';

    protected $description = 'Self-healing watchdog for the terminal sandbox';

    public function handle(Sentinel $sentinel): int
    {
        $interval = max(1, (int) $this->option('interval'));

        if ($this->option('once')) {
            $result = $sentinel->restore('sentinel');
            $this->line(sprintf(
                '[sentinel] restored · %d anomalies · %d files · %dms',
                $result['anomalies'],
                $result['files'],
                $result['ms']
            ));

            return self::SUCCESS;
        }

        $this->info("sentinel online · pulse every {$interval}s · ctrl-c to stop");

        $running = true;
        if (function_exists('pcntl_signal')) {
            pcntl_async_signals(true);
            pcntl_signal(SIGINT, function () use (&$running) {
                $running = false;
            });
            pcntl_signal(SIGTERM, function () use (&$running) {
                $running = false;
            });
        }

        while ($running) {
            $result = $sentinel->restore('sentinel');
            $repaired = $result['anomalies'] > 0;

            if ($repaired || ! $this->option('quiet-pulses')) {
                $this->line(sprintf(
                    '[%s] %s · %d files · %dms',
                    now()->format('H:i:s'),
                    $repaired ? "repaired {$result['anomalies']} anomalies" : 'integrity ok',
                    $result['files'],
                    $result['ms']
                ));
            }

            sleep($interval);
        }

        $this->info('sentinel offline');

        return self::SUCCESS;
    }
}
