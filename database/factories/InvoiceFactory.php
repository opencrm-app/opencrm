<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class InvoiceFactory extends Factory
{
    protected $model = Invoice::class;

    public function definition(): array
    {
        $issueDate = fake()->dateTimeBetween('-6 months', 'now');
        $dueDate = (clone $issueDate)->modify('+' . fake()->numberBetween(7, 60) . ' days');

        $subtotal = fake()->randomFloat(2, 100, 10000);
        $discountPercent = fake()->randomElement([0, 5, 10, 15, 20]);
        $taxPercent = fake()->randomElement([0, 5, 8, 10, 13]);

        $discountTotal = $subtotal * ($discountPercent / 100);
        $taxableAmount = $subtotal - $discountTotal;
        $taxTotal = $taxableAmount * ($taxPercent / 100);
        $grandTotal = $taxableAmount + $taxTotal;

        return [
            'user_id' => User::factory(),
            'client_id' => Client::factory(),
            'invoice_number' => 'INV-' . fake()->unique()->numberBetween(1000, 99999),
            'issue_date' => $issueDate->format('Y-m-d'),
            'due_date' => $dueDate->format('Y-m-d'),
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_total' => $taxTotal,
            'grand_total' => $grandTotal,
            'currency' => fake()->randomElement(['USD', 'EUR', 'GBP']),
            'status' => fake()->randomElement(['pending', 'paid', 'overdue', 'draft']),
            'notes' => fake()->optional()->sentence(10),
        ];
    }

    public function paid(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'paid',
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'pending',
        ]);
    }

    public function overdue(): static
    {
        return $this->state(fn(array $attributes) => [
            'status' => 'overdue',
            'due_date' => fake()->dateTimeBetween('-60 days', '-1 day')->format('Y-m-d'),
        ]);
    }
}
