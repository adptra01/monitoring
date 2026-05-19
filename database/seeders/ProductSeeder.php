<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Laravel Monitor Pro', 'slug' => 'laravel-monitor-pro', 'description' => 'Solusi monitoring profesional untuk aplikasi Laravel', 'repo' => 'devWebs01/laravel13-pos', 'repo_id' => 1233155058],
            ['name' => 'Analytics Dashboard', 'slug' => 'analytics-dashboard', 'description' => 'Dasbor analitik dan pelaporan waktu nyata', 'repo' => 'devWebs01/NW-Coffe', 'repo_id' => 1212716271],
            ['name' => 'API Gateway', 'slug' => 'api-gateway', 'description' => 'Manajemen API enterprise dan pembatasan kecepatan', 'repo' => null, 'repo_id' => null],
        ];

        foreach ($products as $productData) {
            $product = Product::where('slug', $productData['slug'])->orWhere('github_repo_id', $productData['repo_id'])->first();

            if ($product) {
                $product->update([
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'is_active' => true,
                    'github_repo_id' => $productData['repo_id'],
                    'github_repo_full_name' => $productData['repo'],
                    'github_repo_url' => $productData['repo'] ? "https://github.com/{$productData['repo']}" : null,
                    'github_default_branch' => 'main',
                ]);
            } else {
                Product::create([
                    'slug' => $productData['slug'],
                    'name' => $productData['name'],
                    'description' => $productData['description'],
                    'is_active' => true,
                    'github_repo_id' => $productData['repo_id'],
                    'github_repo_full_name' => $productData['repo'],
                    'github_repo_url' => $productData['repo'] ? "https://github.com/{$productData['repo']}" : null,
                    'github_default_branch' => 'main',
                ]);
            }
        }
    }
}
