<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PruebaSencillaTest extends TestCase
{
    public function test_total_de_una_venta(): void
    {
        // 1. PREPARAR
        $cantidad = 3;
        $precio = 10;

        // 2. EJECUTAR
        $total = $cantidad * $precio;

        // 3. VERIFICAR
        $this->assertEquals(30, $total);
    }
}
