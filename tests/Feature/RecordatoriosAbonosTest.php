<?php

namespace Tests\Feature;

use App\Models\{CategoriaTuristica, Cliente, DestinoTuristico, Role, ServicioTuristico, User, VariableSistema, VentaPaquete};
use App\Notifications\RecordatorioAbono;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class RecordatoriosAbonosTest extends TestCase
{
    use DatabaseTransactions;

    private function usuario(): User
    {
        $user = User::factory()->create(['lCambiarPassword' => false]);
        $role = Role::create(['name' => 'Avisos propios', 'slug' => uniqid('avisos'), 'permissions' => ['apartados.ver', 'apartados.recordatorios']]);
        $user->roles()->sync([$role->id]);
        return $user;
    }

    private function apartado(User $user, array $extra = [], ?int $importe = 100): VentaPaquete
    {
        $categoria = CategoriaTuristica::create(['cNombre' => uniqid('Recordatorio')]);
        $destino = DestinoTuristico::create(['cNombre' => uniqid('Destino')]);
        $servicio = ServicioTuristico::create(['cCodigo' => uniqid('REC'), 'cNombre' => 'Servicio de prueba', 'iIDCategoria' => $categoria->iID,
            'iIDDestino' => $destino->iID, 'lActivo' => true, 'lUsaVigencia' => false, 'cMoneda' => 'MXN', 'dPrecioVenta' => 1000]);
        $cliente = Cliente::create(['cNombre' => 'Cliente', 'cPrimerApellido' => 'Recordatorio', 'lActivo' => true]);
        $venta = VentaPaquete::create(array_merge(['iIDCliente' => $cliente->iID, 'iIDUsuario' => $user->id, 'iIDServicio' => $servicio->iID,
            'cPaquete' => $servicio->cNombre, 'cMoneda' => 'MXN', 'iCantidad' => 1, 'dPrecioUnitario' => 1000, 'dTotal' => 1000,
            'dFechaViaje' => '2026-12-01', 'cTipoOperacion' => 'APARTADO'], $extra));
        if ($importe !== null) $venta->pagos()->create(['dImporte' => $importe, 'dFechaPago' => today()->subDays(10), 'cMetodo' => 'Efectivo', 'iIDUsuario' => $user->id]);
        return $venta;
    }

    private function configurar(): void
    {
        VariableSistema::updateOrCreate(['clave' => 'dias_sin_abonos'], ['valor' => 10]);
    }

    public function test_configuration_has_separate_read_and_write_permissions_and_validates_days(): void
    {
        $user = $this->usuario(); $this->actingAs($user);
        $this->get(route('configuracion.variables.edit'))->assertForbidden();
        $role = $user->roles()->first(); $role->update(['permissions' => ['variables_entorno.ver']]); $user->unsetRelation('roles');
        $this->get(route('configuracion.variables.edit'))->assertOk()->assertSee('Variables de entorno')->assertDontSee('Guardar cambios');
        $this->putJson(route('configuracion.variables.update'), ['dias_sin_abonos' => 10])->assertForbidden();
        $role->update(['permissions' => ['variables_entorno.ver', 'variables_entorno.editar']]); $user->unsetRelation('roles');
        $this->putJson(route('configuracion.variables.update'), ['dias_sin_abonos' => 0])->assertUnprocessable();
        $this->putJson(route('configuracion.variables.update'), ['dias_sin_abonos' => 2.5])->assertUnprocessable();
        $this->put(route('configuracion.variables.update'), ['dias_sin_abonos' => 15])->assertSessionHasNoErrors();
        $this->assertEquals(15, VariableSistema::diasSinAbonos());
        $this->assertEquals($user->id, VariableSistema::find('dias_sin_abonos')->actualizada_por);
    }

    public function test_overdue_alert_is_not_duplicated_and_new_payment_starts_new_period(): void
    {
        $this->configurar(); $user = $this->usuario(); $venta = $this->apartado($user); $this->actingAs($user);
        $this->getJson(route('notificaciones.index'))->assertOk()->assertJsonPath('sin_leer', 1)->assertJsonPath('notificaciones.0.titulo', 'Apartado sin abonos recientes');
        $this->getJson(route('notificaciones.index'))->assertJsonPath('sin_leer', 1);
        $this->assertEquals(1, $user->notifications()->where('type', RecordatorioAbono::class)->count());
        $id = $user->notifications()->first()->id;
        $this->postJson(route('notificaciones.leer', $id))->assertOk();
        $this->getJson(route('notificaciones.index'))->assertJsonPath('sin_leer', 0);
        $venta->pagos()->create(['dImporte' => 100, 'dFechaPago' => today(), 'cMetodo' => 'Tarjeta']);
        $this->getJson(route('notificaciones.index'))->assertJsonPath('sin_leer', 0);
        $this->travel(10)->days();
        $this->getJson(route('notificaciones.index'))->assertJsonPath('sin_leer', 1);
        $this->assertEquals(2, $user->notifications()->where('type', RecordatorioAbono::class)->count());
    }

    public function test_excludes_closed_paid_recent_empty_and_voided_payments_and_marks_resolved_alerts_read(): void
    {
        $this->configurar(); $user = $this->usuario(); $this->actingAs($user);
        $abierto = $this->apartado($user);
        $this->apartado($user, ['cancelada_at' => now()]);
        $this->apartado($user, ['finalizada_at' => now()]);
        $this->apartado($user, ['eliminada_at' => now()]);
        $this->apartado($user, [], 1000);
        $this->apartado($user, [], null);
        $anulado = $this->apartado($user); $anulado->pagos()->update(['anulado_at' => now()]);
        $reciente = $this->apartado($user); $reciente->pagos()->update(['dFechaPago' => today()->subDays(9)]);
        $this->getJson(route('notificaciones.index'))->assertJsonPath('sin_leer', 1);
        $abierto->pagos()->create(['dImporte' => 100, 'dFechaPago' => today(), 'cMetodo' => 'Efectivo']);
        $this->getJson(route('notificaciones.index'))->assertJsonPath('sin_leer', 0);
        $this->assertEquals(1, $user->notifications()->whereNotNull('read_at')->count());
    }

    public function test_alert_visibility_respects_ownership_all_permission_and_revocation(): void
    {
        $this->configurar(); $user = $this->usuario(); $other = $this->usuario();
        $this->apartado($user); $ajeno = $this->apartado($other); $this->actingAs($user);
        $this->getJson(route('notificaciones.index'))->assertJsonPath('sin_leer', 1);
        $role = $user->roles()->first(); $role->update(['permissions' => ['apartados.ver', 'apartados.recordatorios', 'apartados.todos']]); $user->unsetRelation('roles');
        $this->getJson(route('notificaciones.index'))->assertJsonPath('sin_leer', 2);
        $notificacion = $user->notifications()->where('data->venta_id', $ajeno->iID)->firstOrFail();
        $role->update(['permissions' => ['apartados.ver', 'apartados.recordatorios']]); $user->unsetRelation('roles');
        $this->getJson(route('notificaciones.index'))->assertJsonPath('sin_leer', 1)->assertJsonCount(1, 'notificaciones');
        $this->postJson(route('notificaciones.leer', $notificacion->id))->assertNotFound();
        $role->update(['permissions' => ['apartados.ver']]); $user->unsetRelation('roles');
        $this->getJson(route('notificaciones.index'))->assertJsonPath('sin_leer', 0)->assertJsonCount(0, 'notificaciones');
    }
}
