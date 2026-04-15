<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AddressController extends Controller
{
    public function index(Request $request)
    {
        try {
            $user = $request->attributes->get('kc_user');
            $userId = $user['sub'] ?? null;
            if (!$userId) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $list = Address::where('user_id', $userId)->orderByDesc('id')->get();
            return response()->json($list);
        } catch (\Throwable $e) {
            Log::error('GET /api/addresses failed', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Server error'], 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $user = $request->attributes->get('kc_user');
            $userId = $user['sub'] ?? null;
            if (!$userId) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $data = $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:20',
                'address_line' => 'required|string|max:255',
                'city' => 'required|string|max:255',
                'state' => 'required|string|max:255',
                'pincode' => 'required|string|max:12',
            ]);

            $data['user_id'] = $userId;
            $addr = Address::create($data);

            return response()->json($addr, 201);
        } catch (ValidationException $e) {
            throw $e; // let Laravel return 422 with validation errors
        } catch (\Throwable $e) {
            Log::error('POST /api/addresses failed', ['message' => $e->getMessage()]);
            return response()->json(['message' => 'Server error'], 500);
        }
    }

    public function update(Request $request, int $id)
    {
        try {
            $user = $request->attributes->get('kc_user');
            $userId = $user['sub'] ?? null;
            if (!$userId) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }

            $address = Address::where('id', $id)->where('user_id', $userId)->firstOrFail();

            $data = $request->validate([
                'name' => 'sometimes|required|string|max:255',
                'phone' => 'sometimes|required|string|max:20',
                'address_line' => 'sometimes|required|string|max:255',
                'city' => 'sometimes|required|string|max:255',
                'state' => 'sometimes|required|string|max:255',
                'pincode' => 'sometimes|required|string|max:12',
            ]);

            $address->fill($data)->save();
            return response()->json($address);
        } catch (ValidationException $e) {
            throw $e; // return 422 automatically
        } catch (\Throwable $e) {
            Log::error('PUT /api/addresses/{id} failed', ['id' => $id, 'message' => $e->getMessage()]);
            return response()->json(['message' => 'Server error'], 500);
        }
    }

    public function destroy(Request $request, int $id)
    {
        try {
            $user = $request->attributes->get('kc_user');
            $userId = $user['sub'] ?? null;
            if (!$userId) {
                return response()->json(['message' => 'Unauthorized'], 401);
            }
            $address = Address::where('id', $id)->where('user_id', $userId)->firstOrFail();
            $address->delete();
            return response()->json(['status' => 'ok']);
        } catch (\Throwable $e) {
            Log::error('DELETE /api/addresses/{id} failed', ['id' => $id, 'message' => $e->getMessage()]);
            return response()->json(['message' => 'Server error'], 500);
        }
    }
}
