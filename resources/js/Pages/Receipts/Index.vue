<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Client {
    id: number;
    name: string;
}

interface Transaction {
    id: number;
    amount_cents: number;
    occurred_on: string;
}

interface ReceiptItem {
    id: number;
    formatted_number: string;
    code: string;
    status: 'issued' | 'canceled';
    issued_at: string;
    amount_cents: number;
    formatted_amount: string;
    payment_method: string;
    description: string;
    payer_name: string;
    client?: Client | null;
    transaction?: Transaction | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface ReceiptsPagination {
    data: ReceiptItem[];
    links: PaginationLink[];
    total: number;
}

const props = defineProps<{
    receipts: ReceiptsPagination;
    filters: {
        status?: string;
        q?: string;
    };
}>();

const status = ref(props.filters.status || '');
const search = ref(props.filters.q || '');

const applyFilters = () => {
    router.get(
        route('receipts.index'),
        {
            status: status.value || undefined,
            q: search.value || undefined,
        },
        { preserveState: true, replace: true }
    );
};

let searchTimer: ReturnType<typeof setTimeout> | null = null;
watch(search, () => {
    if (searchTimer) clearTimeout(searchTimer);
    searchTimer = setTimeout(() => {
        applyFilters();
    }, 400);
});

const formatDate = (dateStr: string): string => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleDateString('pt-BR');
};
</script>

<template>
    <Head title="Recibos Emitidos - GestÁgil" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-gray-900">
                        Recibos e Comprovantes
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Histórico oficial de recibos digitais emitidos aos seus clientes
                    </p>
                </div>
                <div class="mt-3 sm:mt-0">
                    <Link
                        :href="route('transactions.index')"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                    >
                        + Emitir a partir do Caixa
                    </Link>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Filtros -->
                <div class="bg-white p-4 rounded-xl shadow-sm border border-gray-100 flex flex-col sm:flex-row gap-4 items-center justify-between">
                    <div class="w-full sm:w-1/3">
                        <input
                            v-model="search"
                            type="text"
                            placeholder="Buscar por cliente, nº ou descrição..."
                            class="w-full rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                    </div>
                    <div class="flex gap-2 w-full sm:w-auto">
                        <select
                            v-model="status"
                            @change="applyFilters"
                            class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option value="">Todos os status</option>
                            <option value="issued">Emitidos</option>
                            <option value="canceled">Cancelados</option>
                        </select>
                    </div>
                </div>

                <!-- Tabela de Recibos -->
                <div class="overflow-hidden bg-white shadow-sm rounded-xl border border-gray-100">
                    <div v-if="receipts.data.length === 0" class="p-12 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 text-indigo-600 text-2xl">
                            📄
                        </div>
                        <h3 class="mt-3 text-base font-semibold text-gray-900">Nenhum recibo encontrado</h3>
                        <p class="mt-1 text-sm text-gray-500">
                            {{ filters.q || filters.status ? 'Tente ajustar os filtros acima.' : 'Emita recibos facilmente a partir de qualquer entrada no seu fluxo de caixa.' }}
                        </p>
                        <div class="mt-4">
                            <Link
                                :href="route('transactions.index')"
                                class="inline-flex items-center rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                            >
                                Ir para o Caixa
                            </Link>
                        </div>
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                            <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                                <tr>
                                    <th class="px-6 py-3 font-semibold">Número</th>
                                    <th class="px-6 py-3 font-semibold">Cliente / Pagador</th>
                                    <th class="px-6 py-3 font-semibold">Valor</th>
                                    <th class="px-6 py-3 font-semibold">Forma</th>
                                    <th class="px-6 py-3 font-semibold">Data Emissão</th>
                                    <th class="px-6 py-3 font-semibold">Status</th>
                                    <th class="px-6 py-3 font-semibold text-right">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <tr v-for="r in receipts.data" :key="r.id" class="hover:bg-gray-50 transition">
                                    <td class="px-6 py-4 font-bold text-indigo-600 whitespace-nowrap">
                                        <Link :href="route('receipts.show', r.id)" class="hover:underline">
                                            Nº {{ r.formatted_number }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 text-gray-900">
                                        <div class="font-medium">{{ r.payer_name }}</div>
                                        <div class="text-xs text-gray-500 truncate max-w-xs">{{ r.description }}</div>
                                    </td>
                                    <td class="px-6 py-4 font-bold text-emerald-600 whitespace-nowrap">
                                        {{ r.formatted_amount }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-600 uppercase text-xs font-semibold whitespace-nowrap">
                                        {{ r.payment_method }}
                                    </td>
                                    <td class="px-6 py-4 text-gray-500 whitespace-nowrap">
                                        {{ formatDate(r.issued_at) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                                            :class="r.status === 'issued' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                                        >
                                            {{ r.status === 'issued' ? 'Emitido' : 'Cancelado' }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-right whitespace-nowrap space-x-2">
                                        <a
                                            :href="route('receipts.pdf', { receipt: r.id, disposition: 'attachment' })"
                                            target="_blank"
                                            class="inline-flex items-center text-xs font-semibold text-gray-700 bg-gray-100 hover:bg-gray-200 px-2.5 py-1.5 rounded-md"
                                        >
                                            PDF
                                        </a>
                                        <Link
                                            :href="route('receipts.show', r.id)"
                                            class="inline-flex items-center text-xs font-semibold text-indigo-600 hover:text-indigo-900 px-2 py-1"
                                        >
                                            Detalhes &rarr;
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <div v-if="receipts.links && receipts.links.length > 3" class="px-6 py-4 border-t border-gray-100 flex justify-center">
                        <div class="flex space-x-1">
                            <Link
                                v-for="(link, idx) in receipts.links"
                                :key="idx"
                                :href="link.url || '#'"
                                v-html="link.label"
                                class="px-3 py-1.5 text-xs rounded-md border"
                                :class="link.active ? 'bg-indigo-600 text-white border-indigo-600 font-bold' : 'text-gray-700 border-gray-200 hover:bg-gray-50'"
                            />
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
