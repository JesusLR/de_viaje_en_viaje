<?php

namespace Tests\Unit;

use App\Services\FinanzasExcelService;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PHPUnit\Framework\TestCase;

class FinanzasExcelTest extends TestCase
{
    public function test_excel_tiene_estilos_fechas_importes_totales_y_textos_seguros(): void
    {
        $ingreso = (object) ['fecha' => '2026-11-03', 'venta_id' => 15, 'tipo' => 'INGRESO', 'origen' => 'Abono', 'concepto' => '=HYPERLINK("https://example.com")', 'detalle' => 'Cliente de prueba', 'metodo' => 'Transferencia', 'importe' => '1500.50', 'moneda' => 'MXN'];
        $egreso = clone $ingreso;
        $egreso->tipo = 'EGRESO';
        $egreso->origen = 'Manual';
        $egreso->importe = '200.25';
        $libro = (new FinanzasExcelService())->crear([$ingreso, $egreso], '2026-11-01', '2026-11-30', 'MXN', true, true);
        try {
            $hoja = $libro->getActiveSheet();
            $this->assertEquals(1500.50, $hoja->getCell('A7')->getCalculatedValue());
            $this->assertEquals(200.25, $hoja->getCell('D7')->getCalculatedValue());
            $this->assertEquals(1300.25, $hoja->getCell('G7')->getCalculatedValue());
            $this->assertSame(DataType::TYPE_STRING, $hoja->getCell('E13')->getDataType());
            $this->assertSame($ingreso->concepto, $hoja->getCell('E13')->getValue());
            $this->assertSame(DataType::TYPE_NUMERIC, $hoja->getCell('H13')->getDataType());
            $this->assertSame('03/11/2026', $hoja->getCell('A13')->getFormattedValue());
            $this->assertSame('1,500.50', $hoja->getCell('H13')->getFormattedValue());
            $this->assertSame('Abono', $hoja->getCell('C13')->getValue());
            $this->assertSame('VTA-000015', $hoja->getCell('D13')->getValue());
            $this->assertSame('147D55', $hoja->getStyle('A12')->getFill()->getStartColor()->getRGB());
            $this->assertCount(2, $hoja->getTableCollection());
            $rangos = [];
            foreach ($hoja->getTableCollection() as $tabla) {
                $rangos[] = $tabla->getRange();
            }
            $this->assertSame(['A12:I13', 'A18:I19'], $rangos);
            $this->assertSame('B33B44', $hoja->getStyle('A18')->getFill()->getStartColor()->getRGB());
            $this->assertSame('Egreso', $hoja->getCell('B19')->getValue());
            $this->assertSame('A10', $hoja->getFreezePane());
            $this->assertSame(1, $libro->getSheetCount());
            $this->assertFalse($hoja->getShowGridlines());
        } finally {
            $libro->disconnectWorksheets();
        }
    }

    public function test_excel_vacio_no_muestra_errores_ni_inventa_totales_restringidos(): void
    {
        $libro = (new FinanzasExcelService())->crear([], '2026-11-01', '2026-11-30', 'MXN', true, false);
        try {
            $hoja = $libro->getActiveSheet();
            $this->assertEquals(0, $hoja->getCell('A7')->getCalculatedValue());
            $this->assertSame('No incluido', $hoja->getCell('D7')->getValue());
            $this->assertSame('No incluido', $hoja->getCell('G7')->getValue());
            $this->assertSame('Sin ingresos en este periodo.', $hoja->getCell('E13')->getValue());
        } finally {
            $libro->disconnectWorksheets();
        }
    }
}
