<?php

namespace Tests\Feature;

use Tests\TestCase;

class LandingPageTest extends TestCase
{
    public function test_home_page_displays_the_bank_mini_landing_page(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Apa itu Bank Mini?')
            ->assertSee('Layanan yang membantu kegiatan Bank Mini')
            ->assertSee('>Login <span', false)
            ->assertSee('Download Aplikasi Android')
            ->assertSee(asset('app-release.apk'), false)
            ->assertSee(route('login'), false);
    }

    public function test_login_page_remains_available_from_the_landing_page(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertSee('Silakan login untuk mengakses sistem')
            ->assertSee('Kembali ke halaman utama')
            ->assertSee(route('home'), false);
    }
}
