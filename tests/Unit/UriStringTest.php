<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Tests\TestCase;

class UriStringTest extends TestCase
{
    public function test_uri_string_returns_path_without_query_string(): void
    {
        $this->app->instance('request', Request::create('http://localhost/admin/presensi?tab=1'));

        $this->assertSame('admin/presensi', uri_string());
    }

    public function test_uri_string_returns_slash_for_root(): void
    {
        $this->app->instance('request', Request::create('http://localhost/'));

        $this->assertSame('/', uri_string());
    }
}
