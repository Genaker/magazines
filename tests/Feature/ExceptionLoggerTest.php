<?php

namespace Tests\Feature;

use App\Support\ExceptionLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use RuntimeException;
use Tests\TestCase;

class ExceptionLoggerTest extends TestCase
{
    use RefreshDatabase;

    public function test_exception_logger_writes_structured_error_log(): void
    {
        $logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$logged): void {
            $logged[] = $event;
        });

        ExceptionLogger::log(
            new RuntimeException('Something broke'),
            'Operation failed',
            ['component' => 'test'],
        );

        $this->assertCount(1, $logged);
        $this->assertSame('error', $logged[0]->level);
        $this->assertSame('Operation failed', $logged[0]->message);
        $this->assertSame(RuntimeException::class, $logged[0]->context['exception']);
        $this->assertSame('Something broke', $logged[0]->context['exception_message']);
        $this->assertSame('test', $logged[0]->context['component']);
    }

    public function test_exception_logger_can_be_disabled_via_config(): void
    {
        config(['logging.exception_context.enabled' => false]);

        $logged = [];
        Event::listen(MessageLogged::class, function (MessageLogged $event) use (&$logged): void {
            $logged[] = $event;
        });

        ExceptionLogger::log(new RuntimeException('Silent'), 'Should not log');

        $this->assertSame([], $logged);
    }
}
