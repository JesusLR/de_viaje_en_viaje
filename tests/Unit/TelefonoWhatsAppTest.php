<?php

namespace Tests\Unit;

use App\Support\TelefonoWhatsApp;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class TelefonoWhatsAppTest extends TestCase
{
    public static function numeros(): array
    {
        return [
            ['9991234567', '529991234567'], ['+52 999 123 4567', '529991234567'],
            ['52 (999) 123-4567', '529991234567'], ['0052 999 123 4567', '529991234567'],
            ['+52 1 999 123 4567', '529991234567'], ['5219991234567', '529991234567'],
            ['+1 (212) 555-1234', '12125551234'], ['+34 612 345 678', '34612345678'],
        ];
    }

    #[DataProvider('numeros')]
    public function test_normaliza_sin_duplicar_el_codigo_de_pais(string $entrada, string $esperado): void
    {
        $this->assertSame($esperado, TelefonoWhatsApp::normalizar($entrada));
    }

    public function test_rechaza_numeros_incompletos_grupos_extensiones_y_prefijos_duplicados(): void
    {
        foreach (['', '999123', '+52 52 999 123 4567', '9991234567 ext 1', '123@g.us', '000', '12125551234', '+52 999123456'] as $telefono) {
            try { TelefonoWhatsApp::normalizar($telefono); $this->fail('Se aceptó un teléfono inválido.'); }
            catch (ValidationException $exception) { $this->assertArrayHasKey('telefono', $exception->errors()); }
        }
    }
}
