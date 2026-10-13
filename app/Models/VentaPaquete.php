<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VentaPaquete extends Model
{
    protected $table = 'ventas_paquetes';

    protected $primaryKey = 'iID';

    protected $guarded = ['iID'];

    protected $casts = ['dFechaViaje' => 'date', 'cancelada_at' => 'datetime', 'finalizada_at' => 'datetime', 'dPrecioUnitario' => 'decimal:2', 'dTotal' => 'decimal:2'];

    public function cliente()
    {
        return $this->belongsTo(Cliente::class, 'iIDCliente', 'iID');
    }

    public function pagos()
    {
        return $this->hasMany(PagoVenta::class, 'iIDVenta', 'iID');
    }

    public function detalles()
    {
        return $this->hasMany(VentaPaqueteDetalle::class, 'iIDVenta', 'iID');
    }

    public function usuario()
    {
        return $this->belongsTo(User::class, 'iIDUsuario');
    }

    public function getFolioAttribute(): string
    {
        return 'VTA-'.str_pad($this->iID, 6, '0', STR_PAD_LEFT);
    }

    public function getSaldoCentavosAttribute(): int
    {
        return (int) round((float) $this->dTotal * 100) - $this->pagos->whereNull('anulado_at')->sum(fn ($pago) => (int) round((float) $pago->dImporte * 100));
    }

    public function getEstadoAttribute(): string
    {
        return $this->cancelada_at ? 'Cancelada' : ($this->saldo_centavos === 0 ? 'Pagada' : 'Pendiente');
    }

    public function getEstadoApartadoAttribute(): string
    {
        return $this->cancelada_at ? 'Cancelado' : ($this->finalizada_at ? 'Finalizado' : ($this->saldo_centavos === 0 ? 'Liquidado' : 'Abierto'));
    }
}
