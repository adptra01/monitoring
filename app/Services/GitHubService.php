<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class GitHubService
{
    protected string $token;

    protected string $apiUrl = 'https://api.github.com';

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
            $response = Http::withToken($this->token)
                ->get("{$this->apiUrl}{$path}", array_merge($params, [
                    'per_page' => 100,
                    'page' => $page,
                ]));

            if (! $response->successful()) {
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

    public function syncProductMetadata(Product $product): ?array
    {
        if (empty($product->github_repo_full_name)) {
            return null;
        }

        $response = Http::withToken($this->token)
            ->get("{$this->apiUrl}/repos/{$product->github_repo_full_name}");

        if (! $response->successful()) {
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
