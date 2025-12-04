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
        Schema::create('configuraciones_servicios', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100)->unique()->comment('Clave única de configuración');
            $table->boolean('email_habilitado')->default(true)->comment('Email service enabled');
            $table->boolean('sms_habilitado')->default(false)->comment('SMS service enabled');
            $table->time('resumen_diario_hora')->default('08:00')->comment('Daily summary send time');
            $table->integer('throttling_email')->default(5)->comment('Email throttling limit per hour');
            $table->integer('throttling_sms')->default(3)->comment('SMS throttling limit per day');
            $table->json('destinatarios_resumen')->nullable()->comment('Resume recipients user IDs');
            $table->string('ambiente')->nullable()->comment('Environment info');
            $table->string('mail_driver')->nullable()->comment('Mail driver info');
            $table->string('queue_connection')->nullable()->comment('Queue connection info');
            $table->json('metadata')->nullable()->comment('Additional configuration metadata');
            $table->boolean('activa')->default(true)->comment('Configuration is active');
            $table->timestamps();

            $table->index(['clave', 'activa']);
            $table->index('activa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('configuraciones_servicios');
    }
};
