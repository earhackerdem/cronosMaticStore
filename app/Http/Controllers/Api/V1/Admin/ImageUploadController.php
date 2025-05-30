<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ImageUploadController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created image resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048', // Máximo 2MB
            'type' => 'nullable|string|in:products,categories' // Opcional: para subdirectorios
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422); // Revertir a la respuesta de error original
        }

        $file = $request->file('image');
        $type = $request->input('type', 'general'); // Directorio por defecto si no se especifica

        $fileName = Str::uuid()->toString() . '.' . $file->getClientOriginalExtension();

        // Directorio relativo dentro del disco 'public'
        $directory = $type; // ej. 'products' o 'categories'

        // Almacenar el archivo en el disco 'public', $path será 'products/filename.jpg'
        $path = $file->storeAs($directory, $fileName, 'public');

        if (!$path) {
            return response()->json(['message' => 'Error uploading image.'], 500);
        }

        // Obtener la URL pública completa. Storage::url() prefija con /storage si es necesario.
        $publicUrl = Storage::url($path);

        return response()->json([
            'message' => 'Image uploaded successfully',
            'path' => $path, // Ruta relativa al disco 'public', ej: 'products/filename.jpg'
            'url' => $publicUrl, // URL pública completa
            'relative_url' => $path // Esta es la ruta que se debe guardar en la BD
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
