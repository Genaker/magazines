<?php

namespace App\Console\Scheduling;

use Cron\CronExpression;
use Illuminate\Console\Scheduling\Event;
use Illuminate\Console\Scheduling\Schedule;
use InvalidArgumentException;

/** Registers scheduled tasks from config/schedule.php on the Laravel scheduler. */
class ScheduleRegistrar
{
    public function register(Schedule $schedule): void
    {
        if (! config('schedule.enabled', true)) {
            return;
        }

        foreach (config('schedule.tasks', []) as $task) {
            if (! ($task['enabled'] ?? true)) {
                continue;
            }

            $this->registerTask($schedule, $task);
        }
    }

    /** @param  array<string, mixed>  $task */
    private function registerTask(Schedule $schedule, array $task): void
    {
        $parameters = $task['parameters'] ?? [];

        $event = $parameters !== []
            ? $schedule->command($task['command'], $parameters)
            : $schedule->command($task['command']);

        if (isset($task['name'])) {
            $event->name($task['name']);
        }

        $this->applyFrequency($event, $task);

        if (isset($task['when'])) {
            $when = $task['when'];
            $event->when(fn () => $this->passesWhen($when));
        }
    }

    /** @param  array<string, mixed>  $task */
    private function applyFrequency(Event $event, array $task): void
    {
        $frequency = $task['frequency'] ?? 'daily';

        if ($this->isCronExpression($frequency)) {
            $event->cron($frequency);

            return;
        }

        if ($frequency === 'cron') {
            $event->cron($task['expression'] ?? '* * * * *');

            return;
        }

        match ($frequency) {
            'every_minute' => $event->everyMinute(),
            'hourly' => $event->hourly(),
            'daily' => $event->daily(),
            'daily_at' => $event->dailyAt($task['time'] ?? '00:00'),
            'weekly' => $event->weekly(),
            'monthly' => $event->monthly(),
            default => throw new InvalidArgumentException(
                'Unknown schedule frequency: '.$frequency,
            ),
        };
    }

    private function isCronExpression(string $frequency): bool
    {
        return str_contains($frequency, ' ') && CronExpression::isValidExpression($frequency);
    }

    /** @param  array<string, mixed>  $when */
    private function passesWhen(array $when): bool
    {
        if (isset($when['config'])) {
            return config($when['config']) === ($when['equals'] ?? true);
        }

        return true;
    }
}
