<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClientRequest;
use App\Models\Client;
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

    public function show(Client $client): Response
    {
        return Inertia::render('Clients/Show', [
            'client' => $client,
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
