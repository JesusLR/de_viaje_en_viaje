<?php

namespace App\Services;

use App\Models\ServicioTuristico;
use App\Models\ServicioImagen;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Carbon\Carbon;

class ServicioTuristicoService
{
    /**
     * Genera un código interno único correlativo para el servicio (ej. SERV-2026-0001).
     */
    public function generarCodigoUnico(): string
    {
        $iYear = date('Y');
        $cPrefix = "SERV-{$iYear}-";

        $oUltimo = ServicioTuristico::where('cCodigo', 'LIKE', "{$cPrefix}%")
            ->orderBy('iID', 'desc')
            ->first();

        if ($oUltimo) {
            $cNumero = substr($oUltimo->cCodigo, strlen($cPrefix));
            $iConsecutivo = ((int) $cNumero) + 1;
        } else {
            $iConsecutivo = 1;
        }

        return $cPrefix . str_pad($iConsecutivo, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Calcula la simulación de precios, ganancias y descuentos en tiempo real.
     */
    public function calcularSimulacionPrecios(
        float $dPrecioCompra,
        float $dPrecioVenta,
        bool $lTienePromocion = false,
        ?string $cTipoDescuento = null,
        float $dValorDescuento = 0.00,
        ?string $dFechaInicioPromocion = null,
        ?string $dFechaFinPromocion = null
    ): array {
        $dGananciaRegular = max(0, $dPrecioVenta - $dPrecioCompra);
        $dUtilidadRegular = $dPrecioCompra > 0 ? round(($dGananciaRegular / $dPrecioCompra) * 100, 2) : 0.00;

        $dMontoDescuento = 0.00;
        $lPromocionVigente = false;

        if ($lTienePromocion && $cTipoDescuento && $dValorDescuento > 0) {
            if ($dFechaInicioPromocion && $dFechaFinPromocion) {
                $now = Carbon::now();
                $start = Carbon::parse($dFechaInicioPromocion);
                $end = Carbon::parse($dFechaFinPromocion);
                $lPromocionVigente = $now->between($start, $end);
            } else {
                $lPromocionVigente = true;
            }

            if ($cTipoDescuento === 'PORCENTAJE') {
                $dMontoDescuento = round(($dPrecioVenta * ($dValorDescuento / 100)), 2);
            } else if ($cTipoDescuento === 'IMPORTE') {
                $dMontoDescuento = min($dPrecioVenta, max(0, $dValorDescuento));
            }
        }

        $dPrecioFinal = max(0, $dPrecioVenta - $dMontoDescuento);
        $dGananciaEfectiva = $dPrecioFinal - $dPrecioCompra;
        $dUtilidadEfectiva = $dPrecioCompra > 0 ? round(($dGananciaEfectiva / $dPrecioCompra) * 100, 2) : 0.00;

        return [
            'dPrecioCompra'           => round($dPrecioCompra, 2),
            'dPrecioVenta'            => round($dPrecioVenta, 2),
            'dGananciaRegular'        => round($dGananciaRegular, 2),
            'dGananciaNormal'         => round($dGananciaRegular, 2),
            'dUtilidadRegular'        => $dUtilidadRegular,
            'dPorcentajeUtilidad'     => $dUtilidadRegular,
            'lPromocionVigente'       => $lPromocionVigente,
            'dMontoDescuento'         => round($dMontoDescuento, 2),
            'dPrecioFinal'            => round($dPrecioFinal, 2),
            'dPrecioFinalPromocional' => round($dPrecioFinal, 2),
            'dGananciaEfectiva'       => round($dGananciaEfectiva, 2),
            'dUtilidadEfectiva'       => $dUtilidadEfectiva,
        ];
    }

    /**
     * Procesa la carga de una imagen en Laravel Storage.
     */
    public function guardarImagen(UploadedFile $file, string $cDirectory = 'servicios'): string
    {
        $cFileName = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
        $file->storeAs($cDirectory, $cFileName, 'public');
        return "public/{$cDirectory}/{$cFileName}";
    }

    /**
     * Elimina una imagen del almacenamiento físico si existe.
     */
    public function eliminarImagen(?string $cPath): void
    {
        if ($cPath) {
            $relativePath = str_replace('public/', '', $cPath);
            if (Storage::disk('public')->exists($relativePath)) {
                Storage::disk('public')->delete($relativePath);
            }
        }
    }
}
