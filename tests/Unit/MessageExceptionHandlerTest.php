<?php

namespace Tests\Unit;

use App\Support\MessageExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class MessageExceptionHandlerTest extends TestCase
{
    public function test_converts_422_with_message_to_redirect_banner(): void
    {
        $request = Request::create('/magazine/test/join-request', 'POST');
        $request->setLaravelSession($this->app['session.store']);

        $response = MessageExceptionHandler::renderResponse(
            new HttpException(422, 'You are already a member.'),
            $request,
        );

        $this->assertNotNull($response);
        $this->assertTrue($response->isRedirect());
    }

    public function test_leaves_404_for_default_error_handling(): void
    {
        $request = Request::create('/missing', 'GET');

        $this->assertNull(MessageExceptionHandler::renderResponse(
            new NotFoundHttpException(),
            $request,
        ));
    }

    public function test_leaves_422_without_message_for_default_handling(): void
    {
        $request = Request::create('/test', 'POST');

        $this->assertNull(MessageExceptionHandler::renderResponse(
            new HttpException(422),
            $request,
        ));
    }
}
