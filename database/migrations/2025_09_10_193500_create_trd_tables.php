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
        // Tabla principal de TRD
        Schema::create('trd_tables', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('code')->unique();
            $table->string('entity_name'); // Entidad productora
            $table->string('entity_code');
            $table->string('version', 10)->default('1.0');
            $table->enum('status', ['draft', 'active', 'archived', 'obsolete'])->default('draft');
            $table->date('approval_date')->nullable();
            $table->date('effective_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->json('metadata')->nullable(); // Metadatos adicionales
            $table->timestamps();
            $table->softDeletes();
        });

        // Plantillas predefinidas para TRD
        Schema::create('trd_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description');
            $table->string('category'); // gobierno, educacion, salud, etc.
            $table->json('template_structure'); // Estructura predefinida
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Secciones de la TRD (Oficina productora, Subserie, etc.)
        Schema::create('trd_sections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trd_table_id')->constrained('trd_tables')->cascadeOnDelete();
            $table->string('section_code');
            $table->string('section_name');
            $table->text('description')->nullable();
            $table->integer('order_index')->default(0);
            $table->timestamps();
        });

        // Series documentales
        Schema::create('trd_series', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trd_section_id')->constrained('trd_sections')->cascadeOnDelete();
            $table->string('series_code');
            $table->string('series_name');
            $table->text('description')->nullable();
            $table->integer('order_index')->default(0);
            $table->timestamps();
        });

        // Subseries documentales
        Schema::create('trd_subseries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trd_series_id')->constrained('trd_series')->cascadeOnDelete();
            $table->string('subseries_code');
            $table->string('subseries_name');
            $table->text('description')->nullable();
            $table->string('document_type'); // Tipo documental
            $table->integer('retention_archive_management')->default(0); // Tiempo en archivo de gestión
            $table->integer('retention_central_archive')->default(0); // Tiempo en archivo central
            $table->enum('final_disposition', ['conservation_total', 'selection', 'elimination']); // CT, S, E
            $table->text('access_restrictions')->nullable();
            $table->text('procedure')->nullable(); // Procedimiento
            $table->integer('order_index')->default(0);
            $table->timestamps();
        });

        // Historial de versiones de TRD
        Schema::create('trd_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trd_table_id')->constrained('trd_tables');
            $table->string('version', 10);
            $table->json('changes_summary'); // Resumen de cambios
            $table->json('full_snapshot'); // Snapshot completo de la versión
            $table->foreignId('created_by')->constrained('users');
            $table->text('change_notes')->nullable();
            $table->timestamp('created_at');
        });

        // Configuraciones de importación
        Schema::create('trd_import_configurations', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('import_type'); // csv, excel, json
            $table->json('field_mapping'); // Mapeo de campos
            $table->json('validation_rules'); // Reglas de validación
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Log de importaciones
        Schema::create('trd_import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trd_table_id')->constrained('trd_tables');
            $table->foreignId('import_configuration_id')->nullable()->constrained('trd_import_configurations');
            $table->string('filename');
            $table->string('import_type');
            $table->integer('total_records');
            $table->integer('imported_records');
            $table->integer('failed_records');
            $table->json('errors')->nullable();
            $table->enum('status', ['processing', 'completed', 'failed']);
            $table->foreignId('imported_by')->constrained('users');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trd_import_logs');
        Schema::dropIfExists('trd_import_configurations');
        Schema::dropIfExists('trd_versions');
        Schema::dropIfExists('trd_subseries');
        Schema::dropIfExists('trd_series');
        Schema::dropIfExists('trd_sections');
        Schema::dropIfExists('trd_templates');
        Schema::dropIfExists('trd_tables');
    }
};
