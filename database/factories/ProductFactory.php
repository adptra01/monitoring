<?php

namespace Database\Factories;

use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => ucwords($name),
            'slug' => Str::slug($name),
            'description' => fake()->sentence(),
            'is_active' => true,
        ];
    }

    public function withGitHubRepo(): static
    {
        return $this->state(fn (array $attributes) => [
            'github_repo_id' => fake()->unique()->randomNumber(8),
            'github_repo_full_name' => fake()->userName().'/'.fake()->slug(2),
            'github_repo_url' => 'https://github.com/'.fake()->userName().'/'.fake()->slug(2),
            'github_repo_description' => fake()->sentence(),
            'github_default_branch' => 'main',
        ]);
    }

    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => ['is_active' => false]);
    }
}
