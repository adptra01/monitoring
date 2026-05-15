<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Services\GitHubService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SyncGitHubProducts extends Command
{
    protected $signature = 'github:sync-products';

    protected $description = 'Sync GitHub repositories as products in the database';

    public function handle(GitHubService $gitHub): int
    {
        $this->info('Fetching repositories from GitHub...');

        $repos = $gitHub->fetchRepos();

        if (empty($repos)) {
            $this->warn('No repositories found or GitHub token not configured.');

            return Command::FAILURE;
        }

        $count = count($repos);
        $this->info("Found {$count} repositories.");

        $gitHub->clearCache();
        $gitHub->listRepos();

        $created = 0;
        $updated = 0;

        foreach ($repos as $repo) {
            $product = Product::where('github_repo_id', $repo['id'])->first();

            if ($product === null) {
                Product::create([
                    'name' => $repo['full_name'],
                    'slug' => Str::slug($repo['full_name']),
                    'description' => $repo['description'],
                    'is_active' => true,
                    'github_repo_id' => $repo['id'],
                    'github_repo_full_name' => $repo['full_name'],
                    'github_repo_url' => $repo['url'],
                    'github_repo_description' => $repo['description'],
                    'github_default_branch' => $repo['default_branch'],
                ]);

                $created++;
                $this->line("  Created: {$repo['full_name']}");
            } else {
                $product->update([
                    'github_repo_description' => $repo['description'],
                    'github_repo_url' => $repo['url'],
                    'github_default_branch' => $repo['default_branch'],
                ]);

                $updated++;
            }
        }

        $this->info("Created {$created} new product(s), updated {$updated} existing product(s).");

        return Command::SUCCESS;
    }
}
