<?php

use App\Models\Transaction;
use App\Models\TransactionCategory;
use App\Models\User;

test('user can manage custom transaction categories with tenant isolation', function () {
    $user = User::factory()->withDefaultCategories()->create();
    $otherUser = User::factory()->withDefaultCategories()->create();

    $this->actingAs($user)->get(route('categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('Categories/Index'));

    // Create custom category
    $this->actingAs($user)->post(route('categories.store'), [
        'type' => 'income',
        'name' => 'Consultoria Especial',
        'color' => '#3b82f6',
    ])->assertRedirect();

    $category = TransactionCategory::where('user_id', $user->id)
        ->where('name', 'Consultoria Especial')
        ->first();
    expect($category)->not->toBeNull();

    // Duplicate name for same type fails
    $this->actingAs($user)->post(route('categories.store'), [
        'type' => 'income',
        'name' => 'Consultoria Especial',
    ])->assertSessionHasErrors(['name']);

    // Update category
    $this->actingAs($user)->put(route('categories.update', $category), [
        'name' => 'Consultoria VIP',
        'color' => '#10b981',
    ])->assertRedirect();

    expect($category->fresh()->name)->toBe('Consultoria VIP');

    // Cross-tenant update/delete -> 404
    $this->actingAs($otherUser)->put(route('categories.update', $category), [
        'name' => 'Hack',
    ])->assertNotFound();
    $this->actingAs($otherUser)->delete(route('categories.destroy', $category))
        ->assertNotFound();

    // Category in use cannot be deleted
    Transaction::create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'type' => 'income',
        'amount_cents' => 10000,
        'occurred_on' => now()->toDateString(),
        'description' => 'Consultoria',
    ]);

    $this->actingAs($user)->delete(route('categories.destroy', $category))
        ->assertSessionHas('error');
    expect(TransactionCategory::find($category->id))->not->toBeNull();

    // Unused category can be deleted
    $unused = TransactionCategory::create([
        'user_id' => $user->id,
        'type' => 'expense',
        'name' => 'Temporária',
    ]);

    $this->actingAs($user)->delete(route('categories.destroy', $unused))
        ->assertSessionHas('success');
    expect(TransactionCategory::find($unused->id))->toBeNull();
});
