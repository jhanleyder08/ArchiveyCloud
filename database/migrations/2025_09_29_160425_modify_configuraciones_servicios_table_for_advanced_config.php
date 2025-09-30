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
        Schema::table('configuraciones_servicios', function (Blueprint $table) {
            // Add new columns for advanced configuration system
            $table->text('valor')->nullable()->after('clave')->comment('Configuration value');
            $table->string('categoria', 50)->nullable()->after('valor')->comment('Configuration category');
            $table->text('descripcion')->nullable()->after('categoria')->comment('Configuration description');
            $table->string('tipo', 30)->default('texto')->after('descripcion')->comment('Configuration input type');
            
            // Rename 'activa' to 'activo' to match seeder
            $table->renameColumn('activa', 'activo');
            
            // Make existing columns nullable for flexibility
            $table->boolean('email_habilitado')->nullable()->change();
            $table->boolean('sms_habilitado')->nullable()->change();
            $table->time('resumen_diario_hora')->nullable()->change();
            $table->integer('throttling_email')->nullable()->change();
            $table->integer('throttling_sms')->nullable()->change();
            
            // Add indexes for better performance
            $table->index(['categoria', 'activo']);
            $table->index('tipo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('configuraciones_servicios', function (Blueprint $table) {
            // Remove added columns
            $table->dropColumn(['valor', 'categoria', 'descripcion', 'tipo']);
            
            // Rename back
            $table->renameColumn('activo', 'activa');
            
            // Restore original column constraints
            $table->boolean('email_habilitado')->default(true)->change();
            $table->boolean('sms_habilitado')->default(false)->change();
            $table->time('resumen_diario_hora')->default('08:00')->change();
            $table->integer('throttling_email')->default(5)->change();
            $table->integer('throttling_sms')->default(3)->change();
            
            // Remove indexes
            $table->dropIndex(['categoria', 'activo']);
            $table->dropIndex(['tipo']);
        });
    }
};
