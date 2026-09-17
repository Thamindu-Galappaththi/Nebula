<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Module;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class ModuleCreationController extends Controller
{
    public function create(Request $request)
    {
        $data = $this->modulePageData($request);

        if ($request->ajax()) {
            return response()->json([
                'html' => view('courses_&_modules.partials.module_rows', $data)->render(),
                'pagination' => view('courses_&_modules.partials.module_pagination', $data)->render(),
            ]);
        }

        return view('courses_&_modules.module_creation', $data);
    }

    public function store(Request $request)
    {
        try {
            $validationRules = [
                'module_name' => 'required|string|max:255',
                'module_code' => [
                    'required',
                    'string',
                    'max:100',
                    'unique:modules,module_code',
                    'regex:/^[a-zA-Z0-9]+_[a-zA-Z0-9]+_[a-zA-Z0-9]+$/',
                ],
                'module_category' => ['required', Rule::in(['degree', 'certificate'])],
            ];

            if ($request->input('module_category') === 'degree') {
                $validationRules['credits'] = 'required|integer|min:0';
                $validationRules['module_type'] = ['required', Rule::in(['core', 'elective', 'special_unit_compulsory'])];
            }

            $validatedData = $request->validate($validationRules, [
                'module_code.regex' => 'Module code must follow the pattern: program_name_specification_unit_code (e.g., CS101_Programming_001)',
            ]);

            if (($validatedData['module_category'] ?? '') === 'certificate') {
                $validatedData['credits'] = 0;
                $validatedData['module_type'] = 'core';
            }

            $module = Module::create($validatedData);

            return response()->json([
                'success' => true,
                'message' => 'Module created successfully.',
                'module' => $module,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            Log::error('Error storing module data: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating the module.',
            ], 500);
        }
    }

    public function update(Request $request, $id)
    {
        $module = Module::find($id);
        if (!$module) {
            return response()->json([
                'success' => false,
                'message' => 'Module not found.',
            ], 404);
        }

        $validationRules = [
            'module_name' => 'sometimes|required|string|max:255',
            'module_code' => [
                'sometimes',
                'required',
                'string',
                'max:100',
                'unique:modules,module_code,' . $id . ',module_id',
                'regex:/^[a-zA-Z0-9]+_[a-zA-Z0-9]+_[a-zA-Z0-9]+$/',
            ],
            'module_category' => ['sometimes', 'required', Rule::in(['degree', 'certificate'])],
        ];

        $moduleCategory = $request->input('module_category', $module->module_category);
        if ($moduleCategory === 'degree') {
            $validationRules['credits'] = 'sometimes|required|integer|min:0';
            $validationRules['module_type'] = ['sometimes', 'required', Rule::in(['core', 'elective', 'special_unit_compulsory'])];
        }

        $validatedData = $request->validate($validationRules, [
            'module_code.regex' => 'Module code must follow the pattern: program_name_specification_unit_code (e.g., CS101_Programming_001)',
        ]);

        $module->update($validatedData);

        return response()->json([
            'success' => true,
            'message' => 'Module updated successfully.',
            'module' => $module->fresh(),
        ]);
    }

    public function destroy($id)
    {
        try {
            $module = Module::find($id);
            if (!$module) {
                return response()->json([
                    'success' => false,
                    'message' => 'Module not found.',
                ], 404);
            }

            $module->delete();

            return response()->json([
                'success' => true,
                'message' => 'Module deleted successfully.',
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting module: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting the module.',
            ], 500);
        }
    }

    public function bulkDestroy(Request $request)
    {
        $data = $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer',
        ]);

        $deleted = 0;
        $failed = 0;

        foreach ($data['ids'] as $id) {
            try {
                $module = Module::find($id);
                if (!$module) {
                    $failed++;
                    continue;
                }
                $module->delete();
                $deleted++;
            } catch (\Exception $e) {
                Log::error('Error deleting module ' . $id . ': ' . $e->getMessage());
                $failed++;
            }
        }

        return response()->json([
            'success' => $deleted > 0,
            'message' => $failed === 0
                ? "Successfully deleted {$deleted} module(s)."
                : "Deleted {$deleted} module(s). {$failed} could not be deleted.",
            'deleted' => $deleted,
            'failed' => $failed,
        ]);
    }

    private function modulePageData(Request $request): array
    {
        $filters = $request->validate([
            'search' => 'nullable|string|max:255',
            'category' => 'nullable|in:degree,certificate',
            'type' => 'nullable|in:core,elective,special_unit_compulsory',
            'per_page' => 'nullable|integer|in:10,25,50',
        ]);

        $perPage = (int) ($filters['per_page'] ?? 10);

        $modules = Module::query()
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($inner) use ($search) {
                    $inner->where('module_name', 'like', '%' . $search . '%')
                        ->orWhere('module_code', 'like', '%' . $search . '%');
                });
            })
            ->when($filters['category'] ?? null, fn ($query, $category) => $query->where('module_category', $category))
            ->when($filters['type'] ?? null, fn ($query, $type) => $query->where('module_type', $type))
            ->orderBy('module_name')
            ->paginate($perPage)
            ->withQueryString();

        return [
            'modules' => $modules,
            'filters' => $filters,
            'perPage' => $perPage,
        ];
    }
}
