<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

interface RecentClient {
    id: number;
    name: string;
    phone_e164: string | null;
    created_at: string;
}

interface Metrics {
    total_clients: number;
    store_name: string;
    activity_description: string | null;
    recent_clients: RecentClient[];
}

interface Checklist {
    store_profile_completed: boolean;
    first_client_added: boolean;
}

defineProps<{
    metrics: Metrics;
    checklist: Checklist;
}>();
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
                <div class="mt-3 sm:mt-0 flex space-x-2">
                    <Link
                        :href="route('clients.create')"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                    >
                        + Novo Cliente
                    </Link>
                    <Link
                        :href="route('business-profile.edit')"
                        class="inline-flex items-center rounded-md bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                    >
                        Configurar Loja
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Checklist de Onboarding Inicial -->
                <div
                    v-if="!checklist.store_profile_completed || !checklist.first_client_added"
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

                    <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
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
                                <h4 class="text-sm font-medium text-gray-900">Configurar Nome e Ramo da Loja</h4>
                                <p class="text-xs text-gray-500">Defina o nome fantasia e descrição da atividade comercial</p>
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
                                <h4 class="text-sm font-medium text-gray-900">Cadastrar Primeiro Cliente</h4>
                                <p class="text-xs text-gray-500">Adicione contatos para emitir orçamentos e recibos rápidos</p>
                            </div>
                        </Link>
                    </div>
                </div>

                <!-- Cards de Indicadores (KPIs) -->
                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <!-- Total Clientes -->
                    <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                        <dt class="truncate text-sm font-medium text-gray-500">Carteira de Clientes</dt>
                        <dd class="mt-2 text-3xl font-bold tracking-tight text-indigo-600">
                            {{ metrics.total_clients }}
                        </dd>
                        <p class="mt-2 text-xs text-gray-400">Clientes ativos cadastrados</p>
                    </div>

                    <!-- Receitas do Mês (Preview Sprint 2) -->
                    <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                        <dt class="truncate text-sm font-medium text-gray-500">Entradas / Vendas</dt>
                        <dd class="mt-2 text-3xl font-bold tracking-tight text-emerald-600">
                            R$ 0,00
                        </dd>
                        <p class="mt-2 text-xs text-gray-400">Pronto para lançamentos do fluxo de caixa</p>
                    </div>

                    <!-- Despesas do Mês (Preview Sprint 2) -->
                    <div class="overflow-hidden rounded-xl bg-white p-6 shadow-sm border border-gray-100">
                        <dt class="truncate text-sm font-medium text-gray-500">Saídas / Despesas</dt>
                        <dd class="mt-2 text-3xl font-bold tracking-tight text-rose-600">
                            R$ 0,00
                        </dd>
                        <p class="mt-2 text-xs text-gray-400">Controle de custos e fornecedores</p>
                    </div>
                </div>

                <!-- Grid de Ações Rápidas & Clientes Recentes -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                    <!-- Clientes Recentes -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <div class="flex items-center justify-between border-b pb-4 mb-4">
                            <h3 class="text-base font-semibold text-gray-900">Clientes Recentes</h3>
                            <Link :href="route('clients.index')" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                                Ver todos &rarr;
                            </Link>
                        </div>

                        <div v-if="metrics.recent_clients.length === 0" class="text-center py-8">
                            <p class="text-sm text-gray-500">Nenhum cliente cadastrado ainda.</p>
                            <Link
                                :href="route('clients.create')"
                                class="mt-3 inline-block text-xs font-semibold text-indigo-600 underline"
                            >
                                Cadastrar primeiro cliente agora
                            </Link>
                        </div>

                        <ul v-else class="divide-y divide-gray-100">
                            <li v-for="c in metrics.recent_clients" :key="c.id" class="py-3 flex justify-between items-center hover:bg-gray-50 px-2 rounded-md transition">
                                <div>
                                    <Link :href="route('clients.show', c.id)" class="text-sm font-medium text-gray-900 hover:text-indigo-600">
                                        {{ c.name }}
                                    </Link>
                                    <p class="text-xs text-gray-500">{{ c.phone_e164 || 'Sem telefone' }}</p>
                                </div>
                                <span class="text-xs text-gray-400">
                                    {{ new Date(c.created_at).toLocaleDateString('pt-BR') }}
                                </span>
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
                                    :href="route('clients.create')"
                                    class="p-4 rounded-lg bg-gray-50 hover:bg-indigo-50 border border-gray-200 text-left transition group"
                                >
                                    <span class="text-xl">👥</span>
                                    <h4 class="mt-2 text-sm font-semibold text-gray-900 group-hover:text-indigo-700">Novo Cliente</h4>
                                    <p class="text-xs text-gray-500 mt-0.5">Cadastrar consumidor ou parceiro</p>
                                </Link>

                                <Link
                                    :href="route('business-profile.edit')"
                                    class="p-4 rounded-lg bg-gray-50 hover:bg-indigo-50 border border-gray-200 text-left transition group"
                                >
                                    <span class="text-xl">🏪</span>
                                    <h4 class="mt-2 text-sm font-semibold text-gray-900 group-hover:text-indigo-700">Dados da Loja</h4>
                                    <p class="text-xs text-gray-500 mt-0.5">Nome comercial e atividade</p>
                                </Link>

                                <div class="p-4 rounded-lg bg-gray-50 border border-gray-200 opacity-60 text-left cursor-not-allowed">
                                    <span class="text-xl">💰</span>
                                    <h4 class="mt-2 text-sm font-semibold text-gray-900">Novo Recibo</h4>
                                    <p class="text-xs text-gray-400 mt-0.5">Disponível no Sprint 2</p>
                                </div>

                                <div class="p-4 rounded-lg bg-gray-50 border border-gray-200 opacity-60 text-left cursor-not-allowed">
                                    <span class="text-xl">📊</span>
                                    <h4 class="mt-2 text-sm font-semibold text-gray-900">Relatório Caixa</h4>
                                    <p class="text-xs text-gray-400 mt-0.5">Disponível no Sprint 2</p>
                                </div>
                            </div>
                        </div>

                        <div class="mt-6 p-4 rounded-lg bg-indigo-50 border border-indigo-100 flex items-center justify-between">
                            <span class="text-xs text-indigo-900">💡 Precisa alterar sua senha ou e-mail de acesso?</span>
                            <Link :href="route('profile.edit')" class="text-xs font-semibold text-indigo-600 hover:underline">
                                Minha Conta
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
