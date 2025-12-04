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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->integer('nivel_jerarquico')->default(5);
            $table->foreignId('padre_id')->nullable()->constrained('roles')->onDelete('set null');
            $table->boolean('activo')->default(true);
            $table->boolean('sistema')->default(false);
            $table->json('configuracion')->nullable();
            $table->text('observaciones')->nullable();
            $table->timestamps();
            $table->softDeletes();
            
            // Índices para mejorar el rendimiento
            $table->index(['activo', 'sistema']);
            $table->index('nivel_jerarquico');
            $table->index('padre_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
