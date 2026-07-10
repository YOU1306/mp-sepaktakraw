<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_loads(): void
    {
        $this->get('/')->assertOk();
    }

    public function test_login_page_loads(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_register_page_loads(): void
    {
        $this->get('/register')->assertOk();
    }

    public function test_news_listing_loads(): void
    {
        $this->get('/news')->assertOk();
    }
}
