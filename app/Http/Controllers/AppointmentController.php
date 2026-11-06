<?php

namespace App\Http\Controllers;

use App\Enums\AppointmentStatus;
use App\Http\Requests\AppointmentRequest;
use App\Models\Appointment;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AppointmentController extends Controller
{
    public function index(Request $request): Response
    {
        $status = $request->input('status');
        $search = $request->input('search');

        $appointments = Appointment::query()
            ->with('client:id,name,phone_e164')
            ->when($status, function ($query, $status) {
                $query->where('status', $status);
            })
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('service_description', 'like', "%{$search}%")
                      ->orWhereHas('client', function ($clientQ) use ($search) {
                          $clientQ->where('name', 'like', "%{$search}%");
                      });
                });
            })
            ->orderByDesc('service_at')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Appointments/Index', [
            'appointments' => $appointments,
            'filters' => [
                'status' => $status,
                'search' => $search,
            ],
            'statuses' => collect(AppointmentStatus::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
                'color' => $s->color(),
            ]),
        ]);
    }

    public function create(Request $request): Response
    {
        $clients = Client::orderBy('name')->get(['id', 'name', 'phone_e164']);

        return Inertia::render('Appointments/Create', [
            'clients' => $clients,
            'selectedClientId' => $request->integer('client_id') ?: null,
        ]);
    }

    public function store(AppointmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        if (isset($data['expected_amount'])) {
            $data['expected_amount_cents'] = (int) round($data['expected_amount'] * 100);
            unset($data['expected_amount']);
        }

        $appointment = Appointment::create($data);

        return redirect()->route('appointments.show', $appointment)
            ->with('success', 'Atendimento agendado com sucesso!');
    }

    public function show(Appointment $appointment): Response
    {
        $appointment->load('client');

        return Inertia::render('Appointments/Show', [
            'appointment' => [
                ...$appointment->toArray(),
                'status_label' => $appointment->status->label(),
                'status_color' => $appointment->status->color(),
            ],
        ]);
    }

    public function edit(Appointment $appointment): Response
    {
        $clients = Client::orderBy('name')->get(['id', 'name', 'phone_e164']);

        return Inertia::render('Appointments/Edit', [
            'appointment' => [
                ...$appointment->toArray(),
                'expected_amount' => $appointment->expected_amount_cents ? $appointment->expected_amount_cents / 100 : null,
                'service_at_formatted' => $appointment->service_at->format('Y-m-d\TH:i'),
            ],
            'clients' => $clients,
        ]);
    }

    public function update(AppointmentRequest $request, Appointment $appointment): RedirectResponse
    {
        $data = $request->validated();
        if (isset($data['expected_amount'])) {
            $data['expected_amount_cents'] = (int) round($data['expected_amount'] * 100);
            unset($data['expected_amount']);
        } else {
            $data['expected_amount_cents'] = null;
        }

        $appointment->update($data);

        return redirect()->route('appointments.show', $appointment)
            ->with('success', 'Atendimento atualizado com sucesso!');
    }

    public function updateStatus(Request $request, Appointment $appointment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
            'cancel_reason' => ['nullable', 'string', 'max:500'],
        ]);

        $newStatus = AppointmentStatus::tryFrom($validated['status']);
        if (! $newStatus) {
            return back()->withErrors(['status' => 'Status inválido.']);
        }

        if (! $appointment->status->canTransitionTo($newStatus)) {
            return back()->withErrors(['status' => "Não é possível alterar de {$appointment->status->label()} para {$newStatus->label()}."]);
        }

        $updateData = ['status' => $newStatus];

        if ($newStatus === AppointmentStatus::Completed) {
            $updateData['completed_at'] = now();
        } elseif ($newStatus === AppointmentStatus::Canceled) {
            $updateData['canceled_at'] = now();
            $updateData['cancel_reason'] = $validated['cancel_reason'] ?? null;
        }

        $appointment->update($updateData);

        return back()->with('success', 'Status do atendimento atualizado!');
    }

    public function destroy(Appointment $appointment): RedirectResponse
    {
        $appointment->delete();

        return redirect()->route('appointments.index')
            ->with('success', 'Atendimento removido com sucesso!');
    }
}
