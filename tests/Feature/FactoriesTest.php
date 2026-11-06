<?php

use App\Models\BusinessProfile;
use App\Models\Client;
use App\Models\TransactionCategory;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;

test('factories can create entities and maintain relationships', function () {
    $user = User::factory()
        ->withBusinessProfile(['store_name' => 'Oficina Mecânica Alpha'])
        ->withDefaultCategories()
        ->withClients(5)
        ->create();

    expect($user->businessProfile)->not->toBeNull()
        ->and($user->businessProfile->store_name)->toBe('Oficina Mecânica Alpha')
        ->and($user->categories()->count())->toBe(9)
        ->and($user->clients()->count())->toBe(5);

    $client = $user->clients()->first();
    expect($client->search_name)->not->toBeEmpty();
});

test('individual factories can be instantiated', function () {
    $profile = BusinessProfile::factory()->create();
    expect($profile->user)->not->toBeNull();

    $category = TransactionCategory::factory()->income()->create();
    expect($category->type)->toBe('income')
        ->and($category->user)->not->toBeNull();

    $client = Client::factory()->create();
    expect($client->user)->not->toBeNull();
});

test('database seeder populates database successfully', function () {
    $this->seed(DatabaseSeeder::class);

    $testUser = User::where('email', 'test@example.com')->first();
    expect($testUser)->not->toBeNull()
        ->and($testUser->businessProfile)->not->toBeNull()
        ->and($testUser->businessProfile->store_name)->toBe('Auto Elétrica Silva')
        ->and($testUser->categories()->count())->toBe(9)
        ->and($testUser->clients()->count())->toBe(25);

    // Total users seeded = 3 (Carlos + Mariana + Rodrigo)
    expect(User::count())->toBe(3)
        ->and(Client::count())->toBe(25 + 12 + 8) // 45 clients
        ->and(BusinessProfile::count())->toBe(3);
});
