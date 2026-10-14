<?php

namespace Tests\Feature;

use App\Models\{Role, User, ServicioTuristico, Cliente, VentaPaquete};
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PermissionTest extends TestCase
{
    use DatabaseTransactions;

    private function userWith(array $permissions): User
    {
        $user = User::factory()->create(['lCambiarPassword' => false]);
        $role = Role::create(['name' => 'Prueba', 'slug' => uniqid('prueba'), 'permissions' => $permissions]);
        $user->roles()->sync([$role->id]);
        return $user;
    }

    public function test_administrador_gestiona_roles_asigna_y_registra_auditoria(): void
    {
        $admin = User::factory()->create(['lCambiarPassword' => false]);
        $this->actingAs($admin)->get(route('admin.permisos.index'))->assertOk()->assertSee('Roles y permisos');
        $this->post(route('admin.permisos.store'), ['name' => 'Operador prueba', 'permissions' => ['clientes.crear']])->assertSessionHasNoErrors();
        $role = Role::where('name', 'Operador prueba')->firstOrFail();
        $this->assertContains('clientes.ver', $role->permissions);
        $user = $this->userWith([]);
        $this->put(route('admin.permisos.assign', $user), ['roles' => [$role->id]])->assertSessionHasNoErrors();
        $this->assertTrue($user->fresh()->hasPermission('clientes.crear'));
        $this->put(route('admin.permisos.update', $role), ['name' => 'Operador nuevo', 'permissions' => ['servicios.ver']])->assertSessionHasNoErrors();
        $this->assertFalse($user->fresh()->hasPermission('clientes.crear'));
        $this->assertDatabaseHas('permission_audits', ['actor_id' => $admin->id, 'action' => 'Roles asignados']);
        $this->put(route('admin.permisos.assign', $admin), ['roles' => []])->assertSessionHasErrors('roles');
        $this->delete(route('admin.permisos.destroy', $role))->assertStatus(422);
    }

    public function test_acceso_al_modulo_no_autoriza_mutaciones_ni_gestion_de_permisos(): void
    {
        $user = $this->userWith(['clientes.ver']);
        $this->actingAs($user)->get(route('catalogos.clientes.index'))->assertOk();
        $this->postJson(route('catalogos.clientes.saveData'), ['cNombre' => 'Sin permiso'])->assertForbidden();
        $this->get(route('admin.permisos.index'))->assertForbidden();
        $this->put(route('admin.permisos.assign', $user), ['roles' => [Role::where('slug', 'administrador')->value('id')]])->assertForbidden();
        $this->get(route('perfil.edit'))->assertOk();
        $this->get(route('dashboard'))->assertOk()->assertViewHas('finanzas', fn ($finanzas) => $finanzas->isEmpty());
    }

    public function test_costos_y_utilidad_no_se_exponen_y_no_se_pueden_modificar(): void
    {
        $servicio = ServicioTuristico::create(['cCodigo' => uniqid('PERM'), 'cNombre' => 'Costos protegidos', 'dPrecioCompra' => 700, 'dPrecioVenta' => 1000, 'lUsaVigencia' => false, 'lActivo' => true]);
        $this->actingAs($this->userWith(['servicios.ver', 'servicios.editar']));
        $this->getJson(route('servicios.paquetes.getData', $servicio->iID))->assertOk()->assertJsonMissingPath('cData.dPrecioCompra');
        $this->getJson(route('servicios.paquetes.gridData', ['search' => ['value' => 'Costos protegidos']]))->assertOk()->assertJsonMissingPath('data.0.dPrecioCompra')->assertJsonMissingPath('data.0.dGananciaEfectiva');
        $this->postJson(route('servicios.paquetes.simularPrecios'), ['dPrecioVenta' => 1000])->assertOk()->assertJsonMissingPath('cData.dGananciaEfectiva')->assertJsonMissingPath('cData.dPrecioCompra');
        $data = ['iID' => $servicio->iID, 'cNombre' => $servicio->cNombre, 'dPrecioVenta' => 1000, 'lUsaVigencia' => false];
        $this->postJson(route('servicios.paquetes.saveData'), $data + ['dPrecioCompra' => 1])->assertForbidden();
        $this->postJson(route('servicios.paquetes.saveData'), $data)->assertOk();
        $this->assertEquals(700, $servicio->fresh()->dPrecioCompra);
    }

    public function test_vendedor_solo_consulta_sus_operaciones_y_no_anula_abonos(): void
    {
        $vendedor = $this->userWith(['apartados.ver', 'apartados.abonos', 'apartados.pdf']);
        $cliente = Cliente::create(['cNombre' => 'Cliente', 'cPrimerApellido' => 'Permisos']);
        $servicio = ServicioTuristico::create(['cCodigo' => uniqid('PERM'), 'cNombre' => 'Viaje permiso', 'dPrecioVenta' => 100, 'lUsaVigencia' => false, 'lActivo' => true]);
        $base = ['iIDCliente' => $cliente->iID, 'iIDServicio' => $servicio->iID, 'cPaquete' => 'Viaje permiso', 'cMoneda' => 'MXN', 'iCantidad' => 1, 'dPrecioUnitario' => 100, 'dTotal' => 100, 'cTipoOperacion' => 'APARTADO'];
        $propia = VentaPaquete::create($base + ['iIDUsuario' => $vendedor->id]);
        $ajena = VentaPaquete::create($base + ['iIDUsuario' => User::factory()->create()->id]);
        $this->actingAs($vendedor)->get(route('agencia.apartados.index'))->assertOk()->assertSee($propia->folio)->assertDontSee($ajena->folio);
        $this->get(route('agencia.ventas.show', $ajena))->assertForbidden();
        $this->post(route('agencia.ventas.cancelar', $propia))->assertForbidden();
    }

    public function test_edicion_sin_permiso_de_promociones_conserva_sus_horas_y_audita(): void
    {
        $fin = now()->addWeek()->setTime(23, 59, 59);
        $service = ServicioTuristico::create(['cCodigo' => uniqid('PERM'), 'cNombre' => 'Promo protegida', 'dPrecioCompra' => 50, 'dPrecioVenta' => 100, 'lUsaVigencia' => false, 'lActivo' => true, 'lTienePromocion' => true, 'lPromocionActiva' => true, 'cTipoDescuento' => 'IMPORTE', 'dValorDescuento' => 10, 'dFechaInicioPromocion' => now()->subDay(), 'dFechaFinPromocion' => $fin]);
        $user = $this->userWith(['servicios.ver', 'servicios.editar']);
        $this->actingAs($user)->postJson(route('servicios.paquetes.saveData'), ['iID' => $service->iID, 'cNombre' => 'Nombre actualizado', 'lUsaVigencia' => false])->assertOk();
        $this->assertEquals($fin->toDateTimeString(), $service->fresh()->dFechaFinPromocion->toDateTimeString());
        $this->assertDatabaseHas('permission_audits', ['actor_id' => $user->id, 'action' => 'Servicios y paquetes · saveData']);
    }

    public function test_finanzas_y_exportacion_solo_incluyen_tipos_autorizados(): void
    {
        $this->actingAs($this->userWith(['finanzas.ver', 'finanzas.ingresos', 'finanzas.exportar']));
        foreach (['INGRESO', 'EGRESO'] as $tipo) {
            \App\Models\MovimientoFinanciero::create(['cTipo' => $tipo, 'cConcepto' => 'Permiso '.$tipo, 'dImporte' => 55, 'cMoneda' => 'TST', 'cMetodo' => 'Efectivo', 'dFecha' => today()]);
        }
        $this->get(route('agencia.finanzas.index', ['moneda' => 'TST']))->assertOk()->assertViewHas('egresos', fn ($rows) => $rows->total() === 0)->assertViewHas('totales', fn ($totales) => $totales->egresos == 0 && $totales->ingresos == 55);
        $export = $this->get(route('agencia.finanzas.exportar', ['moneda' => 'TST']))->assertOk();
        $this->assertStringContainsString('Permiso INGRESO', $export->streamedContent());
        $this->assertStringNotContainsString('Permiso EGRESO', $export->streamedContent());
    }

    public function test_dashboard_tiene_permisos_independientes_y_bienvenida_siempre_visible(): void
    {
        $user = $this->userWith(['finanzas.ver', 'finanzas.ingresos', 'servicios.ver']);
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertSee('¡Bienvenido,')
            ->assertSee('Módulos')->assertDontSee('Resumen de la agencia')
            ->assertDontSee('Finanzas del mes')->assertDontSee('Servicios del mes')->assertDontSee('Próximos viajes')->assertDontSee('Accesos rápidos');
        $role = Role::create(['name' => 'Resumen', 'slug' => uniqid('resumen'), 'permissions' => ['dashboard.servicios', 'dashboard.finanzas', 'dashboard.ingresos']]);
        $user->roles()->sync([$role->id]);
        $this->actingAs($user->fresh())->get(route('dashboard'))->assertOk()->assertSee('¡Bienvenido,')
            ->assertDontSee('Módulos')->assertSee('Resumen de la agencia')
            ->assertSee('Servicios del mes')->assertSee('Finanzas del mes')->assertSee('Ingresos')
            ->assertDontSee('Origen de los ingresos')->assertDontSee('Próximos viajes')->assertDontSee('Apartados abiertos');
        $this->get(route('servicios.paquetes.index'))->assertForbidden();
        $this->get(route('agencia.finanzas.index'))->assertForbidden();
    }

    public function test_guardar_permisos_dashboard_no_crea_un_permiso_para_bienvenida(): void
    {
        $this->actingAs(User::factory()->create(['lCambiarPassword' => false]));
        $this->post(route('admin.permisos.store'), ['name' => 'Resumen ingresos', 'permissions' => ['dashboard.origen']])->assertSessionHasNoErrors();
        $permissions = Role::where('name', 'Resumen ingresos')->firstOrFail()->permissions;
        $this->assertContains('dashboard.finanzas', $permissions);
        $this->assertContains('dashboard.ingresos', $permissions);
        $this->assertNotContains('dashboard.ver', $permissions);
        $this->assertNotContains('finanzas.ver', $permissions);
    }

    public function test_sin_permisos_no_muestra_titulos_de_modulos_ni_resumen(): void
    {
        $this->actingAs($this->userWith([]))->get(route('dashboard'))->assertOk()
            ->assertSee('¡Bienvenido,')->assertSee('Dashboard')
            ->assertDontSee('Módulos')->assertDontSee('Resumen de la agencia');
    }
}
