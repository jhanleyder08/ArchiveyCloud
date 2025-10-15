<?php

namespace Tests\Feature;

use App\Models\Documento;
use App\Models\Expediente;
use App\Models\Serie;
use App\Models\TipologiaDocumental;
use App\Models\User;
use App\Services\BusinessRulesService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Pruebas de integración para el sistema de validaciones y reglas de negocio
 */
class ValidationIntegrationTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected BusinessRulesService $businessRules;
    protected User $usuario;
    protected Serie $serie;
    protected TipologiaDocumental $tipologia;
    protected Expediente $expediente;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear instancias necesarias para las pruebas
        $this->businessRules = app(BusinessRulesService::class);
        
        // Crear datos de prueba
        $this->usuario = User::factory()->create();
        $this->serie = Serie::factory()->create(['activa' => true]);
        $this->tipologia = TipologiaDocumental::factory()->create(['activo' => true]);
        $this->expediente = Expediente::factory()->create([
            'serie_id' => $this->serie->id,
            'estado' => 'abierto'
        ]);

        $this->actingAs($this->usuario);
    }

    /**
     * Test: Validación exitosa de estructura TRD/CCD
     */
    public function test_validacion_estructura_trd_exitosa(): void
    {
        $datos = [
            'serie_id' => $this->serie->id,
            'tipologia_id' => $this->tipologia->id
        ];

        $resultado = $this->businessRules->validarEstructuraTRD($datos);

        $this->assertTrue($resultado['valido']);
        $this->assertEmpty($resultado['errores']);
    }

    /**
     * Test: Validación falla con serie inactiva
     */
    public function test_validacion_estructura_trd_falla_serie_inactiva(): void
    {
        $serieInactiva = Serie::factory()->create(['activa' => false]);
        
        $datos = [
            'serie_id' => $serieInactiva->id,
            'tipologia_id' => $this->tipologia->id
        ];

        $resultado = $this->businessRules->validarEstructuraTRD($datos);

        $this->assertFalse($resultado['valido']);
        $this->assertContains('La serie especificada está inactiva', $resultado['errores']);
    }

    /**
     * Test: Validación de reglas de negocio para expedientes
     */
    public function test_validacion_reglas_expediente_transicion_estado(): void
    {
        // Cambio de estado válido: abierto -> activo
        $cambios = ['estado' => 'activo'];
        $resultado = $this->businessRules->validarReglasExpediente($this->expediente, $cambios);

        $this->assertTrue($resultado['valido']);
        $this->assertEmpty($resultado['errores']);

        // Cambio de estado inválido: abierto -> eliminado
        $cambiosInvalidos = ['estado' => 'eliminado'];
        $resultadoInvalido = $this->businessRules->validarReglasExpediente($this->expediente, $cambiosInvalidos);

        $this->assertFalse($resultadoInvalido['valido']);
        $this->assertNotEmpty($resultadoInvalido['errores']);
    }

    /**
     * Test: Validación de metadatos obligatorios para documentos
     */
    public function test_validacion_metadatos_obligatorios_documento(): void
    {
        // Documento con metadatos completos
        $documentoCompleto = Documento::factory()->create([
            'nombre' => 'Documento de prueba',
            'tipologia_id' => $this->tipologia->id,
            'expediente_id' => $this->expediente->id,
            'usuario_creador_id' => $this->usuario->id
        ]);

        $resultado = $this->businessRules->validarMetadatosObligatorios($documentoCompleto, 'documento');
        $this->assertTrue($resultado['valido']);

        // Documento con metadatos incompletos
        $documentoIncompleto = Documento::factory()->create([
            'nombre' => null, // Campo obligatorio faltante
            'expediente_id' => $this->expediente->id,
            'usuario_creador_id' => $this->usuario->id
        ]);

        $resultadoIncompleto = $this->businessRules->validarMetadatosObligatorios($documentoIncompleto, 'documento');
        $this->assertFalse($resultadoIncompleto['valido']);
        $this->assertNotEmpty($resultadoIncompleto['errores']);
    }

    /**
     * Test: Validación de integridad referencial
     */
    public function test_validacion_integridad_referencial_documento(): void
    {
        $documento = Documento::factory()->create([
            'expediente_id' => $this->expediente->id,
            'usuario_creador_id' => $this->usuario->id
        ]);

        // Validación exitosa
        $resultado = $this->businessRules->validarIntegridadReferencial($documento, 'create');
        $this->assertTrue($resultado['valido']);

        // Documento con expediente inexistente
        $documentoInvalido = Documento::factory()->make([
            'expediente_id' => 99999, // ID inexistente
            'usuario_creador_id' => $this->usuario->id
        ]);

        $resultadoInvalido = $this->businessRules->validarIntegridadReferencial($documentoInvalido, 'create');
        $this->assertFalse($resultadoInvalido['valido']);
    }

    /**
     * Test: Generación de asistente de validación
     */
    public function test_generacion_asistente_validacion(): void
    {
        $documento = Documento::factory()->create([
            'nombre' => 'Documento completo',
            'descripcion' => 'Descripción detallada',
            'tipologia_id' => $this->tipologia->id,
            'expediente_id' => $this->expediente->id,
            'usuario_creador_id' => $this->usuario->id,
            'palabras_clave' => ['prueba', 'validación'],
            'hash_sha256' => hash('sha256', 'contenido_prueba')
        ]);

        $asistente = $this->businessRules->generarAsistenteValidacion($documento);

        $this->assertArrayHasKey('puntuacion_calidad', $asistente);
        $this->assertArrayHasKey('recomendaciones', $asistente);
        $this->assertArrayHasKey('errores_criticos', $asistente);
        $this->assertArrayHasKey('mejoras_sugeridas', $asistente);
        
        $this->assertIsInt($asistente['puntuacion_calidad']);
        $this->assertGreaterThanOrEqual(0, $asistente['puntuacion_calidad']);
        $this->assertLessThanOrEqual(100, $asistente['puntuacion_calidad']);
    }

    /**
     * Test: API de validación completa
     */
    public function test_api_validacion_completa(): void
    {
        $documento = Documento::factory()->create([
            'expediente_id' => $this->expediente->id,
            'tipologia_id' => $this->tipologia->id,
            'usuario_creador_id' => $this->usuario->id
        ]);

        $response = $this->postJson('/validations/completa', [
            'tipo_entidad' => 'documento',
            'entidad_id' => $documento->id,
            'incluir_asistente' => true
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'valido_general',
                        'tipo_entidad',
                        'entidad_id',
                        'validaciones' => [
                            'metadatos',
                            'integridad',
                            'estructura_trd',
                            'asistente'
                        ],
                        'resumen'
                    ]
                ]);
    }

    /**
     * Test: API de validación de estructura TRD
     */
    public function test_api_validacion_estructura_trd(): void
    {
        $response = $this->postJson('/validations/estructura-trd', [
            'serie_id' => $this->serie->id,
            'tipologia_id' => $this->tipologia->id
        ]);

        $response->assertStatus(200)
                ->assertJson([
                    'success' => true,
                    'data' => [
                        'valido' => true
                    ]
                ]);
    }

    /**
     * Test: API de validación de reglas de expediente
     */
    public function test_api_validacion_reglas_expediente(): void
    {
        $response = $this->postJson("/validations/reglas-expediente/{$this->expediente->id}", [
            'estado' => 'activo'
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'valido',
                        'errores',
                        'advertencias',
                        'expediente_id',
                        'estado_actual'
                    ]
                ]);
    }

    /**
     * Test: API de asistente de validación
     */
    public function test_api_asistente_validacion(): void
    {
        $documento = Documento::factory()->create([
            'expediente_id' => $this->expediente->id,
            'usuario_creador_id' => $this->usuario->id
        ]);

        $response = $this->postJson('/validations/asistente', [
            'tipo_entidad' => 'documento',
            'entidad_id' => $documento->id
        ]);

        $response->assertStatus(200)
                ->assertJsonStructure([
                    'success',
                    'data' => [
                        'tipo_entidad',
                        'entidad_id',
                        'puntuacion_calidad',
                        'nivel_calidad',
                        'recomendaciones',
                        'errores_criticos',
                        'mejoras_sugeridas',
                        'siguiente_accion',
                        'resumen'
                    ]
                ]);
    }

    /**
     * Test: Validación con datos inválidos
     */
    public function test_validacion_con_datos_invalidos(): void
    {
        // Test con tipo de entidad inválido
        $response = $this->postJson('/validations/metadatos', [
            'tipo_entidad' => 'invalido',
            'entidad_id' => 1
        ]);

        $response->assertStatus(422)
                ->assertJsonValidationErrors(['tipo_entidad']);

        // Test con ID inexistente
        $response = $this->postJson('/validations/asistente', [
            'tipo_entidad' => 'documento',
            'entidad_id' => 99999
        ]);

        $response->assertStatus(404);
    }

    /**
     * Test: Middleware de validación automática
     */
    public function test_middleware_validacion_automatica(): void
    {
        // Crear documento que activará validaciones automáticas
        $response = $this->post('/admin/documentos', [
            'nombre' => 'Documento de prueba',
            'expediente_id' => $this->expediente->id,
            'tipologia_id' => $this->tipologia->id,
            'tipo_soporte' => 'electronico',
            'estado' => 'borrador',
            'confidencialidad' => 'interna'
        ]);

        // La respuesta puede ser redirección (éxito) o error de validación
        $this->assertTrue(
            $response->status() === 302 || $response->status() === 422
        );
    }

    /**
     * Test: Eventos de validación
     */
    public function test_eventos_validacion(): void
    {
        $this->expectsEvents([
            \App\Events\ValidationFailedEvent::class
        ]);

        // Crear documento con datos inválidos para disparar evento
        $documentoInvalido = Documento::factory()->make([
            'expediente_id' => 99999, // ID inexistente
            'usuario_creador_id' => $this->usuario->id
        ]);

        $this->businessRules->validarIntegridadReferencial($documentoInvalido, 'create');
    }
}
