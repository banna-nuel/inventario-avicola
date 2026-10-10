<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    public function test_la_raiz_redirige_a_home(): void
    {
        $this->get('/')->assertRedirect('/home');
    }
}
