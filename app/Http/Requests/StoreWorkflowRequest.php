<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * Form Request para crear Workflows
 * Incluye validación completa y autorización
 */
class StoreWorkflowRequest extends FormRequest
{
    /**
     * Determinar si el usuario está autorizado
     */
    public function authorize(): bool
    {
        return auth()->check() && 
               (auth()->user()->can('crear_workflows') || auth()->user()->hasRole(['admin', 'super-admin']));
    }

    /**
     * Reglas de validación
     */
    public function rules(): array
    {
        return [
            'nombre' => 'required|string|max:255|unique:workflows,nombre',
            'descripcion' => 'nullable|string|max:1000',
            'tipo_entidad' => 'required|string|max:255',
            
            // Pasos del workflow
            'pasos' => 'required|array|min:1|max:20',
            'pasos.*.nombre' => 'required|string|max:255',
            'pasos.*.descripcion' => 'nullable|string|max:500',
            'pasos.*.tipo_asignacion' => 'required|in:usuario,rol',
            'pasos.*.asignado_id' => 'required|integer|min:1',
            'pasos.*.asignado_type' => 'required|string|max:255',
            'pasos.*.dias_vencimiento' => 'nullable|integer|min:1|max:365',
            
            // Configuración
            'configuracion' => 'nullable|array',
            'configuracion.requiere_observaciones' => 'nullable|boolean',
            'configuracion.permite_delegacion' => 'nullable|boolean',
            'configuracion.notificar_vencimiento' => 'nullable|boolean',
            'configuracion.escalamiento_automatico' => 'nullable|boolean',
            
            'activo' => 'nullable|boolean',
        ];
    }

    /**
     * Mensajes de validación personalizados
     */
    public function messages(): array
    {
        return [
            'nombre.required' => 'El nombre del workflow es obligatorio',
            'nombre.unique' => 'Ya existe un workflow con este nombre',
            'tipo_entidad.required' => 'Debe especificar el tipo de entidad',
            
            'pasos.required' => 'El workflow debe tener al menos un paso',
            'pasos.min' => 'El workflow debe tener al menos un paso',
            'pasos.max' => 'El workflow no puede tener más de 20 pasos',
            
            'pasos.*.nombre.required' => 'Cada paso debe tener un nombre',
            'pasos.*.tipo_asignacion.required' => 'Debe especificar cómo se asigna cada paso',
            'pasos.*.tipo_asignacion.in' => 'El tipo de asignación debe ser "usuario" o "rol"',
            'pasos.*.asignado_id.required' => 'Debe especificar a quién se asigna',
            'pasos.*.dias_vencimiento.max' => 'El vencimiento no puede ser mayor a 365 días',
        ];
    }

    /**
     * Nombres de atributos personalizados
     */
    public function attributes(): array
    {
        return [
            'nombre' => 'nombre del workflow',
            'descripcion' => 'descripción',
            'tipo_entidad' => 'tipo de entidad',
            'pasos' => 'pasos',
            'configuracion' => 'configuración',
        ];
    }

    /**
     * Preparar datos después de la validación
     */
    protected function passedValidation(): void
    {
        // Agregar usuario creador automáticamente
        $this->merge([
            'usuario_creador_id' => auth()->id(),
        ]);

        // Configuración por defecto si no se especifica
        if (!$this->has('configuracion')) {
            $this->merge([
                'configuracion' => [
                    'requiere_observaciones' => false,
                    'permite_delegacion' => true,
                    'notificar_vencimiento' => true,
                ],
            ]);
        }
    }
}
