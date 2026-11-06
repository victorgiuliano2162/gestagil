<?php

namespace Database\Factories;

use App\Models\BusinessProfile;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BusinessProfile>
 */
class BusinessProfileFactory extends Factory
{
    protected $model = BusinessProfile::class;

    public function definition(): array
    {
        $docType = fake()->randomElement(['cpf', 'cnpj']);
        $docNumber = $docType === 'cpf'
            ? fake('pt_BR')->cpf(false)
            : fake('pt_BR')->cnpj(false);

        $storeNames = [
            'Oficina Auto Elétrica Silva',
            'Padaria & Confeitaria Pão Quente',
            'Studio Bella Estética & Cabelo',
            'Marido de Aluguel Reformas Rápidas',
            'Clínica Veterinária Patas & Pelos',
            'Marcenaria Design Criativo',
            'Ateliê Costura & Ponto',
        ];

        $activities = [
            'Manutenção elétrica preventiva e corretiva automotiva, instalação de som e acessórios.',
            'Produção e venda diária de pães artesanais, bolos confeitados e lanches sob encomenda.',
            'Cortes unissex, penteados, hidratação capilar, manicure e estética facial.',
            'Pequenos reparos hidráulicos, alvenaria, pintura residencial e instalação de luminárias.',
            'Consultas veterinárias, vacinação, exames laboratoriais e banho & tosa para cães e gatos.',
        ];

        return [
            'user_id' => User::factory(),
            'display_name' => fake()->company(),
            'store_name' => fake()->randomElement($storeNames),
            'activity_description' => fake()->randomElement($activities),
            'document_type' => $docType,
            'document_number' => $docNumber,
            'phone' => '+55'.fake('pt_BR')->areaCode().fake()->numerify('9########'),
            'postal_code' => fake('pt_BR')->postcode(),
            'street' => fake('pt_BR')->streetName(),
            'number' => (string) fake()->buildingNumber(),
            'complement' => fake()->optional()->secondaryAddress(),
            'neighborhood' => 'Centro',
            'city' => fake('pt_BR')->city(),
            'state' => fake('pt_BR')->stateAbbr(),
            'logo_path' => null,
        ];
    }
}
