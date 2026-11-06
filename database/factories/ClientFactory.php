<?php

namespace Database\Factories;

use App\Models\Client;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Client>
 */
class ClientFactory extends Factory
{
    protected $model = Client::class;

    public function definition(): array
    {
        $name = fake('pt_BR')->name();
        $docType = fake()->randomElement(['cpf', 'cnpj', null]);
        $docNumber = match ($docType) {
            'cpf' => fake('pt_BR')->cpf(false),
            'cnpj' => fake('pt_BR')->cnpj(false),
            default => null,
        };

        return [
            'user_id' => User::factory(),
            'name' => $name,
            'search_name' => Str::lower(Str::ascii($name)),
            'phone_e164' => '+55'.fake('pt_BR')->areaCode().fake()->numerify('9########'),
            'document_type' => $docType,
            'document_number' => $docNumber,
            'email' => fake()->optional(0.7)->safeEmail(),
            'notes' => fake()->optional(0.5)->sentence(),
            'created_at' => fake()->dateTimeBetween('-6 months', 'now'),
        ];
    }
}
