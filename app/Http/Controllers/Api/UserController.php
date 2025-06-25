<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function setTinkoffTokenApi(Request $Request): JsonResponse
    {
        $validatedData = $Request->validate([
            'tinkoff_token' => 'required|string|max:512',
        ]);

        try {
            $userId = auth()->user()->getAuthIdentifier();

            if (!$userId) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'User not authenticated'
                ], 401);
            }

            $user = User::find($userId);

            $user->tinkoff_token_api = $validatedData['tinkoff_token'];
            $user->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Tinkoff API token successfully saved',
                'user_id' => $userId
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to save token',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
