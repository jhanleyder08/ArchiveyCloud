<?php

namespace App\Http\Controllers;

use App\Models\TrdTable;
use App\Models\TrdTemplate;
use App\Models\TrdImportConfiguration;
use App\Models\TrdImportLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;

class TrdController extends Controller
{
    /**
     * Display a listing of TRD tables.
     */
    public function index(Request $request)
    {
        $query = TrdTable::with(['creator', 'approver'])
            ->when($request->search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('entity_name', 'like', "%{$search}%");
            })
            ->when($request->status, function ($query, $status) {
                return $query->where('status', $status);
            })
            ->when($request->entity_code, function ($query, $entityCode) {
                return $query->byEntity($entityCode);
            });

        $trdTables = $query->latest()->paginate(15);

        return Inertia::render('TRD/Index', [
            'trdTables' => $trdTables,
            'filters' => $request->only(['search', 'status', 'entity_code'])
        ]);
    }

    /**
     * Show the form for creating a new TRD.
     */
    public function create()
    {
        $templates = TrdTemplate::active()->get();
        
        return Inertia::render('TRD/Create', [
            'templates' => $templates
        ]);
    }

    /**
     * Store a newly created TRD in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'code' => 'required|string|unique:trd_tables,code',
            'entity_name' => 'required|string|max:255',
            'entity_code' => 'required|string|max:50',
            'template_id' => 'nullable|exists:trd_templates,id',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:effective_date'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        try {
            $trdData = $validator->validated();
            $trdData['created_by'] = auth()->id();
            $trdData['status'] = 'draft';

            if ($request->template_id) {
                $template = TrdTemplate::find($request->template_id);
                $trdTable = $template->createTrdFromTemplate($trdData);
            } else {
                $trdTable = TrdTable::create($trdData);
            }

            // Crear primera versión
            $trdTable->createVersion([
                'action' => 'created',
                'summary' => 'TRD creada inicialmente'
            ], 'Versión inicial de la TRD');

            DB::commit();

            return redirect()->route('trd.show', $trdTable)
                ->with('success', 'TRD creada exitosamente');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Error al crear la TRD: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Display the specified TRD.
     */
    public function show(TrdTable $trd)
    {
        $trd->load([
            'creator', 
            'approver',
            'sections.series.subseries',
            'versions.creator'
        ]);

        return Inertia::render('TRD/Show', [
            'trd' => $trd
        ]);
    }

    /**
     * Show the form for editing the specified TRD.
     */
    public function edit(TrdTable $trd)
    {
        $trd->load('sections.series.subseries');
        
        return Inertia::render('TRD/Edit', [
            'trd' => $trd
        ]);
    }

    /**
     * Update the specified TRD in storage.
     */
    public function update(Request $request, TrdTable $trd)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'entity_name' => 'required|string|max:255',
            'entity_code' => 'required|string|max:50',
            'effective_date' => 'nullable|date',
            'expiry_date' => 'nullable|date|after:effective_date',
            'sections' => 'array',
            'sections.*.section_code' => 'required|string',
            'sections.*.section_name' => 'required|string',
            'sections.*.series' => 'array',
            'sections.*.series.*.series_code' => 'required|string',
            'sections.*.series.*.series_name' => 'required|string',
            'sections.*.series.*.subseries' => 'array'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        DB::beginTransaction();
        try {
            $oldData = $trd->toArray();
            
            // Actualizar datos principales
            $trd->update($validator->validated());

            // Actualizar estructura
            if ($request->has('sections')) {
                $this->updateTrdStructure($trd, $request->sections);
            }

            // Crear nueva versión
            $changes = $this->getChanges($oldData, $trd->fresh());
            $trd->createVersion($changes, $request->change_notes);

            DB::commit();

            return redirect()->route('trd.show', $trd)
                ->with('success', 'TRD actualizada exitosamente');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Error al actualizar la TRD: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Remove the specified TRD from storage.
     */
    public function destroy(TrdTable $trd)
    {
        try {
            $trd->createVersion([
                'action' => 'deleted',
                'summary' => 'TRD eliminada'
            ], 'TRD marcada como eliminada');

            $trd->delete();

            return redirect()->route('trd.index')
                ->with('success', 'TRD eliminada exitosamente');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error al eliminar la TRD: ' . $e->getMessage());
        }
    }

    /**
     * Show import form
     */
    public function showImport()
    {
        $configurations = TrdImportConfiguration::where('is_active', true)->get();
        
        return Inertia::render('TRD/Import', [
            'configurations' => $configurations
        ]);
    }

    /**
     * Import TRD from file
     */
    public function import(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|file|mimes:csv,xlsx,json',
            'import_configuration_id' => 'nullable|exists:trd_import_configurations,id',
            'trd_table_id' => 'required|exists:trd_tables,id'
        ]);

        if ($validator->fails()) {
            return redirect()->back()
                ->withErrors($validator)
                ->withInput();
        }

        // Implementar lógica de importación
        // Por ahora crear log básico
        $importLog = TrdImportLog::create([
            'trd_table_id' => $request->trd_table_id,
            'import_configuration_id' => $request->import_configuration_id,
            'filename' => $request->file('file')->getClientOriginalName(),
            'import_type' => $request->file('file')->getClientOriginalExtension(),
            'total_records' => 0,
            'imported_records' => 0,
            'failed_records' => 0,
            'status' => 'processing',
            'imported_by' => auth()->id()
        ]);

        return redirect()->route('trd.index')
            ->with('success', 'Importación iniciada. Se notificará cuando termine.');
    }

    /**
     * Approve TRD
     */
    public function approve(TrdTable $trd)
    {
        DB::beginTransaction();
        try {
            $trd->update([
                'status' => 'active',
                'approved_by' => auth()->id(),
                'approval_date' => now()
            ]);

            $trd->createVersion([
                'action' => 'approved',
                'summary' => 'TRD aprobada y activada'
            ], 'TRD aprobada por ' . auth()->user()->name);

            DB::commit();

            return redirect()->back()
                ->with('success', 'TRD aprobada exitosamente');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Error al aprobar la TRD: ' . $e->getMessage());
        }
    }

    /**
     * Restore specific version
     */
    public function restoreVersion(TrdTable $trd, $versionId)
    {
        $version = $trd->versions()->findOrFail($versionId);
        
        DB::beginTransaction();
        try {
            $version->restore();
            
            $trd->createVersion([
                'action' => 'restored',
                'summary' => "Restaurada versión {$version->version}"
            ], "Versión restaurada desde {$version->version}");

            DB::commit();

            return redirect()->route('trd.show', $trd)
                ->with('success', 'Versión restaurada exitosamente');
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()
                ->with('error', 'Error al restaurar la versión: ' . $e->getMessage());
        }
    }

    private function updateTrdStructure($trd, $sections)
    {
        // Eliminar estructura existente
        $trd->sections()->delete();

        foreach ($sections as $index => $sectionData) {
            $section = $trd->sections()->create([
                'section_code' => $sectionData['section_code'],
                'section_name' => $sectionData['section_name'],
                'description' => $sectionData['description'] ?? null,
                'order_index' => $index
            ]);

            if (isset($sectionData['series'])) {
                foreach ($sectionData['series'] as $seriesIndex => $seriesData) {
                    $series = $section->series()->create([
                        'series_code' => $seriesData['series_code'],
                        'series_name' => $seriesData['series_name'],
                        'description' => $seriesData['description'] ?? null,
                        'order_index' => $seriesIndex
                    ]);

                    if (isset($seriesData['subseries'])) {
                        foreach ($seriesData['subseries'] as $subseriesIndex => $subseriesData) {
                            $series->subseries()->create(array_merge($subseriesData, [
                                'order_index' => $subseriesIndex
                            ]));
                        }
                    }
                }
            }
        }
    }

    private function getChanges($oldData, $newData)
    {
        $changes = [];
        
        foreach (['name', 'description', 'entity_name', 'entity_code'] as $field) {
            if ($oldData[$field] !== $newData[$field]) {
                $changes[] = [
                    'field' => $field,
                    'old_value' => $oldData[$field],
                    'new_value' => $newData[$field]
                ];
            }
        }

        return [
            'action' => 'updated',
            'summary' => 'TRD actualizada',
            'changes' => $changes
        ];
    }
}
