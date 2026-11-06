<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface Client {
    id: number;
    name: string;
    phone_e164: string | null;
}

interface AppointmentItem {
    id: number;
    client: Client;
    service_description: string;
    service_at: string;
    status: string;
    expected_amount_cents: number | null;
}

interface PaginatedAppointments {
    data: AppointmentItem[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

interface StatusOption {
    value: string;
    label: string;
    color: string;
}

const props = defineProps<{
    appointments: PaginatedAppointments;
    filters: { status?: string; search?: string };
    statuses: StatusOption[];
}>();

const search = ref(props.filters.search || '');
const status = ref(props.filters.status || '');

let debounceTimeout: ReturnType<typeof setTimeout>;

const applyFilters = () => {
    router.get(
        route('appointments.index'),
        { search: search.value || undefined, status: status.value || undefined },
        { preserveState: true, replace: true }
    );
};

watch(search, () => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        applyFilters();
    }, 300);
});

watch(status, () => {
    applyFilters();
});

const formatCurrency = (cents: number | null) => {
    if (cents === null) return '-';
    return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
};

const getStatusBadge = (s: string) => {
    switch (s) {
        case 'completed':
            return { label: 'Concluído', bg: 'bg-emerald-100 text-emerald-800' };
        case 'canceled':
            return { label: 'Cancelado', bg: 'bg-rose-100 text-rose-800' };
        default:
            return { label: 'Em Andamento', bg: 'bg-amber-100 text-amber-800' };
    }
};
</script>

<template>
    <Head title="Atendimentos e Serviços - GestÁgil" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        Atendimentos e Ordens de Serviço
                    </h2>
                    <p class="text-xs text-gray-500 mt-1">
                        Acompanhe serviços, entregas e manutenções da sua loja
                    </p>
                </div>
                <Link
                    :href="route('appointments.create')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    + Novo Atendimento
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <!-- Filtros -->
                    <div class="mb-6 flex flex-col sm:flex-row gap-4 items-center justify-between">
                        <div class="w-full sm:w-80">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Buscar por serviço ou cliente..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>

                        <!-- Abas / Filtro de Status -->
                        <div class="flex items-center space-x-2 w-full sm:w-auto">
                            <button
                                @click="status = ''"
                                class="px-3 py-1.5 text-xs font-semibold rounded-lg transition"
                                :class="!status ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            >
                                Todos
                            </button>
                            <button
                                v-for="s in statuses"
                                :key="s.value"
                                @click="status = s.value"
                                class="px-3 py-1.5 text-xs font-semibold rounded-lg transition"
                                :class="status === s.value ? 'bg-indigo-600 text-white shadow-sm' : 'bg-gray-100 text-gray-700 hover:bg-gray-200'"
                            >
                                {{ s.label }}
                            </button>
                        </div>
                    </div>

                    <!-- Tabela de Atendimentos -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Data / Hora</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Cliente</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Descrição do Serviço</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Valor Previsto</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-if="appointments.data.length === 0">
                                    <td colspan="6" class="py-10 text-center text-sm text-gray-500">
                                        Nenhum atendimento encontrado.
                                    </td>
                                </tr>
                                <tr v-for="a in appointments.data" :key="a.id" class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                        {{ new Date(a.service_at).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700">
                                        <Link :href="route('clients.show', a.client.id)" class="text-indigo-600 hover:text-indigo-900 font-medium">
                                            {{ a.client.name }}
                                        </Link>
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-600 max-w-xs truncate">
                                        {{ a.service_description }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-900">
                                        {{ formatCurrency(a.expected_amount_cents) }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <span
                                            class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium"
                                            :class="getStatusBadge(a.status).bg"
                                        >
                                            {{ getStatusBadge(a.status).label }}
                                        </span>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium space-x-2">
                                        <Link :href="route('appointments.show', a.id)" class="text-indigo-600 hover:text-indigo-900">
                                            Ver Detalhes
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação -->
                    <div class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4" v-if="appointments.last_page > 1">
                        <Link
                            v-if="appointments.prev_page_url"
                            :href="appointments.prev_page_url"
                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Anterior
                        </Link>
                        <span v-else></span>

                        <span class="text-sm text-gray-500">
                            Página {{ appointments.current_page }} de {{ appointments.last_page }}
                        </span>

                        <Link
                            v-if="appointments.next_page_url"
                            :href="appointments.next_page_url"
                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
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
