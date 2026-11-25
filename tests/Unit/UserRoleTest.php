<?php

namespace Tests\Unit;

use App\Models\Role;
use App\Models\User;
use PHPUnit\Framework\TestCase;

/** Ejemplo de prueba unitaria: revisa User::hasRole sin usar la base de datos. */
class UserRoleTest extends TestCase
{
    private function usuarioConRol(string $nombre): User
    {
        $rol = new Role();
        $rol->name = $nombre;
        $usuario = new User();
        $usuario->setRelation('roles', collect([$rol])); // roles en memoria, sin MySQL
        return $usuario;
    }

    public function test_vendedor_tiene_rol_vendedor(): void
    {
        // 1. PREPARAR
        $usuario = $this->usuarioConRol('vendedor');
        // 2. EJECUTAR
        $resultado = $usuario->hasRole(['vendedor']);
        // 3. VERIFICAR
        $this->assertTrue($resultado);
    }

    public function test_vendedor_no_es_administrador(): void
    {
        $usuario = $this->usuarioConRol('vendedor');
        $this->assertFalse($usuario->hasRole(['administrador']));
    }

    public function test_acepta_si_tiene_alguno_de_varios_roles(): void
    {
        $usuario = $this->usuarioConRol('distribuidor');
        $this->assertTrue($usuario->hasRole(['administrador', 'distribuidor']));
    }
}
