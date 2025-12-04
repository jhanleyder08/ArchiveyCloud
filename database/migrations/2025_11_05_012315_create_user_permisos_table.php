<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('user_permisos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('permiso_id')->constrained('permisos')->onDelete('cascade');
            $table->datetime('vigencia_desde')->nullable();
            $table->datetime('vigencia_hasta')->nullable();
            $table->boolean('activo')->default(true);
            $table->foreignId('asignado_por')->nullable()->constrained('users')->onDelete('set null');
            $table->text('observaciones')->nullable();
            $table->timestamps();
            
            // Evitar duplicados
            $table->unique(['user_id', 'permiso_id']);
            
            // Índices para mejorar el rendimiento
            $table->index(['user_id', 'permiso_id', 'activo']);
            $table->index('vigencia_desde');
            $table->index('vigencia_hasta');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_permisos');
    }
};
