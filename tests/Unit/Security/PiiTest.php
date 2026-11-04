<?php

namespace Tests\Unit\Security;

use App\Helpers\Pii;
use Tests\TestCase;

class PiiTest extends TestCase
{
    public function test_cifrado_es_reversible_y_no_almacena_texto_plano(): void
    {
        // Arrange
        $ciOriginal = '4839201-LP';

        // Act
        $ciCifrado = Pii::encrypt($ciOriginal);

        // Assert: el cifrado difiere del texto plano y el descifrado lo recupera
        $this->assertNotEquals($ciOriginal, $ciCifrado);
        $this->assertEquals($ciOriginal, Pii::decrypt($ciCifrado));
    }

    public function test_hash_es_determinista_para_busqueda_exacta(): void
    {
        // Act: dos hashes del mismo valor
        $hashA = Pii::hash('4839201-LP');
        $hashB = Pii::hash('4839201-LP');

        // Assert: determinista, distinto del texto plano y sensible al valor
        $this->assertNotNull($hashA);
        $this->assertSame($hashA, $hashB);
        $this->assertNotEquals('4839201-LP', $hashA);
        $this->assertNotSame($hashA, Pii::hash('9999999-SC'));
    }

    public function test_hash_de_valor_nulo_devuelve_nulo(): void
    {
        $this->assertNull(Pii::hash(null));
    }
}
