<?php

namespace App\Http\Controllers;

use App\Models\TransactionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class TransactionCategoryController extends Controller
{
    public function index(): Response
    {
        $categories = TransactionCategory::query()
            ->withCount('transactions')
            ->orderBy('type')
            ->orderBy('name')
            ->get(['id', 'type', 'name', 'color']);

        return Inertia::render('Categories/Index', [
            'categories' => $categories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'type' => ['required', 'in:income,expense'],
            'name' => ['required', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:20'],
        ], [
            'name.required' => 'Informe o nome da categoria.',
            'type.required' => 'Informe o tipo da categoria.',
        ]);

        $nameKey = Str::slug($data['name']);
        $exists = TransactionCategory::where('type', $data['type'])
            ->where('name_key', $nameKey)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'Já existe uma categoria com esse nome para este tipo.',
            ]);
        }

        TransactionCategory::create([
            'type' => $data['type'],
            'name' => $data['name'],
            'name_key' => $nameKey,
            'color' => $data['color'] ?: null,
        ]);

        return back()->with('success', 'Categoria criada com sucesso!');
    }

    public function update(Request $request, TransactionCategory $category): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:80'],
            'color' => ['nullable', 'string', 'max:20'],
        ], [
            'name.required' => 'Informe o nome da categoria.',
        ]);

        $nameKey = Str::slug($data['name']);
        $exists = TransactionCategory::where('type', $category->type)
            ->where('name_key', $nameKey)
            ->where('id', '!=', $category->id)
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => 'Já existe uma categoria com esse nome para este tipo.',
            ]);
        }

        $category->update([
            'name' => $data['name'],
            'name_key' => $nameKey,
            'color' => $data['color'] ?: null,
        ]);

        return back()->with('success', 'Categoria atualizada com sucesso!');
    }

    public function destroy(TransactionCategory $category): RedirectResponse
    {
        if ($category->transactions()->exists()) {
            return back()->with('error', 'Não é possível excluir uma categoria que possui lançamentos vinculados.');
        }

        $category->delete();

        return back()->with('success', 'Categoria removida com sucesso!');
    }
}
