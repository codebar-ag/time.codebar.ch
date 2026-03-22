<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Http\Middleware\VerifyCsrfToken;
use Tests\TestCase;

class HttpMethodOverrideTest extends TestCase
{
    public function test_malicious_method_override_does_not_throw(): void
    {
        $response = $this->withoutMiddleware(VerifyCsrfToken::class)
            ->post('/', ['_method' => 'FOO123']);

        $response->assertStatus(405);
    }
}
