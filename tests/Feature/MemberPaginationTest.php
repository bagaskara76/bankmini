<?php

namespace Tests\Feature;

use App\Models\PendingMember;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class MemberPaginationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_member_list_displays_25_names_per_page_and_keeps_search_when_paging(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        foreach (range(1, 27) as $number) {
            PendingMember::create([
                'name' => sprintf('Anggota %02d', $number),
                'email' => null,
                'grade' => 10,
                'kelas' => 'RPL 1',
            ]);
        }

        $firstPage = $this->actingAs($admin)
            ->get(route('admin.members.index', ['search' => 'Anggota']))
            ->assertOk()
            ->assertSee('Menampilkan 1')
            ->assertSee('dari 27 nama')
            ->assertSee('Anggota 01')
            ->assertSee('Anggota 25')
            ->assertDontSee('Anggota 26')
            ->assertSee('search=Anggota', false)
            ->assertSee('page=2', false);

        $this->assertSame(25, substr_count($firstPage->getContent(), 'Belum dibuatkan akun'));

        $secondPage = $this->get(route('admin.members.index', ['search' => 'Anggota', 'page' => 2]))
            ->assertOk()
            ->assertSee('Menampilkan 26')
        ->assertSee('dari 27 nama')
            ->assertSee('Anggota 26')
            ->assertSee('Anggota 27')
            ->assertDontSee('Anggota 25')
            ->assertSee('search=Anggota', false);

        $this->assertSame(2, substr_count($secondPage->getContent(), 'Belum dibuatkan akun'));
    }
}
