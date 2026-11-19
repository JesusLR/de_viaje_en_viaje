<?php

namespace App\Services;

use App\Models\ServicioTuristico;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OpcionesPrecioService
{
    public function validarPartidas(array $items): void
    {
        $claves = collect($items)->map(fn ($item) => $item['iIDServicio'].':'.($item['opcion_id'] ?? ''));
        if ($claves->unique()->count() !== $claves->count()) {
            throw ValidationException::withMessages(['items' => 'La misma opción está repetida. Ajusta la cantidad en una sola fila.']);
        }
    }

    private function conTarifaMenor(array $opcion, ?ServicioTuristico $servicio): array
    {
        $opcion['lPrecioMenorActivo'] = (bool) ($opcion['lPrecioMenorActivo'] ?? ($opcion['unidad'] === 'PERSONA' && $servicio?->lPrecioMenorActivo));
        $opcion['dPrecioMenor'] = round((float) ($opcion['dPrecioMenor'] ?? $servicio?->dPrecioMenor ?? 0), 2);

        return $opcion;
    }

    public function guardar(Request $request, ?ServicioTuristico $actual): array
    {
        $validado = $request->validate([
            'lOpcionesPrecio' => 'sometimes|boolean',
            'opciones_precio' => 'sometimes|array|max:30',
            'opciones_precio.*.id' => 'nullable|uuid|distinct',
            'opciones_precio.*.nombre' => 'required|string|max:100',
            'opciones_precio.*.descripcion' => 'nullable|string|max:500',
            'opciones_precio.*.cama' => 'nullable|string|max:100',
            'opciones_precio.*.unidad' => 'required|in:HABITACION,PERSONA',
            'opciones_precio.*.adultos' => 'exclude_if:opciones_precio.*.unidad,PERSONA|required|integer|min:1|max:20',
            'opciones_precio.*.menores' => 'exclude_if:opciones_precio.*.unidad,PERSONA|required|integer|min:0|max:20',
            'opciones_precio.*.dPrecioCompra' => 'nullable|numeric|min:0|max:9999999999.99',
            'opciones_precio.*.dPrecioVenta' => 'required|numeric|min:0|max:9999999999.99',
            'opciones_precio.*.lPrecioMenorActivo' => 'sometimes|boolean',
            'opciones_precio.*.dPrecioMenor' => 'nullable|numeric|min:0|max:9999999999.99',
            'opciones_precio.*.activo' => 'required|boolean',
        ]);
        $habilitado = $request->boolean('lOpcionesPrecio', $actual?->lOpcionesPrecio ?? false);
        $originales = collect($actual?->opciones_precio ?? [])->map(fn ($opcion) => $this->conTarifaMenor($opcion, $actual))->all();
        $anteriores = collect($originales)->keyBy('id');
        $opciones = $request->has('opciones_precio') ? collect($validado['opciones_precio'])->map(function ($opcion) use ($request, $anteriores) {
            $id = $opcion['id'] ?? (string) Str::uuid();
            $anterior = $anteriores->get($id);
            $menorActivo = (bool) ($opcion['lPrecioMenorActivo'] ?? $anterior['lPrecioMenorActivo'] ?? false);
            $precioMenor = array_key_exists('dPrecioMenor', $opcion) ? (float) $opcion['dPrecioMenor'] : (float) ($anterior['dPrecioMenor'] ?? 0);
            $costo = (float) ($opcion['dPrecioCompra'] ?? $anteriores->get($id)['dPrecioCompra'] ?? 0);
            if (! $request->user()->hasPermission('servicios.costos.editar')) {
                $original = (float) ($anteriores->get($id)['dPrecioCompra'] ?? 0);
                abort_if($costo !== $original, 403);
                $costo = $original;
            }

            return ['id' => $id, 'nombre' => trim($opcion['nombre']), 'descripcion' => trim($opcion['descripcion'] ?? ''),
                'cama' => trim($opcion['cama'] ?? ''), 'unidad' => $opcion['unidad'], 'adultos' => (int) ($opcion['adultos'] ?? $anterior['adultos'] ?? 1),
                'lPrecioMenorActivo' => $menorActivo, 'dPrecioMenor' => round($precioMenor, 2),
                'menores' => (int) ($opcion['menores'] ?? $anterior['menores'] ?? 0), 'dPrecioCompra' => round($costo, 2),
                'dPrecioVenta' => round((float) $opcion['dPrecioVenta'], 2), 'activo' => (bool) $opcion['activo']];
        })->values()->all() : $originales;
        if (! $request->user()->hasPermission('servicios.precios')) {
            $sinCosto = fn ($items) => collect($items)->map(function ($item) {
                unset($item['dPrecioCompra']);
                ksort($item);

                return $item;
            })->values()->toJson();
            abort_if($habilitado !== (bool) $actual?->lOpcionesPrecio || $sinCosto($opciones) !== $sinCosto($originales), 403);
        }
        if ($habilitado && ! collect($opciones)->contains(fn ($item) => $item['activo'])) {
            throw ValidationException::withMessages(['opciones_precio' => 'Agrega al menos una opción de precio activa.']);
        }
        if (collect($opciones)->contains(fn ($item) => $item['nombre'] === '')) {
            throw ValidationException::withMessages(['opciones_precio' => 'Cada opción debe tener un nombre.']);
        }
        $datos = ['lOpcionesPrecio' => $habilitado, 'opciones_precio' => $opciones];
        if ($habilitado) {
            $minima = collect($opciones)->where('activo', true)->sortBy('dPrecioVenta')->first();
            $datos += ['dPrecioVenta' => $minima['dPrecioVenta'], 'dPrecioCompra' => $minima['dPrecioCompra']];
        }

        return $datos;
    }

    /** Return only public prices, never the agency's costs. */
    public function publicas(ServicioTuristico $servicio, bool $soloActivas = true): array
    {
        if (! $servicio->lOpcionesPrecio) {
            return [];
        }

        return collect($servicio->opciones_precio ?? [])->filter(fn ($opcion) => ! $soloActivas || $opcion['activo'])->map(function ($opcion) use ($servicio) {
            $opcion = $this->conTarifaMenor($opcion, $servicio);
            unset($opcion['dPrecioCompra']);
            $opcion['menoresIncluidos'] = $opcion['unidad'] === 'HABITACION' && ! $opcion['lPrecioMenorActivo'] && $opcion['menores'] > 0;
            if ($opcion['menoresIncluidos']) {
                $opcion['dPrecioMenor'] = 0;
            }
            $precio = (int) round($opcion['dPrecioVenta'] * 100);
            $descuento = ! $servicio->l_promocion_vigente ? 0 : match ($servicio->cTipoDescuento) {
                'PORCENTAJE' => (int) round($precio * $servicio->dValorDescuento / 100),
                'IMPORTE' => (int) round($servicio->dValorDescuento * 100),
                default => 0,
            };
            $opcion['precio'] = max(0, $precio - max(0, $descuento)) / 100;

            return $opcion;
        })->values()->all();
    }

    public function resolver(ServicioTuristico $servicio, ?string $id, int $menores, int $cantidad = 1): ServicioTuristico
    {
        $resultado = clone $servicio;
        $resultado->opcionSeleccionada = null;
        if (! $servicio->lOpcionesPrecio) {
            if ($id) {
                throw ValidationException::withMessages(['items' => 'Este servicio utiliza precio único. Actualiza la página.']);
            }

            return $resultado;
        }
        $opcion = collect($this->publicas($servicio))->firstWhere('id', $id);
        if (! $opcion) {
            throw ValidationException::withMessages(['items' => 'Selecciona una opción de precio activa para '.$servicio->cNombre.'.']);
        }
        if ($opcion['unidad'] === 'HABITACION' && $menores > $cantidad * $opcion['menores']) {
            throw ValidationException::withMessages(['items' => 'La cantidad de menores supera la capacidad de las habitaciones seleccionadas.']);
        }
        $resultado->dPrecioVenta = $opcion['dPrecioVenta'];
        $resultado->dPrecioCompra = collect($servicio->opciones_precio)->firstWhere('id', $id)['dPrecioCompra'];
        $resultado->lPrecioMenorActivo = $opcion['lPrecioMenorActivo'] || $opcion['menoresIncluidos'];
        $resultado->dPrecioMenor = $opcion['dPrecioMenor'];
        $resultado->opcionSeleccionada = $opcion;

        return $resultado;
    }
}
