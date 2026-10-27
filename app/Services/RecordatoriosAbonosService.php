<?php

namespace App\Services;

use App\Models\{User, VariableSistema};
use App\Notifications\RecordatorioAbono;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Ramsey\Uuid\Uuid;

class RecordatoriosAbonosService
{
    public function generarPara(User $usuario): void
    {
        if (!$usuario->hasPermission('apartados.ver') || !$usuario->hasPermission('apartados.recordatorios')) return;
        $dias = VariableSistema::diasSinAbonos();
        if (!$dias || $dias < 1) return;
        $pagos = DB::table('pagos_ventas')->whereNull('anulado_at')->groupBy('iIDVenta')
            ->selectRaw('iIDVenta, sum(dImporte) abonado, max(coalesce(dFechaPago, date(created_at))) ultima_fecha, max(iID) ultimo_id');
        $apartados = DB::table('ventas_paquetes as v')->joinSub($pagos, 'p', 'p.iIDVenta', '=', 'v.iID')
            ->join('clientes as c', 'c.iID', '=', 'v.iIDCliente')
            ->where('v.cTipoOperacion', 'APARTADO')->whereNull('v.cancelada_at')->whereNull('v.finalizada_at')->whereNull('v.eliminada_at')
            ->whereColumn('v.dTotal', '>', 'p.abonado')->where('p.ultima_fecha', '<=', today()->subDays($dias)->toDateString())
            ->when(!$usuario->hasPermission('apartados.todos'), fn ($q) => $q->where('v.iIDUsuario', $usuario->id))
            ->select('v.iID', 'v.iIDUsuario', 'p.ultima_fecha', 'p.ultimo_id', 'p.abonado')->selectRaw("concat_ws(' ', c.cNombre, c.cPrimerApellido, c.cSegundoApellido) cliente")->get();
        $vigentes = [];
        foreach ($apartados as $apartado) {
            $id = (string) Uuid::uuid5(Uuid::NAMESPACE_URL, 'recordatorio-abono:'.$usuario->id.':'.$apartado->iID.':'.$apartado->ultimo_id.':'.$apartado->ultima_fecha.':'.$apartado->abonado);
            $vigentes[] = $id;
            $notificacion = new RecordatorioAbono($apartado, (int) Carbon::parse($apartado->ultima_fecha)->startOfDay()->diffInDays(today()));
            // A deterministic primary key makes repeated polls and concurrent requests idempotent.
            DB::table('notifications')->insertOrIgnore([
                'id' => $id, 'type' => RecordatorioAbono::class, 'notifiable_type' => $usuario->getMorphClass(), 'notifiable_id' => $usuario->id,
                'data' => json_encode($notificacion->toArray($usuario), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                'read_at' => null, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        // Keep old alerts as read history when a new payment arrives or the operation closes.
        $usuario->notifications()->where('type', RecordatorioAbono::class)->whereNull('read_at')->whereNotIn('id', $vigentes)->update(['read_at' => now()]);
    }
}
