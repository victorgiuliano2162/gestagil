<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClientController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');

        $clients = Client::query()
            ->when($search, function ($query, $search) {
                $term = mb_strtolower($search);
                $query->where(function ($q) use ($term, $search) {
                    $q->where('search_name', 'like', "%{$term}%")
                        ->orWhere('document_number', 'like', "%{$search}%")
                        ->orWhere('phone_e164', 'like', "%{$search}%");
                });
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Clients/Create');
    }

    public function store(ClientRequest $request): RedirectResponse
    {
        $client = Client::create($request->validated());

        return redirect()->route('clients.show', $client)
            ->with('success', 'Cliente cadastrado com sucesso!');
    }

    public function quickStore(ClientRequest $request): JsonResponse
    {
        $client = Client::create($request->validated());

        return response()->json([
            'client' => $client->only(['id', 'name', 'phone_e164']),
        ], 201);
    }

    public function show(Client $client): Response
    {
        $appointments = $client->appointments()
            ->orderByDesc('service_at')
            ->limit(20)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'service_description' => $a->service_description,
                'service_at' => $a->service_at->toIso8601String(),
                'status' => $a->status->value,
                'status_label' => $a->status->label(),
                'expected_amount_cents' => $a->expected_amount_cents,
            ]);

        $transactions = $client->transactions()
            ->with('category:id,name')
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->limit(20)
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'type' => $t->type,
                'amount_cents' => $t->amount_cents,
                'occurred_on' => $t->occurred_on->format('Y-m-d'),
                'description' => $t->description,
                'payment_method_label' => $t->payment_method->label(),
                'category_name' => $t->category?->name,
            ]);

        $totalSpentCents = (int) $client->transactions()
            ->where('type', 'income')
            ->sum('amount_cents');

        return Inertia::render('Clients/Show', [
            'client' => $client,
            'appointments' => $appointments,
            'transactions' => $transactions,
            'stats' => [
                'total_spent_cents' => $totalSpentCents,
                'appointments_count' => $client->appointments()->count(),
                'transactions_count' => $client->transactions()->count(),
            ],
        ]);
    }

    public function edit(Client $client): Response
    {
        return Inertia::render('Clients/Edit', [
            'client' => $client,
        ]);
    }

    public function update(ClientRequest $request, Client $client): RedirectResponse
    {
        $client->update($request->validated());

        return redirect()->route('clients.show', $client)
            ->with('success', 'Cliente atualizado com sucesso!');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return redirect()->route('clients.index')
            ->with('success', 'Cliente arquivado com sucesso!');
    }
}
