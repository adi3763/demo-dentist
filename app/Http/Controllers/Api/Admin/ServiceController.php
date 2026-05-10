<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    // GET /api/admin/services
    // Admin sees all services including inactive
    public function index()
    {
        $services = Service::orderBy('name')->get();

        return response()->json(['services' => $services]);
    }

    // POST /api/admin/services
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:100|unique:services,name',
            'description'      => 'nullable|string|max:500',
            'price'            => 'nullable|numeric|min:0',
            'duration_minutes' => 'nullable|integer|min:15|max:480',
            'is_active'        => 'nullable|boolean',
        ]);

        $service = Service::create([
            'name'             => $validated['name'],
            'description'      => $validated['description'] ?? null,
            'price'            => $validated['price'] ?? null,
            'duration_minutes' => $validated['duration_minutes'] ?? 30,
            'is_active'        => $validated['is_active'] ?? true,
        ]);

        return response()->json([
            'message' => 'Service created.',
            'service' => $service,
        ], 201);
    }

    // GET /api/admin/services/{id}
    public function show($id)
    {
        $service = Service::findOrFail($id);

        return response()->json(['service' => $service]);
    }

    // PATCH /api/admin/services/{id}
    public function update(Request $request, $id)
    {
        $service = Service::findOrFail($id);

        $validated = $request->validate([
            'name'             => 'sometimes|string|max:100|unique:services,name,' . $id,
            'description'      => 'sometimes|nullable|string|max:500',
            'price'            => 'sometimes|nullable|numeric|min:0',
            'duration_minutes' => 'sometimes|nullable|integer|min:15|max:480',
            'is_active'        => 'sometimes|boolean',
        ]);

        $service->update($validated);

        return response()->json([
            'message' => 'Service updated.',
            'service' => $service->fresh(),
        ]);
    }

    // DELETE /api/admin/services/{id}
    public function destroy($id)
    {
        $service = Service::findOrFail($id);
        $service->delete();

        return response()->json(['message' => 'Service deleted.']);
    }

    // PATCH /api/admin/services/{id}/toggle
    public function toggle($id)
    {
        $service = Service::findOrFail($id);
        $service->update(['is_active' => ! $service->is_active]);

        return response()->json([
            'message'   => $service->is_active ? 'Service enabled.' : 'Service disabled.',
            'is_active' => $service->is_active,
        ]);
    }
}
