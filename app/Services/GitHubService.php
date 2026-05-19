<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GitHubService
{
    protected string $token;

    protected string $apiUrl = 'https://api.github.com';

    protected int $timeout = 5;

    protected int $connectTimeout = 5;

    public function __construct()
    {
        $this->token = config('services.github.token', '');
    }

    public function listRepos(): array
    {
        if (empty($this->token)) {
            return [];
        }

        return Cache::remember('github_repos', 3600, fn () => $this->fetchRepos());
    }

    public function fetchRepos(): array
    {
        if (empty($this->token)) {
            return [];
        }

        $usernames = config('services.github.usernames', []);

        if (empty($usernames)) {
            return $this->fetchAuthenticatedUserRepos();
        }

        $allRepos = [];

        foreach ($usernames as $username) {
            $username = trim($username);

            if (empty($username)) {
                continue;
            }

            $repos = $this->fetchReposForUser($username);
            $allRepos = array_merge($allRepos, $repos);
        }

        usort($allRepos, fn ($a, $b) => strcmp($a['full_name'], $b['full_name']));

        return $allRepos;
    }

    protected function fetchAuthenticatedUserRepos(): array
    {
        return $this->paginate('/user/repos', ['sort' => 'full_name', 'type' => 'all']);
    }

    protected function fetchReposForUser(string $username): array
    {
        return $this->paginate("/users/{$username}/repos", ['sort' => 'full_name', 'type' => 'all']);
    }

    protected function paginate(string $path, array $params = []): array
    {
        $repos = [];
        $page = 1;

        do {
            try {
                $response = Http::withToken($this->token)
                    ->timeout($this->timeout)
                    ->connectTimeout($this->connectTimeout)
                    ->retry(2, 100)
                    ->get("{$this->apiUrl}{$path}", array_merge($params, [
                        'per_page' => 100,
                        'page' => $page,
                    ]));
            } catch (\Throwable) {
                Log::warning('GitHub API connection timeout', ['path' => $path, 'page' => $page]);

                break;
            }

            if ($response->tooManyRequests()) {
                Log::warning('GitHub API rate limited');

                break;
            }

            if (! $response->successful()) {
                Log::warning('GitHub API error', [
                    'status' => $response->status(),
                    'path' => $path,
                    'page' => $page,
                ]);

                break;
            }

            $items = $response->json();

            foreach ($items as $repo) {
                $repos[] = [
                    'id' => $repo['id'],
                    'full_name' => $repo['full_name'],
                    'url' => $repo['html_url'],
                    'description' => $repo['description'],
                    'default_branch' => $repo['default_branch'],
                    'language' => $repo['language'],
                    'private' => $repo['private'],
                    'updated_at' => $repo['updated_at'],
                ];
            }

            $page++;
        } while (count($items) === 100);

        return $repos;
    }

    public function clearCache(): void
    {
        Cache::forget('github_repos');
    }

    public function fetchReadme(string $fullName): ?string
    {
        if (empty($this->token)) {
            return null;
        }

        try {
            $response = Http::withToken($this->token)
                ->timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->withHeaders(['Accept' => 'application/vnd.github.raw'])
                ->retry(2, 100)
                ->get("{$this->apiUrl}/repos/{$fullName}/readme");

            if (! $response->successful()) {
                return null;
            }
        } catch (\Throwable) {
            Log::warning('GitHub API error fetching readme', ['repo' => $fullName]);

            return null;
        }

        $content = $response->body();

        $content = preg_replace('/^#\s+.*$/m', '', $content, 1);
        $content = preg_replace('/<!--.*?-->/s', '', $content);
        $content = strip_tags($content);
        $content = preg_replace('/\n{3,}/', "\n\n", $content);

        return trim(mb_substr($content, 0, 2000));
    }

    public function syncProductMetadata(Product $product): ?array
    {
        if (empty($product->github_repo_full_name)) {
            return null;
        }

        try {
            $response = Http::withToken($this->token)
                ->timeout($this->timeout)
                ->connectTimeout($this->connectTimeout)
                ->retry(2, 100)
                ->get("{$this->apiUrl}/repos/{$product->github_repo_full_name}");

            if (! $response->successful()) {
                return null;
            }
        } catch (\Throwable) {
            Log::warning('GitHub API error syncing product', ['repo' => $product->github_repo_full_name]);

            return null;
        }

        $repo = $response->json();

        $product->update([
            'github_repo_description' => $repo['description'],
            'github_default_branch' => $repo['default_branch'],
            'github_repo_url' => $repo['html_url'],
        ]);

        return $repo;
    }
}
