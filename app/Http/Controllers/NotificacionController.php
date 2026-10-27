<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class NotificacionController extends Controller
{
    private function visibles(Request $request)
    {
        $query = $request->user()->notifications()->where(function ($query) use ($request) {
            $query->whereNull('data->permiso')->orWhereIn('data->permiso', $request->user()->permissionKeys());
        });
        if (!$request->user()->hasPermission('apartados.ver') || !$request->user()->hasPermission('apartados.recordatorios')) {
            $query->where('type', '!=', \App\Notifications\RecordatorioAbono::class);
        }
        if (!$request->user()->hasPermission('apartados.todos')) {
            $query->where(fn ($q) => $q->whereNull('data->apartado_usuario_id')->orWhere('data->apartado_usuario_id', $request->user()->id));
        }
        return $query;
    }

    public function index(Request $request)
    {
        app(\App\Services\RecordatoriosAbonosService::class)->generarPara($request->user());
        $query = $this->visibles($request);
        $total = (clone $query)->whereNull('read_at')->count();
        $items = $query->orderByDesc('created_at')->orderByDesc('id')->limit(20)->get()->map(function ($notification) {
            $data = $notification->data;
            return [
                'id' => $notification->id,
                'titulo' => $data['titulo'] ?? 'Notificación',
                'mensaje' => $data['mensaje'] ?? '',
                'fecha' => $notification->created_at->format('d/m/Y H:i'),
                'leida' => $notification->read_at !== null,
                'url' => !empty($data['ruta']) && Route::has($data['ruta']) ? route($data['ruta'], $data['parametros'] ?? [], false) : null,
            ];
        });
        return response()->json(['sin_leer' => $total, 'notificaciones' => $items])->header('Cache-Control', 'private, no-store');
    }

    public function leer(Request $request, string $notificacion)
    {
        $notification = $this->visibles($request)->whereKey($notificacion)->firstOrFail();
        $notification->markAsRead();
        return response()->json(['success' => true]);
    }

    public function leerTodas(Request $request)
    {
        $this->visibles($request)->whereNull('read_at')->update(['read_at' => now()]);
        return response()->json(['success' => true]);
    }
}
