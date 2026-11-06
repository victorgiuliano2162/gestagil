<?php

use App\Enums\AppointmentStatus;
use App\Enums\PaymentMethod;
use App\Models\Appointment;
use App\Models\BusinessProfile;
use App\Models\Client;
use App\Models\Receipt;
use App\Models\Transaction;
use App\Models\User;

test('full end-to-end journey of persona Marcio da Silva through all GestAgil modules', function () {
    // 1. Cadastro do Comerciante com Aceite dos Termos
    $registerResponse = $this->post(route('register'), [
        'name' => 'Marcio da Silva',
        'email' => 'marcio.eletrica@exemplo.com',
        'password' => 'SenhaForte123',
        'password_confirmation' => 'SenhaForte123',
        'terms' => true,
    ]);

    $registerResponse->assertRedirect(route('dashboard', absolute: false));
    $user = User::where('email', 'marcio.eletrica@exemplo.com')->first();
    expect($user)->not->toBeNull()
        ->and($user->terms_accepted_at)->not->toBeNull();

    // 2. Primeiro acesso ao Dashboard (verifica checklist de onboarding ativo)
    $dashboardResponse = $this->actingAs($user)->get(route('dashboard'));
    $dashboardResponse->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('checklist.store_profile_completed', false)
            ->where('checklist.first_client_added', false)
        );

    // 3. Configuração dos Dados Comerciais da Loja (Perfil Comercial)
    $profileResponse = $this->actingAs($user)
        ->from(route('business-profile.edit'))
        ->post(route('business-profile.update'), [
        'store_name' => 'Silva Auto Elétrica & Baterias',
        'display_name' => 'Marcio da Silva MEI',
        'activity_description' => 'Serviços automotivos, alternadores, motor de partida e baterias',
        'document_type' => 'cnpj',
        'document_number' => '45.123.789/0001-10',
        'phone' => '+5581988887777',
        'postal_code' => '50030-000',
        'street' => 'Rua do Comércio',
        'number' => '100',
        'neighborhood' => 'Centro',
        'city' => 'Recife',
        'state' => 'PE',
    ]);

    $profileResponse->assertRedirect(route('business-profile.edit'));
    expect($user->businessProfile->fresh()->store_name)->toBe('Silva Auto Elétrica & Baterias');

    // 4. Cadastro do Primeiro Cliente
    $clientResponse = $this->actingAs($user)->post(route('clients.store'), [
        'name' => 'Dona Tereza Souza',
        'phone' => '(81) 98765-4321',
        'document_number' => '987.654.321-99',
        'notes' => 'Cliente recomendada pelo irmão, veículo Palio 2015',
    ]);

    $client = Client::where('user_id', $user->id)->first();
    expect($client)->not->toBeNull()
        ->and($client->name)->toBe('Dona Tereza Souza')
        ->and($client->search_name)->toBe('dona tereza souza');
    $clientResponse->assertRedirect(route('clients.show', $client->id));

    // 5. Agendamento da Ordem de Serviço / Atendimento
    $appointmentResponse = $this->actingAs($user)->post(route('appointments.store'), [
        'client_id' => $client->id,
        'service_description' => 'Troca de bateria 60Ah e revisão do alternador do Palio',
        'service_at' => now()->addHours(2)->format('Y-m-d H:i:s'),
        'expected_amount' => '450,00',
        'technical_notes' => 'Testar fuga de corrente após instalação',
    ]);

    $appointment = Appointment::where('user_id', $user->id)->first();
    expect($appointment)->not->toBeNull()
        ->and($appointment->status)->toBe(AppointmentStatus::InProgress)
        ->and($appointment->expected_amount_cents)->toBe(45000);
    $appointmentResponse->assertRedirect(route('appointments.show', $appointment->id));

    // Conclusão do Atendimento
    $this->actingAs($user)->patch(route('appointments.status', $appointment->id), [
        'status' => 'completed',
    ]);
    expect($appointment->fresh()->status)->toBe(AppointmentStatus::Completed);

    // 6. Lançamento Financeiro no Fluxo de Caixa (vinculado ao atendimento)
    $category = $user->categories()->where('type', 'income')->first();
    $transactionResponse = $this->actingAs($user)->post(route('transactions.store'), [
        'type' => 'income',
        'amount' => '450,00',
        'occurred_on' => now()->toDateString(),
        'description' => 'Pagamento do serviço do Palio da Dona Tereza',
        'payment_method' => 'pix',
        'category_id' => $category?->id,
        'client_id' => $client->id,
        'appointment_id' => $appointment->id,
    ]);

    $transactionResponse->assertRedirect(route('transactions.index'));
    $transaction = Transaction::where('appointment_id', $appointment->id)->first();
    expect($transaction)->not->toBeNull()
        ->and($transaction->amount_cents)->toBe(45000)
        ->and($transaction->client_id)->toBe($client->id);

    // 7. Emissão do Recibo Oficial com numeração NNNN/AAAA
    $receiptResponse = $this->actingAs($user)->post(route('receipts.store'), [
        'transaction_id' => $transaction->id,
        'payer_name' => 'Dona Tereza Souza',
        'payer_document' => '987.654.321-99',
        'description' => 'Serviço elétrico e fornecimento de bateria 60Ah com 1 ano de garantia',
        'payment_method' => 'pix',
    ]);

    $receipt = Receipt::where('transaction_id', $transaction->id)->first();
    expect($receipt)->not->toBeNull()
        ->and($receipt->number)->toBe(1)
        ->and($receipt->year)->toBe((int) now()->format('Y'))
        ->and($receipt->formatted_number)->toBe(sprintf('0001/%04d', now()->format('Y')))
        ->and($receipt->amount_in_words)->toBe('quatrocentos e cinquenta reais')
        ->and($receipt->issuer_snapshot['store_name'])->toBe('Silva Auto Elétrica & Baterias');

    $receiptResponse->assertRedirect(route('receipts.show', $receipt->id));

    // 8. Download do PDF e Verificação Pública do Recibo (sem login)
    $pdfResponse = $this->actingAs($user)->get(route('receipts.pdf', $receipt->id));
    $pdfResponse->assertOk();
    expect($pdfResponse->headers->get('content-type'))->toBe('application/pdf');

    // Consulta pública deslogado via chave UUID
    $publicVerification = $this->get(route('receipts.public.show', $receipt->code));
    $publicVerification->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Public/ReceiptVerification')
            ->where('receipt.payer_name', 'Dona Tereza Souza')
            ->where('receipt.amount_cents', 45000)
        );

    // 9. Painel de Controle e Relatórios consolidados
    $updatedDashboard = $this->actingAs($user)->get(route('dashboard'));
    $updatedDashboard->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('checklist.store_profile_completed', true)
            ->where('checklist.first_client_added', true)
            ->where('checklist.first_transaction_recorded', true)
            ->where('metrics.month_income_cents', 45000)
            ->where('metrics.month_balance_cents', 45000)
        );

    $reportResponse = $this->actingAs($user)->get(route('reports.index', ['tab' => 'faturamento']));
    $reportResponse->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('report.income_cents', 45000)
            ->where('report.net_cents', 45000)
        );

    // 10. Garantia de Isolamento Multi-tenant: Usuário B não acessa nada desta jornada
    $userB = User::factory()->create();
    $this->actingAs($userB)->get(route('clients.show', $client->id))->assertNotFound();
    $this->actingAs($userB)->get(route('appointments.show', $appointment->id))->assertNotFound();
    $this->actingAs($userB)->get(route('receipts.show', $receipt->id))->assertNotFound();
});
