<?php

namespace Database\Factories;

use App\Models\BusinessProfile;
use App\Models\Client;
use App\Models\User;
use Database\Seeders\DefaultCategoriesSeeder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'terms_accepted_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    /**
     * Attach a business profile to the user.
     */
    public function withBusinessProfile(array $attributes = []): static
    {
        return $this->has(
            BusinessProfile::factory()->state($attributes),
            'businessProfile'
        );
    }

    /**
     * Seed default categories for the user.
     */
    public function withDefaultCategories(): static
    {
        return $this->afterCreating(function (User $user) {
            app(DefaultCategoriesSeeder::class)->run($user);
        });
    }

    /**
     * Attach clients to the user.
     */
    public function withClients(int $count = 5, array $attributes = []): static
    {
        return $this->has(
            Client::factory()->count($count)->state($attributes),
            'clients'
        );
    }
}
