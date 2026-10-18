<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Índice por fecha en los movimientos (Fase 9). Lo usan las consultas más frecuentes:
 * el historial (filtra y ordena por fecha), "movimientos de hoy" del Dashboard y el
 * reporte "Más movidos" (rango de fechas). producto_id ya tiene índice por su llave foránea.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->index('fecha');
        });
    }

    public function down(): void
    {
        Schema::table('movimientos_inventario', function (Blueprint $table) {
            $table->dropIndex(['fecha']);
        });
    }
};
