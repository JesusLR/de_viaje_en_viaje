<?php

namespace Tests\Feature;

use App\Http\Controllers\Admin\UsuarioController;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class DeveloperUserTest extends TestCase
{
    use DatabaseTransactions;

    public function test_developer_can_log_in_but_is_excluded_from_user_lists(): void
    {
        $developer = User::where('email', 'usuario.desarrollador')->firstOrFail();
        $this->assertTrue($developer->lDev);
        $this->assertTrue($developer->hasPermission('permisos.gestionar'));
        $this->assertTrue(Validator::make([
            'email' => 'usuario.desarrollador', 'password' => 'test-password',
        ], (new LoginRequest)->rules())->passes());

        $developer->password = 'Developer-test-password';
        $developer->save();
        $this->post('/login', [
            'email' => 'usuario.desarrollador', 'password' => 'Developer-test-password',
        ])->assertRedirect();
        $this->assertAuthenticatedAs($developer);

        $normal = User::factory()->create(['lCambiarPassword' => false]);
        $this->assertFalse($normal->fresh()->lDev);
        $data = app(UsuarioController::class)->gridData(Request::create('/'))->getData(true);
        $ids = array_column($data['cData'], 'iID');
        $this->assertNotContains($developer->id, $ids);
        $this->assertContains($normal->id, $ids);
        $this->get(route('admin.permisos.index'))->assertOk()
            ->assertViewHas('users', fn ($users) => !$users->contains('id', $developer->id));
    }
}
