<?php

namespace App\Services;

use App\Models\License;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GithubLicenseSyncService
{
    protected string $token;

    protected string $owner;

    protected string $repo;

    protected string $path;

    protected string $apiUrl = 'https://api.github.com';

    public function __construct()
    {
        $this->token = config('services.github.token', '');
        $this->owner = config('services.github.sync_owner', '');
        $this->repo = config('services.github.sync_repo', '');
        $this->path = config('services.github.sync_path', 'licenses');
    }

    public function sync(License $license): bool
    {
        if (empty($this->token) || empty($this->owner) || empty($this->repo)) {
            Log::warning('GitHub sync not configured');

            return false;
        }

        $hash = sha1($license->key);
        $filePath = trim($this->path, '/').'/'.$hash.'.json';

        $payload = [
            'license_hash' => $hash,
            'status' => $license->status->value,
            'expires_at' => $license->expires_at?->toIso8601String(),
            'max_devices' => $license->max_devices,
            'updated_at' => now()->toIso8601String(),
        ];

        try {
            $existing = $this->getCurrentSha($filePath);

            if ($existing !== null && $this->hasSameContent($filePath, $payload)) {
                return true;
            }

            $content = base64_encode(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

            $response = Http::withToken($this->token)
                ->timeout(10)
                ->connectTimeout(5)
                ->retry(2, 100)
                ->put("{$this->apiUrl}/repos/{$this->owner}/{$this->repo}/contents/{$filePath}", array_filter([
                    'message' => "Sync license {$hash}",
                    'content' => $content,
                    'sha' => $existing,
                ]));

            if ($response->successful()) {
                return true;
            }

            Log::warning('GitHub sync failed', [
                'license' => $license->key,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return false;
        } catch (\Throwable $e) {
            Log::warning('GitHub sync error', [
                'license' => $license->key,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    protected function hasSameContent(string $filePath, array $payload): bool
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(10)
                ->connectTimeout(5)
                ->get("{$this->apiUrl}/repos/{$this->owner}/{$this->repo}/contents/{$filePath}");

            if (! $response->successful()) {
                return false;
            }

            $existing = $response->json();
            $decoded = base64_decode($existing['content'], true);

            if ($decoded === false) {
                return false;
            }

            $existingPayload = json_decode($decoded, true);

            return ($existingPayload['status'] ?? null) === $payload['status']
                && ($existingPayload['expires_at'] ?? null) === $payload['expires_at']
                && ($existingPayload['max_devices'] ?? null) === $payload['max_devices'];
        } catch (\Throwable) {
            return false;
        }
    }

    protected function getCurrentSha(string $path): ?string
    {
        try {
            $response = Http::withToken($this->token)
                ->timeout(10)
                ->connectTimeout(5)
                ->get("{$this->apiUrl}/repos/{$this->owner}/{$this->repo}/contents/{$path}");

            if ($response->successful()) {
                return $response->json('sha');
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }
}
