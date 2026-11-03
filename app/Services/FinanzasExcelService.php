<?php

namespace App\Services;

use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Worksheet\Table;
use PhpOffice\PhpSpreadsheet\Worksheet\Table\TableStyle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class FinanzasExcelService
{
    private const IMPORTE = '#,##0.00;[Red](#,##0.00);0.00';

    public function crear(iterable $movimientos, string $desde, string $hasta, string $moneda, bool $verIngresos, bool $verEgresos): Spreadsheet
    {
        $libro = new Spreadsheet();
        $libro->getProperties()->setCreator('De Viaje en Viaje')->setTitle('Reporte de finanzas');
        $libro->getDefaultStyle()->getFont()->setName('Arial')->setSize(10)->getColor()->setRGB('253347');
        $hoja = $libro->getActiveSheet()->setTitle('Finanzas');
        $hoja->setShowGridlines(false);
        $hoja->getTabColor()->setRGB('243654');
        $hoja->mergeCells('A2:I2')->setCellValue('A2', 'DE VIAJE EN VIAJE');
        $hoja->getStyle('A2')->getFont()->setBold(true)->getColor()->setRGB('6B778B');
        $hoja->mergeCells('A3:I3')->setCellValue('A3', 'Reporte de finanzas');
        $hoja->getStyle('A3')->getFont()->setSize(16)->setBold(true);
        $hoja->getRowDimension(3)->setRowHeight(30);
        $hoja->mergeCells('A4:I4')->setCellValueExplicit('A4', 'Periodo: '.Carbon::parse($desde)->format('d/m/Y').' al '.Carbon::parse($hasta)->format('d/m/Y').' · Moneda: '.$moneda, DataType::TYPE_STRING);
        $hoja->getStyle('A4:I4')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('CCD4E0');
        foreach ([['A', 'C', 'Ingresos', '147D55', 'EDF8F2'], ['D', 'F', 'Egresos', 'B33B44', 'FDF0F1'], ['G', 'I', 'Balance', '243654', 'EFF3F8']] as [$inicio, $fin, $titulo, $color, $fondo]) {
            $hoja->mergeCells($inicio.'6:'.$fin.'6')->setCellValue($inicio.'6', $titulo);
            $hoja->mergeCells($inicio.'7:'.$fin.'7');
            $hoja->getStyle($inicio.'6:'.$fin.'7')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($fondo);
            $hoja->getStyle($inicio.'6:'.$fin.'7')->getFont()->getColor()->setRGB($color);
            $hoja->getStyle($inicio.'6:'.$fin.'7')->getAlignment()->setIndent(1)->setHorizontal(Alignment::HORIZONTAL_LEFT);
            $hoja->getStyle($inicio.'7')->getFont()->setSize(14)->setBold(true);
            $hoja->getStyle($inicio.'7')->getNumberFormat()->setFormatCode(self::IMPORTE);
        }
        $hoja->getRowDimension(6)->setRowHeight(24);
        $hoja->getRowDimension(7)->setRowHeight(32);
        $hoja->mergeCells('A9:I9')->setCellValue('A9', 'Los importes corresponden al periodo, moneda y permisos seleccionados.');
        $hoja->getStyle('A9')->getFont()->setItalic(true)->getColor()->setRGB('6B778B');

        $grupos = ['INGRESO' => [], 'EGRESO' => []];
        foreach ($movimientos as $movimiento) {
            if (($movimiento->tipo === 'INGRESO' && $verIngresos) || ($movimiento->tipo === 'EGRESO' && $verEgresos)) {
                $grupos[$movimiento->tipo][] = $movimiento;
            }
        }
        $fila = 11;
        foreach (['INGRESO' => $verIngresos, 'EGRESO' => $verEgresos] as $tipo => $permitido) {
            $celdaResumen = $tipo === 'INGRESO' ? 'A7' : 'D7';
            if (!$permitido) {
                $hoja->setCellValue($celdaResumen, 'No incluido');
                continue;
            }
            $total = $this->bloque($hoja, $grupos[$tipo], $tipo, $fila);
            $hoja->setCellValue($celdaResumen, '=H'.$total);
            $fila = $total + 3;
        }
        $hoja->setCellValue('G7', $verIngresos && $verEgresos ? '=A7-D7' : 'No incluido');
        $ultima = max(11, $fila - 3);
        $hoja->getStyle('A1:I'.$ultima)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        foreach (['A' => 14, 'B' => 13, 'C' => 13, 'D' => 17, 'E' => 36, 'F' => 42, 'G' => 19, 'H' => 18, 'I' => 11] as $columna => $ancho) {
            $hoja->getColumnDimension($columna)->setWidth($ancho);
        }
        $hoja->freezePane('A10');
        $hoja->setSelectedCell('A1');
        $hoja->getPageSetup()->setOrientation(PageSetup::ORIENTATION_LANDSCAPE)->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setFitToWidth(1)->setFitToHeight(0)->setPrintArea('A1:I'.$ultima);
        $hoja->getHeaderFooter()->setOddFooter('&LDe Viaje en Viaje&R&P / &N');

        return $libro;
    }

    private function bloque(Worksheet $hoja, array $movimientos, string $tipo, int $inicio): int
    {
        $ingreso = $tipo === 'INGRESO';
        $titulo = $ingreso ? 'Ingresos' : 'Egresos';
        $color = $ingreso ? '147D55' : 'B33B44';
        $fondo = $ingreso ? 'EDF8F2' : 'FDF0F1';
        $cabecera = $inicio + 1;
        $primera = $inicio + 2;
        $hoja->mergeCells('A'.$inicio.':I'.$inicio)->setCellValue('A'.$inicio, mb_strtoupper($titulo));
        $hoja->getRowDimension($inicio)->setRowHeight(28);
        $hoja->getStyle('A'.$inicio.':I'.$inicio)->getFont()->setBold(true)->getColor()->setRGB($color);
        $hoja->getStyle('A'.$inicio.':I'.$inicio)->getBorders()->getBottom()->setBorderStyle(Border::BORDER_MEDIUM)->getColor()->setRGB($color);
        $hoja->fromArray(['Fecha', 'Tipo', 'Origen', 'Folio', 'Concepto', 'Detalle', 'Método', 'Importe', 'Moneda'], null, 'A'.$cabecera);
        $hoja->getStyle('A'.$cabecera.':I'.$cabecera)->applyFromArray([
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $color]],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
            'borders' => ['inside' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'FFFFFF']]],
        ]);
        $hoja->getRowDimension($cabecera)->setRowHeight(27);
        $fila = $primera;
        foreach ($movimientos as $movimiento) {
            $hoja->setCellValue('A'.$fila, Date::PHPToExcel(Carbon::parse($movimiento->fecha)));
            $folio = $movimiento->venta_id ? 'VTA-'.str_pad($movimiento->venta_id, 6, '0', STR_PAD_LEFT) : '';
            foreach (['B' => $ingreso ? 'Ingreso' : 'Egreso', 'C' => $movimiento->origen, 'D' => $folio, 'E' => $movimiento->concepto, 'F' => $movimiento->detalle, 'G' => $movimiento->metodo, 'I' => $movimiento->moneda] as $columna => $valor) {
                // Los textos capturados nunca se interpretan como fórmulas.
                $hoja->setCellValueExplicit($columna.$fila, (string) $valor, DataType::TYPE_STRING);
            }
            $hoja->setCellValue('H'.$fila, (float) $movimiento->importe);
            if (($fila - $primera) % 2 === 0) {
                $hoja->getStyle('A'.$fila.':I'.$fila)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($fondo);
            }
            $lineas = max(1, (int) ceil(mb_strlen((string) $movimiento->concepto) / 32), (int) ceil(mb_strlen((string) $movimiento->detalle) / 38));
            $hoja->getRowDimension($fila)->setRowHeight(min(409, max(26, $lineas * 15)));
            $fila++;
        }
        if ($fila === $primera) {
            $hoja->setCellValue('E'.$fila, 'Sin '.mb_strtolower($titulo).' en este periodo.');
            $hoja->getRowDimension($fila)->setRowHeight(26);
            $fila++;
        } else {
            // Cada bloque conserva sus propios filtros dentro de la misma hoja.
            $tabla = new Table('A'.$cabecera.':I'.($fila - 1), $titulo);
            $tabla->setStyle((new TableStyle())->setTheme('TableStyleLight1')->setShowRowStripes(false));
            $hoja->addTable($tabla);
        }
        $ultima = $fila - 1;
        $hoja->getStyle('A'.$primera.':A'.$ultima)->getNumberFormat()->setFormatCode('dd/mm/yyyy');
        $hoja->getStyle('A'.$primera.':A'.$ultima)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $hoja->getStyle('I'.$primera.':I'.$ultima)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $hoja->getStyle('B'.$primera.':H'.$ultima)->getAlignment()->setIndent(1);
        $hoja->getStyle('E'.$primera.':F'.$ultima)->getAlignment()->setWrapText(true);
        $hoja->getStyle('H'.$primera.':H'.$fila)->getNumberFormat()->setFormatCode(self::IMPORTE);
        $hoja->getStyle('H'.$primera.':H'.$fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT)->setIndent(1);
        $hoja->getStyle('H'.$primera.':H'.$fila)->getFont()->getColor()->setRGB($color);
        $hoja->mergeCells('A'.$fila.':G'.$fila)->setCellValue('A'.$fila, 'Total de '.mb_strtolower($titulo));
        $hoja->setCellValue('H'.$fila, '=SUM(H'.$primera.':H'.$ultima.')');
        $hoja->getStyle('A'.$fila.':I'.$fila)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB($fondo);
        $hoja->getStyle('A'.$fila.':I'.$fila)->getFont()->setBold(true)->getColor()->setRGB($color);
        $hoja->getStyle('A'.$fila.':I'.$fila)->getBorders()->getTop()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB($color);
        $hoja->getRowDimension($fila)->setRowHeight(30);

        return $fila;
    }
}
