<?php

namespace App\Policies;

use App\Models\TransactionCategory;
use App\Models\User;

class TransactionCategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, TransactionCategory $category): bool
    {
        return $category->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, TransactionCategory $category): bool
    {
        return $category->user_id === $user->id;
    }

    public function delete(User $user, TransactionCategory $category): bool
    {
        return $category->user_id === $user->id;
    }
}
