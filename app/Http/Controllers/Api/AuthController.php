<?php

namespace App\Http\Controllers\Api;

use App\Models\ApiClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthController extends ApiController
{
    public function __invoke(Request $request): JsonResponse
    {
        try {
            $data = $request->validate([
                'api_key' => 'required|string',
                'api_secret' => 'required|string',
            ]);
        } catch (ValidationException $e) {
            return $this->error('API key dan secret diperlukan', 422, $e->errors());
        }

        $client = ApiClient::query()->where('api_key', $data['api_key'])->active()->first();

        if (! $client || ! hash_equals($client->api_secret, $data['api_secret'])) {
            return $this->error('Kredensial tidak valid', 401);
        }

        $token = Str::random(64);
        $ttl = now()->addHours(2);

        Cache::put("api_session:{$token}", [
            'api_client_id' => $client->id,
            'api_key' => $client->api_key,
        ], $ttl);

        return $this->success([
            'token' => $token,
            'token_type' => 'bearer',
            'expires_at' => $ttl->toIso8601String(),
        ], 'Token berhasil dibuat');
    }
}
