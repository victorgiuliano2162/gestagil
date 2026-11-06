<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Category {
    id: number;
    name: string;
    color: string | null;
}

interface Client {
    id: number;
    name: string;
}

interface TransactionItem {
    id: number;
    type: 'income' | 'expense';
    amount_cents: number;
    occurred_on: string;
    description: string;
    payment_method: string;
    category: Category | null;
    client: Client | null;
}

interface PaginatedTransactions {
    data: TransactionItem[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface Summary {
    income_cents: number;
    expense_cents: number;
    net_cents: number;
}

const props = defineProps<{
    transactions: PaginatedTransactions;
    summary: Summary;
    filters: { type?: string; month: string };
}>();

const month = ref(props.filters.month);
const selectedType = ref(props.filters.type || '');

const applyFilters = () => {
    router.get(
        route('transactions.index'),
        { month: month.value, type: selectedType.value || undefined },
        { preserveState: true, replace: true }
    );
};

const formatCurrency = (cents: number) => {
    return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
};

const deleteTransaction = (id: number) => {
    if (confirm('Tem certeza que deseja remover este lançamento financeiro?')) {
        router.delete(route('transactions.destroy', id));
    }
};
</script>

<template>
    <Head title="Fluxo de Caixa - GestÁgil" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-gray-900">
                        Fluxo de Caixa e Lançamentos
                    </h2>
                    <p class="text-xs text-gray-500 mt-1">
                        Controle rápido de entradas e saídas do comércio
                    </p>
                </div>
                <div class="flex items-center space-x-2">
                    <Link
                        :href="route('transactions.create', { type: 'expense' })"
                        class="rounded-md bg-rose-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-500"
                    >
                        - Nova Despesa
                    </Link>
                    <Link
                        :href="route('transactions.create', { type: 'income' })"
                        class="rounded-md bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500"
                    >
                        + Nova Entrada
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Cards de Resumo Mensal -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                        <span class="text-xs font-semibold text-emerald-700 uppercase">Total de Entradas</span>
                        <p class="text-2xl font-black text-emerald-600 mt-1">
                            {{ formatCurrency(summary.income_cents) }}
                        </p>
                        <span class="text-[11px] text-gray-400">Vendas e serviços recebidos</span>
                    </div>

                    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                        <span class="text-xs font-semibold text-rose-700 uppercase">Total de Saídas</span>
                        <p class="text-2xl font-black text-rose-600 mt-1">
                            {{ formatCurrency(summary.expense_cents) }}
                        </p>
                        <span class="text-[11px] text-gray-400">Despesas, custos e compras</span>
                    </div>

                    <div class="bg-white p-5 rounded-xl border border-gray-100 shadow-sm">
                        <span class="text-xs font-semibold text-gray-700 uppercase">Resultado Líquido</span>
                        <p class="text-2xl font-black mt-1" :class="summary.net_cents >= 0 ? 'text-indigo-600' : 'text-rose-600'">
                            {{ formatCurrency(summary.net_cents) }}
                        </p>
                        <span class="text-[11px] text-gray-400">Saldo apurado no período</span>
                    </div>
                </div>

                <!-- Tabela de Lançamentos -->
                <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                    <div class="mb-6 flex flex-col sm:flex-row items-center justify-between gap-4">
                        <div class="flex items-center space-x-3 w-full sm:w-auto">
                            <input
                                v-model="month"
                                type="month"
                                @change="applyFilters"
                                class="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <div class="flex items-center space-x-2 w-full sm:w-auto">
                            <button
                                @click="selectedType = ''; applyFilters()"
                                class="px-3 py-1.5 text-xs font-semibold rounded-lg transition"
                                :class="!selectedType ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            >
                                Todos
                            </button>
                            <button
                                @click="selectedType = 'income'; applyFilters()"
                                class="px-3 py-1.5 text-xs font-semibold rounded-lg transition"
                                :class="selectedType === 'income' ? 'bg-emerald-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            >
                                Entradas
                            </button>
                            <button
                                @click="selectedType = 'expense'; applyFilters()"
                                class="px-3 py-1.5 text-xs font-semibold rounded-lg transition"
                                :class="selectedType === 'expense' ? 'bg-rose-600 text-white' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            >
                                Saídas
                            </button>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50 text-xs font-medium uppercase tracking-wider text-gray-500">
                                <tr>
                                    <th class="px-4 py-3 text-left">Data</th>
                                    <th class="px-4 py-3 text-left">Descrição</th>
                                    <th class="px-4 py-3 text-left">Categoria</th>
                                    <th class="px-4 py-3 text-left">Cliente / Parceiro</th>
                                    <th class="px-4 py-3 text-left">Meio</th>
                                    <th class="px-4 py-3 text-right">Valor</th>
                                    <th class="px-4 py-3 text-right">Ação</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white text-sm">
                                <tr v-if="transactions.data.length === 0">
                                    <td colspan="7" class="py-10 text-center text-gray-500">
                                        Nenhum lançamento registrado neste mês.
                                    </td>
                                </tr>
                                <tr v-for="t in transactions.data" :key="t.id" class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600 font-medium">
                                        {{ new Date(t.occurred_on + 'T12:00:00').toLocaleDateString('pt-BR') }}
                                    </td>
                                    <td class="px-4 py-3 font-semibold text-gray-900">
                                        {{ t.description }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                                        <span v-if="t.category" class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-800">
                                            {{ t.category.name }}
                                        </span>
                                        <span v-else class="text-gray-400 text-xs">Sem categoria</span>
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-gray-600">
                                        {{ t.client?.name || '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-xs uppercase font-bold text-gray-500">
                                        {{ t.payment_method }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right font-bold"
                                        :class="t.type === 'income' ? 'text-emerald-600' : 'text-rose-600'">
                                        {{ t.type === 'income' ? '+' : '-' }} {{ formatCurrency(t.amount_cents) }}
                                    </td>
                                    <td class="whitespace-nowrap px-4 py-3 text-right text-xs space-x-2">
                                        <Link
                                            v-if="t.type === 'income'"
                                            :href="route('receipts.create', { transaction_id: t.id })"
                                            class="inline-flex items-center text-indigo-600 hover:text-indigo-900 font-semibold bg-indigo-50 hover:bg-indigo-100 px-2.5 py-1 rounded transition"
                                        >
                                            📄 Recibo
                                        </Link>
                                        <button
                                            @click="deleteTransaction(t.id)"
                                            class="text-rose-600 hover:text-rose-900 font-semibold"
                                        >
                                            Excluir
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <div class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4" v-if="transactions.last_page > 1">
                        <Link
                            v-if="transactions.prev_page_url"
                            :href="transactions.prev_page_url"
                            class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Anterior
                        </Link>
                        <span v-else></span>

                        <span class="text-xs text-gray-500">
                            Página {{ transactions.current_page }} de {{ transactions.last_page }}
                        </span>

                        <Link
                            v-if="transactions.next_page_url"
                            :href="transactions.next_page_url"
                            class="rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Próxima
                        </Link>
                        <span v-else></span>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
