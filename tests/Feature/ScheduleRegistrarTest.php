<?php

namespace Tests\Feature;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class ScheduleRegistrarTest extends TestCase
{
    use RefreshDatabase;

    public function test_schedule_registers_configured_commands(): void
    {
        $commands = $this->scheduledCommands();

        $this->assertContains('subscriptions:send-digest', $commands);
        $this->assertContains('stats:snapshot', $commands);
        $this->assertContains('publications:notify-due', $commands);
    }

    public function test_notification_queue_worker_runs_only_in_queue_mode(): void
    {
        $event = $this->scheduledEvent('queue:work');
        $this->assertNotNull($event);

        Config::set('notifications.email_delivery', 'sync');
        $this->assertFalse($event->filtersPass($this->app));

        Config::set('notifications.email_delivery', 'queue');
        $this->assertTrue($event->filtersPass($this->app));
    }

    public function test_disabled_scheduler_registers_no_tasks(): void
    {
        Config::set('schedule.enabled', false);

        $this->assertSame([], $this->scheduledCommands());
    }

    public function test_disabled_task_is_not_registered(): void
    {
        Config::set('schedule.tasks', [
            [
                'command' => 'stats:snapshot',
                'frequency' => 'daily_at',
                'time' => '01:00',
                'enabled' => false,
            ],
        ]);

        $this->assertNotContains('stats:snapshot', $this->scheduledCommands());
    }

    public function test_cron_notation_can_be_used_as_frequency(): void
    {
        Config::set('schedule.tasks', [
            [
                'command' => 'stats:snapshot',
                'frequency' => '0 8 * * *',
            ],
        ]);

        $event = $this->scheduledEvent('stats:snapshot');
        $this->assertNotNull($event);
        $this->assertSame('0 8 * * *', $event->expression);
    }

    #[DataProvider('cronFrequencyProvider')]
    public function test_cron_notation_registers_valid_expressions(string $expression): void
    {
        Config::set('schedule.tasks', [
            [
                'command' => 'stats:snapshot',
                'frequency' => $expression,
            ],
        ]);

        $event = $this->scheduledEvent('stats:snapshot');
        $this->assertNotNull($event);
        $this->assertSame($expression, $event->expression);
    }

    /** @return array<string, array{string}> */
    public static function cronFrequencyProvider(): array
    {
        return [
            'every minute' => ['* * * * *'],
            'daily at 08:00' => ['0 8 * * *'],
            'every five minutes' => ['*/5 * * * *'],
            'weekdays at noon' => ['0 12 * * 1-5'],
        ];
    }

    public function test_named_frequency_is_not_treated_as_cron_notation(): void
    {
        Config::set('schedule.tasks', [
            [
                'command' => 'stats:snapshot',
                'frequency' => 'every_minute',
            ],
        ]);

        $event = $this->scheduledEvent('stats:snapshot');
        $this->assertNotNull($event);
        $this->assertSame('* * * * *', $event->expression);
    }

    public function test_invalid_frequency_throws(): void
    {
        Config::set('schedule.tasks', [
            [
                'command' => 'stats:snapshot',
                'frequency' => 'not-a-valid-frequency',
            ],
        ]);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown schedule frequency: not-a-valid-frequency');

        $this->freshSchedule();
    }

    public function test_cron_frequency_alias_still_works(): void
    {
        Config::set('schedule.tasks', [
            [
                'command' => 'stats:snapshot',
                'frequency' => 'cron',
                'expression' => '*/5 * * * *',
            ],
        ]);

        $event = $this->scheduledEvent('stats:snapshot');
        $this->assertNotNull($event);
        $this->assertSame('*/5 * * * *', $event->expression);
    }

    /** @return list<string> */
    private function scheduledCommands(): array
    {
        return collect($this->freshSchedule()->events())
            ->map(fn ($event) => $this->normalizeCommand($event->command ?? null))
            ->filter()
            ->values()
            ->all();
    }

    private function scheduledEvent(string $command): ?object
    {
        return collect($this->freshSchedule()->events())
            ->first(fn ($event) => str_contains($event->command ?? '', $command));
    }

    private function normalizeCommand(?string $command): ?string
    {
        if ($command === null) {
            return null;
        }

        foreach (config('schedule.tasks', []) as $task) {
            $name = $task['command'];
            if (str_contains($command, $name)) {
                return $name;
            }
        }

        return $command;
    }

    private function freshSchedule(): Schedule
    {
        $this->app->forgetInstance(Schedule::class);

        $schedule = app(Schedule::class);

        if ($schedule->events() === []) {
            app(\App\Console\Scheduling\ScheduleRegistrar::class)->register($schedule);
        }

        return $schedule;
    }
}
