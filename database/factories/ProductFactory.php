<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $productTypes = [
            'Web Development',
            'Mobile App Development',
            'UI/UX Design',
            'Consulting Services',
            'SEO Optimization',
            'Content Writing',
            'Graphic Design',
            'Video Editing',
            'Social Media Management',
            'Email Marketing',
            'Database Management',
            'Cloud Services',
            'API Integration',
            'Security Audit',
            'Performance Optimization',
            'Training & Workshops',
            'Technical Support',
            'Project Management',
            'Quality Assurance',
            'DevOps Services',
        ];

        return [
            'user_id' => User::factory(),
            'name' => fake()->randomElement($productTypes) . ' - ' . fake()->word(),
            'description' => fake()->sentence(12),
            'unit_price' => fake()->randomFloat(2, 50, 5000),
        ];
    }
}
