<?php

namespace App\Http\Controllers\Api\V1\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreAddressRequest;
use App\Http\Requests\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AddressController extends Controller
{
    /**
     * Display a listing of the user's addresses.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $user = $request->user();

        $query = $user->addresses();

        // Filter by type if provided
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }

        // Order by default first, then by created_at
        $addresses = $query->orderBy('is_default', 'desc')
                          ->orderBy('created_at', 'desc')
                          ->get();

        return AddressResource::collection($addresses);
    }

    /**
     * Store a newly created address in storage.
     */
    public function store(StoreAddressRequest $request): AddressResource
    {
        $user = $request->user();

        $addressData = $request->validated();
        $addressData['user_id'] = $user->id;

        $address = Address::create($addressData);

        return new AddressResource($address);
    }

    /**
     * Display the specified address.
     */
    public function show(Request $request, Address $address): AddressResource
    {
        // Ensure the address belongs to the authenticated user
        if ($address->user_id !== $request->user()->id) {
            abort(403, 'No tienes permiso para acceder a esta dirección.');
        }

        return new AddressResource($address);
    }

    /**
     * Update the specified address in storage.
     */
    public function update(UpdateAddressRequest $request, Address $address): AddressResource
    {
        // Ensure the address belongs to the authenticated user
        if ($address->user_id !== $request->user()->id) {
            abort(403, 'No tienes permiso para modificar esta dirección.');
        }

        $address->update($request->validated());

        return new AddressResource($address->fresh());
    }

    /**
     * Remove the specified address from storage.
     */
    public function destroy(Request $request, Address $address): JsonResponse
    {
        // Ensure the address belongs to the authenticated user
        if ($address->user_id !== $request->user()->id) {
            abort(403, 'No tienes permiso para eliminar esta dirección.');
        }

        $address->delete();

        return response()->json([
            'message' => 'Dirección eliminada exitosamente.'
        ]);
    }

    /**
     * Set an address as default for shipping or billing.
     */
    public function setDefault(Request $request, Address $address): AddressResource
    {
        // Ensure the address belongs to the authenticated user
        if ($address->user_id !== $request->user()->id) {
            abort(403, 'No tienes permiso para modificar esta dirección.');
        }

        // Set this address as default
        $address->update(['is_default' => true]);

        return new AddressResource($address->fresh());
    }
}
