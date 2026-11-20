<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Tenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Lista todas las categorías con el conteo de productos asociados.
     */
    public function index(): JsonResponse
    {
        $categories = Category::withCount('products')
            ->orderBy('name')
            ->get();

        return response()->json([
            'status' => 'success',
            'data'   => $categories,
        ]);
    }

    /**
     * Crea una nueva categoría de productos.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:120',
            'description' => 'nullable|string|max:255',
            'is_active'   => 'nullable|boolean',
        ]);

        $user = $request->user();
        $tenantId = $user?->tenant_id ?? Tenant::value('id');

        $baseSlug = Str::slug($validated['name']);
        $slug = $baseSlug;
        $counter = 1;
        while (Category::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter++;
        }

        $category = Category::create([
            'tenant_id'   => $tenantId,
            'name'        => trim($validated['name']),
            'slug'        => $slug,
            'description' => $validated['description'] ?? null,
            'is_active'   => $validated['is_active'] ?? true,
        ]);

        $category->loadCount('products');

        return response()->json([
            'status'  => 'success',
            'message' => 'Categoría creada exitosamente.',
            'data'    => $category,
        ], 201);
    }

    /**
     * Actualiza una categoría existente.
     */
    public function update(Request $request, string $id): JsonResponse
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'required|string|max:120',
            'description' => 'nullable|string|max:255',
            'is_active'   => 'nullable|boolean',
        ]);

        $newName = trim($validated['name']);
        if ($newName !== $category->name) {
            $baseSlug = Str::slug($newName);
            $slug = $baseSlug;
            $counter = 1;
            while (Category::where('slug', $slug)->where('id', '!=', $category->id)->exists()) {
                $slug = $baseSlug . '-' . $counter++;
            }
            $category->slug = $slug;
        }

        $category->name = $newName;
        if (array_key_exists('description', $validated)) {
            $category->description = $validated['description'];
        }
        if (isset($validated['is_active'])) {
            $category->is_active = (bool) $validated['is_active'];
        }
        $category->save();
        $category->loadCount('products');

        return response()->json([
            'status'  => 'success',
            'message' => 'Categoría actualizada correctamente.',
            'data'    => $category,
        ]);
    }

    /**
     * Elimina una categoría (verifica que no tenga productos vinculados).
     */
    public function destroy(string $id): JsonResponse
    {
        $category = Category::withCount('products')->findOrFail($id);

        if ($category->products_count > 0) {
            return response()->json([
                'status'  => 'error',
                'message' => "No se puede eliminar la categoría \"{$category->name}\" porque tiene {$category->products_count} producto(s) asociado(s).",
            ], 422);
        }

        $category->delete();

        return response()->json([
            'status'  => 'success',
            'message' => 'Categoría eliminada correctamente.',
        ]);
    }
}
