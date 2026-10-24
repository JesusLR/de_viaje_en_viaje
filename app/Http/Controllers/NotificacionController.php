<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

class NotificacionController extends Controller
{
    private function visibles(Request $request)
    {
        return $request->user()->notifications()->where(function ($query) use ($request) {
            $query->whereNull('data->permiso')->orWhereIn('data->permiso', $request->user()->permissionKeys());
        });
    }

    public function index(Request $request)
    {
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
