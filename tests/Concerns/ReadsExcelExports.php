<?php

namespace Tests\Concerns;

use Illuminate\Testing\TestResponse;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

trait ReadsExcelExports
{
    private function leerExcel(TestResponse $respuesta): Spreadsheet
    {
        $ruta = tempnam(sys_get_temp_dir(), 'finanzas-test-');
        try {
            file_put_contents($ruta, $respuesta->streamedContent());

            return IOFactory::load($ruta);
        } finally {
            unlink($ruta);
        }
    }

    private function textoExcel(TestResponse $respuesta): string
    {
        $libro = $this->leerExcel($respuesta);
        try {
            return json_encode($libro->getActiveSheet()->toArray(null, true, false), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        } finally {
            $libro->disconnectWorksheets();
        }
    }
}
