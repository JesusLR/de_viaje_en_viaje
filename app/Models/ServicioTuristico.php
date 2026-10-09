<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class ServicioTuristico extends Model
{
    use HasFactory;

    protected $table = 'servicio_turisticos';
    protected $primaryKey = 'iID';

    protected $fillable = [
        'cCodigo',
        'cNombre',
        'cDescripcionCorta',
        'cDescripcionCompleta',
        'iIDCategoria',
        'iIDDestino',
        'iIDProveedor',
        'cImagenPrincipal',
        'dPrecioCompra',
        'dPrecioVenta',
        'cMoneda',
        'lTienePromocion',
        'cNombrePromocion',
        'cDescripcionPromocion',
        'cTipoDescuento',
        'dValorDescuento',
        'dFechaInicioPromocion',
        'dFechaFinPromocion',
        'lPromocionActiva',
        'dFechaInicioVigencia',
        'dFechaFinVigencia',
        'lActivo',
        'iIDUsuario',
    ];

    protected $casts = [
        'lActivo'               => 'boolean',
        'lTienePromocion'       => 'boolean',
        'lPromocionActiva'      => 'boolean',
        'dPrecioCompra'         => 'float',
        'dPrecioVenta'          => 'float',
        'dValorDescuento'       => 'float',
        'dFechaInicioVigencia'  => 'date',
        'dFechaFinVigencia'     => 'date',
        'dFechaInicioPromocion' => 'datetime',
        'dFechaFinPromocion'    => 'datetime',
    ];

    // Relaciones
    public function categoria(): BelongsTo
    {
        return $this->belongsTo(CategoriaTuristica::class, 'iIDCategoria', 'iID');
    }

    public function destino(): BelongsTo
    {
        return $this->belongsTo(DestinoTuristico::class, 'iIDDestino', 'iID');
    }

    public function proveedor(): BelongsTo
    {
        return $this->belongsTo(Proveedor::class, 'iIDProveedor', 'iID');
    }

    public function imagenes(): HasMany
    {
        return $this->hasMany(ServicioImagen::class, 'iIDServicio', 'iID')->orderBy('iOrden', 'asc');
    }

    public function usuario(): BelongsTo
    {
        return $this->belongsTo(User::class, 'iIDUsuario');
    }

    // Accessors / Lógica de Negocio

    /**
     * Determina si la promoción está actualmente vigente.
     */
    public function getLPromocionVigenteAttribute(): bool
    {
        if (!$this->lTienePromocion || !$this->lPromocionActiva) {
            return false;
        }

        if (!$this->dFechaInicioPromocion || !$this->dFechaFinPromocion) {
            return false;
        }

        $now = Carbon::now();
        return $now->between($this->dFechaInicioPromocion, $this->dFechaFinPromocion);
    }

    /**
     * Calcula el monto del descuento aplicado.
     */
    public function getDMontoDescuentoAttribute(): float
    {
        if (!$this->l_promocion_vigente) {
            return 0.00;
        }

        if ($this->cTipoDescuento === 'PORCENTAJE') {
            return round(($this->dPrecioVenta * ($this->dValorDescuento / 100)), 2);
        }

        if ($this->cTipoDescuento === 'IMPORTE') {
            return min($this->dPrecioVenta, max(0, $this->dValorDescuento));
        }

        return 0.00;
    }

    /**
     * Calcula el precio final (aplicando descuento si la promoción está vigente).
     */
    public function getDPrecioFinalAttribute(): float
    {
        return max(0, $this->dPrecioVenta - $this->d_monto_descuento);
    }

    /**
     * Calcula la ganancia estimada sobre precio regular.
     */
    public function getDGananciaRegularAttribute(): float
    {
        return $this->dPrecioVenta - $this->dPrecioCompra;
    }

    public function getDGananciaEstimadaAttribute(): float
    {
        return $this->d_ganancia_regular;
    }

    /**
     * Calcula la ganancia estimada efectiva (considerando precio promocional).
     */
    public function getDGananciaEfectivaAttribute(): float
    {
        return $this->d_precio_final - $this->dPrecioCompra;
    }

    /**
     * Calcula el porcentaje de utilidad sobre el costo.
     */
    public function getDPorcentajeUtilidadAttribute(): float
    {
        if ($this->dPrecioCompra <= 0) {
            return 0.00;
        }
        return round(($this->d_ganancia_efectiva / $this->dPrecioCompra) * 100, 2);
    }

    /**
     * Determina el estatus de disponibilidad efectiva (PROGRAMADO, ACTIVO, VENCIDO, INACTIVO).
     */
    public function getCEstatusDisponibilidadAttribute(): string
    {
        if (!$this->lActivo) {
            return 'INACTIVO';
        }

        $today = Carbon::today();

        if ($today->lt($this->dFechaInicioVigencia)) {
            return 'PROGRAMADO';
        }

        if ($today->gt($this->dFechaFinVigencia)) {
            return 'VENCIDO';
        }

        return 'ACTIVO';
    }
}
