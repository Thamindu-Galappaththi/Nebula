<?php

namespace Tests\Feature;

use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class MaintenancePageTest extends TestCase
{
    public function test_maintenance_view_uses_nebula_branding(): void
    {
        $html = view('errors.503')->render();

        $this->assertStringContainsString('maintenance-page', $html);
        $this->assertStringContainsString('We’ll be back soon', $html);
        $this->assertStringContainsString('Nebula Institute of Technology is temporarily unavailable for maintenance.', $html);
        $this->assertStringContainsString('css/login.css', $html);
        $this->assertStringNotContainsString('Service Unavailable', $html);
    }

    public function test_http_503_renders_branded_maintenance_page(): void
    {
        $handler = $this->app->make(ExceptionHandler::class);
        $response = $handler->render(
            Request::create('/', 'GET'),
            new HttpException(503)
        );

        $this->assertSame(503, $response->getStatusCode());
        $this->assertStringContainsString('We’ll be back soon', $response->getContent());
        $this->assertStringContainsString('maintenance-page', $response->getContent());
    }
}
