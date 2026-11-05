<?php

namespace App\Http\Controllers\Mensajeria;

use App\Exceptions\OpenWaException;
use App\Http\Controllers\Controller;
use App\Services\OpenWaService;
use Illuminate\Http\Request;

class SesionController extends Controller
{
    public function __construct(private readonly OpenWaService $openwa) {}

    public function index()
    {
        return view('mensajeria.sesiones', ['configurado' => $this->openwa->configurado()]);
    }

    private function responder(callable $accion, int $status = 200)
    {
        try {
            return response()->json(['lSuccess' => true, 'datos' => $accion()], $status)->header('Cache-Control', 'private, no-store');
        } catch (OpenWaException $exception) {
            return response()->json(['lSuccess' => false, 'mensaje' => $exception->getMessage()], $exception->status)->header('Cache-Control', 'private, no-store');
        }
    }

    public function datos()
    {
        return $this->responder(fn () => $this->openwa->sesiones());
    }

    public function store(Request $request)
    {
        $datos = $request->validate(['nombre' => 'required|string|min:3|max:50|regex:/\A[a-zA-Z0-9-]+\z/'], [
            'nombre.regex' => 'El nombre solo puede contener letras sin acentos, números y guiones.',
        ]);

        return $this->responder(fn () => $this->openwa->crear($datos['nombre']), 201);
    }

    public function iniciar(string $sesion)
    {
        return $this->responder(fn () => $this->openwa->iniciar($sesion));
    }

    public function qr(string $sesion)
    {
        return $this->responder(fn () => $this->openwa->qr($sesion));
    }

    public function cerrar(string $sesion)
    {
        return $this->responder(fn () => $this->openwa->cerrar($sesion));
    }

    public function destroy(string $sesion)
    {
        return $this->responder(function () use ($sesion) {
            $this->openwa->eliminar($sesion);
            return null;
        });
    }
}
