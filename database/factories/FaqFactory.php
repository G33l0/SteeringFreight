<?php

namespace Database\Factories;

use App\Models\Faq;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Faq>
 */
class FaqFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'question' => 'How long does sea freight take?',
            'answer' => 'Transit time depends on the lane and the sailing schedule.',
            'is_published' => true,
        ];
    }
}
