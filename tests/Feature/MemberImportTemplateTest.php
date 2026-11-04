<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class MemberImportTemplateTest extends TestCase
{
    public function test_admin_can_download_the_member_import_template(): void
    {
        $admin = User::factory()->make(['role' => 'admin']);

        $response = $this->actingAs($admin)->get(route('admin.members.template'));

        $response->assertOk()
            ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
            ->assertHeader('content-disposition', 'attachment; filename="template-anggota.xlsx"');
        $this->assertStringStartsWith('PK', $response->getContent());
    }
}
