<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface RecentClient {
    id: number;
    name: string;
    phone_e164: string | null;
    created_at: string;
}

interface RecentTransaction {
    id: number;
    type: 'income' | 'expense';
    amount_cents: number;
    description: string;
    occurred_on: string;
    payment_method: string;
    category?: { id: number; name: string } | null;
    client?: { id: number; name: string } | null;
}

interface Metrics {
    total_clients: number;
    store_name: string;
    activity_description: string | null;
    month_income_cents: number;
    month_expense_cents: number;
    month_balance_cents: number;
    in_progress_appointments: number;
    recent_clients: RecentClient[];
    recent_transactions?: RecentTransaction[];
}

interface Checklist {
    store_profile_completed: boolean;
    first_client_added: boolean;
    first_transaction_recorded?: boolean;
}

const props = defineProps<{
    metrics: Metrics;
    checklist: Checklist;
}>();

const formatCurrency = (cents: number): string => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(cents / 100);
};

const formatDate = (dateStr: string): string => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('pt-BR');
};
</script>

<template>
    <Head title="Painel de Controle - GestÁgil" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-gray-900">
                        {{ metrics.store_name }}
                    </h2>
                    <p v-if="metrics.activity_description" class="text-sm text-gray-500 mt-0.5">
                        {{ metrics.activity_description }}
                    </p>
                </div>
                <div class="mt-3 sm:mt-0 flex flex-wrap gap-2">
                    <Link
                        :href="route('transactions.create', { type: 'income' })"
                        class="inline-flex items-center rounded-md bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-emerald-600"
                    >
                        + Entrada
                    </Link>
                    <Link
                        :href="route('transactions.create', { type: 'expense' })"
                        class="inline-flex items-center rounded-md bg-rose-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-rose-600"
                    >
                        - Despesa
                    </Link>
                    <Link
                        :href="route('appointments.create')"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                    >
                        + Atendimento
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Checklist de Onboarding Inicial -->
                <div
                    v-if="!checklist.store_profile_completed || !checklist.first_client_added || !checklist.first_transaction_recorded"
                    class="rounded-xl border border-indigo-100 bg-gradient-to-r from-indigo-50 to-white p-6 shadow-sm"
                >
                    <div class="flex items-start justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-indigo-900">
                                Primeiros passos para organizar seu negócio 🚀
                            </h3>
                            <p class="text-sm text-indigo-700 mt-1">
                                Complete o cadastro inicial para liberar todo o potencial do GestÁgil:
                            </p>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <Link
                            :href="route('business-profile.edit')"
                            class="flex items-center p-3 rounded-lg border transition"
                            :class="checklist.store_profile_completed ? 'bg-emerald-50 border-emerald-200' : 'bg-white border-gray-200 hover:border-indigo-300'"
                        >
                            <span class="mr-3 flex h-7 w-7 items-center justify-center rounded-full text-sm font-bold"
                                  :class="checklist.store_profile_completed ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-600'">
                                {{ checklist.store_profile_completed ? '✓' : '1' }}
                            </span>
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Configurar Loja</h4>
                                <p class="text-xs text-gray-500">Nome fantasia e atividade</p>
                            </div>
                        </Link>

                        <Link
                            :href="route('clients.create')"
                            class="flex items-center p-3 rounded-lg border transition"
                            :class="checklist.first_client_added ? 'bg-emerald-50 border-emerald-200' : 'bg-white border-gray-200 hover:border-indigo-300'"
                        >
                            <span class="mr-3 flex h-7 w-7 items-center justify-center rounded-full text-sm font-bold"
                                  :class="checklist.first_client_added ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-600'">
                                {{ checklist.first_client_added ? '✓' : '2' }}
                            </span>
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Cadastrar Cliente</h4>
                                <p class="text-xs text-gray-500">Adicione contatos para atendimentos</p>
                            </div>
                        </Link>

                        <Link
                            :href="route('transactions.create', { type: 'income' })"
                            class="flex items-center p-3 rounded-lg border transition"
                            :class="checklist.first_transaction_recorded ? 'bg-emerald-50 border-emerald-200' : 'bg-white border-gray-200 hover:border-indigo-300'"
                        >
                            <span class="mr-3 flex h-7 w-7 items-center justify-center rounded-full text-sm font-bold"
                                  :class="checklist.first_transaction_recorded ? 'bg-emerald-600 text-white' : 'bg-gray-200 text-gray-600'">
                                {{ checklist.first_transaction_recorded ? '✓' : '3' }}
                            </span>
                            <div>
                                <h4 class="text-sm font-medium text-gray-900">Registrar Venda / Entrada</h4>
                                <p class="text-xs text-gray-500">Registre sua primeira venda no caixa</p>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Cards de Indicadores (KPIs) -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <!-- Entradas do Mês -->
                    <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                        <dt class="truncate text-sm font-medium text-gray-500">Entradas (Mês)</dt>
                        <dd class="mt-2 text-2xl font-bold tracking-tight text-emerald-600">
                            {{ formatCurrency(metrics.month_income_cents) }}
                        </dd>
                        <p class="mt-2 text-xs text-gray-400">Vendas e recebimentos</p>
                    </div>

                    <!-- Despesas do Mês -->
                    <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                        <dt class="truncate text-sm font-medium text-gray-500">Despesas (Mês)</dt>
                        <dd class="mt-2 text-2xl font-bold tracking-tight text-rose-600">
                            {{ formatCurrency(metrics.month_expense_cents) }}
                        </dd>
                        <p class="mt-2 text-xs text-gray-400">Contas e custos operacionais</p>
                    </div>

                    <!-- Saldo Líquido do Mês -->
                    <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                        <dt class="truncate text-sm font-medium text-gray-500">Saldo Líquido</dt>
                        <dd class="mt-2 text-2xl font-bold tracking-tight"
                            :class="metrics.month_balance_cents >= 0 ? 'text-indigo-600' : 'text-rose-600'">
                            {{ formatCurrency(metrics.month_balance_cents) }}
                        </dd>
                        <p class="mt-2 text-xs text-gray-400">Entradas menos despesas</p>
                    </div>

                    <!-- Atendimentos em Andamento -->
                    <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                        <dt class="truncate text-sm font-medium text-gray-500">Atendimentos Abertos</dt>
                        <dd class="mt-2 text-2xl font-bold tracking-tight text-amber-600">
                            {{ metrics.in_progress_appointments }}
                        </dd>
                        <p class="mt-2 text-xs text-gray-400">Serviços e ordens em andamento</p>
                    </div>
                </div>

                <!-- Grid de Ações Rápidas & Clientes Recentes -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Movimentações Recentes do Caixa -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between border-b pb-4 mb-4">
                            <h3 class="text-base font-semibold text-gray-900">Últimos Lançamentos no Caixa</h3>
                            <Link :href="route('transactions.index')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                Ver extrato completo &rarr;
                            </Link>
                        </div>

                        <div v-if="!metrics.recent_transactions || metrics.recent_transactions.length === 0" class="text-center py-8">
                            <p class="text-sm text-gray-500">Nenhum lançamento no caixa ainda.</p>
                            <Link
                                :href="route('transactions.create', { type: 'income' })"
                                class="mt-3 inline-block text-xs font-semibold text-emerald-600 underline"
                            >
                                Registrar primeira entrada agora
                            </Link>
                        </div>

                        <ul v-else class="divide-y divide-gray-100">
                            <li v-for="t in metrics.recent_transactions" :key="t.id" class="py-3 flex justify-between items-center hover:bg-gray-50 px-2 rounded-md transition">
                                <div class="min-w-0 pr-2">
                                    <p class="text-sm font-medium text-gray-900 truncate">
                                        {{ t.description }}
                                    </p>
                                    <p class="text-xs text-gray-500 truncate">
                                        {{ t.category?.name || 'Geral' }}
                                        <span v-if="t.client"> • {{ t.client.name }}</span>
                                    </p>
                                </div>
                                <div class="text-right flex-shrink-0">
                                    <span class="text-sm font-bold" :class="t.type === 'income' ? 'text-emerald-600' : 'text-rose-600'">
                                        {{ t.type === 'income' ? '+' : '-' }} {{ formatCurrency(t.amount_cents) }}
                                    </span>
                                    <p class="text-xs text-gray-400">{{ formatDate(t.occurred_on) }}</p>
                                </div>
                            </li>
                        </ul>
                    </div>

                    <!-- Atalhos Rápidos da Loja -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex flex-col justify-between">
                        <div>
                            <h3 class="text-base font-semibold text-gray-900 border-b pb-4 mb-4">
                                Atalhos do Comerciante
                            </h3>
                            <div class="grid grid-cols-2 gap-4">
                                <Link
                                    :href="route('transactions.create', { type: 'income' })"
                                    class="p-4 rounded-lg bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 text-left transition group"
                                >
                                    <span class="text-xl">💰</span>
                                    <h4 class="mt-2 text-sm font-semibold text-emerald-900">Nova Venda / Entrada</h4>
                                    <p class="text-xs text-emerald-700 mt-0.5">Entrada no caixa ou PIX</p>
                                </Link>

                                <Link
                                    :href="route('transactions.create', { type: 'expense' })"
                                    class="p-4 rounded-lg bg-rose-50 hover:bg-rose-100 border border-rose-200 text-left transition group"
                                >
                                    <span class="text-xl">💸</span>
                                    <h4 class="mt-2 text-sm font-semibold text-rose-900">Nova Despesa</h4>
                                    <p class="text-xs text-rose-700 mt-0.5">Pagamento ou custo</p>
                                </Link>

                                <Link
                                    :href="route('appointments.create')"
                                    class="p-4 rounded-lg bg-gray-50 hover:bg-indigo-50 border border-gray-200 text-left transition group"
                                >
                                    <span class="text-xl">📅</span>
                                    <h4 class="mt-2 text-sm font-semibold text-gray-900 group-hover:text-indigo-700">Novo Atendimento</h4>
                                    <p class="text-xs text-gray-500 mt-0.5">Agendar serviço ou ordem</p>
                                </Link>

                                <Link
                                    :href="route('transactions.index')"
                                    class="p-4 rounded-lg bg-gray-50 hover:bg-indigo-50 border border-gray-200 text-left transition group"
                                >
                                    <span class="text-xl">📊</span>
                                    <h4 class="mt-2 text-sm font-semibold text-gray-900 group-hover:text-indigo-700">Fluxo de Caixa</h4>
                                    <p class="text-xs text-gray-500 mt-0.5">Extrato e fechamento</p>
                                </Link>
                            </div>
                        </div>

                        <div class="mt-6 p-4 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-between">
                            <span class="text-xs text-indigo-900">💡 Precisa alterar seus dados comerciais ou logotipo?</span>
                            <Link :href="route('business-profile.edit')" class="text-xs font-semibold text-indigo-600 hover:underline">
                                Dados da Loja
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
