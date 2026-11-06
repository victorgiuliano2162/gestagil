<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

interface ClientItem {
    id: number;
    name: string;
    phone_e164: string | null;
    document_number: string | null;
    email: string | null;
    created_at: string;
}

interface PaginatedClients {
    data: ClientItem[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
}

const props = defineProps<{
    clients: PaginatedClients;
    filters: { search?: string };
}>();

const search = ref(props.filters.search || '');
let debounceTimeout: ReturnType<typeof setTimeout>;

watch(search, (value) => {
    clearTimeout(debounceTimeout);
    debounceTimeout = setTimeout(() => {
        router.get(
            route('clients.index'),
            { search: value },
            { preserveState: true, replace: true }
        );
    }, 300);
});
</script>

<template>
    <Head title="Clientes - GestÁgil" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Clientes
                </h2>
                <Link
                    :href="route('clients.create')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600"
                >
                    + Novo Cliente
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <!-- Barra de busca -->
                    <div class="mb-6 flex items-center justify-between">
                        <div class="w-full max-w-md">
                            <input
                                v-model="search"
                                type="text"
                                placeholder="Buscar por nome, documento ou telefone..."
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            />
                        </div>
                        <div class="text-sm text-gray-500">
                            Total: {{ clients.total }} cliente(s)
                        </div>
                    </div>

                    <!-- Tabela de Clientes -->
                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200">
                            <thead class="bg-gray-50">
                                <tr>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Nome</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Telefone</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Documento</th>
                                    <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">E-mail</th>
                                    <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Ações</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 bg-white">
                                <tr v-if="clients.data.length === 0">
                                    <td colspan="5" class="py-10 text-center text-sm text-gray-500">
                                        Nenhum cliente encontrado.
                                    </td>
                                </tr>
                                <tr v-for="c in clients.data" :key="c.id" class="hover:bg-gray-50">
                                    <td class="whitespace-nowrap px-6 py-4 font-medium text-gray-900">
                                        <Link :href="route('clients.show', c.id)" class="text-indigo-600 hover:text-indigo-900">
                                            {{ c.name }}
                                        </Link>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {{ c.phone_e164 || '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {{ c.document_number || '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                        {{ c.email || '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-right text-sm font-medium">
                                        <Link :href="route('clients.edit', c.id)" class="mr-3 text-indigo-600 hover:text-indigo-900">
                                            Editar
                                        </Link>
                                        <Link :href="route('clients.show', c.id)" class="text-gray-600 hover:text-gray-900">
                                            Detalhes
                                        </Link>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Paginação simples -->
                    <div class="mt-6 flex items-center justify-between border-t border-gray-200 pt-4" v-if="clients.last_page > 1">
                        <Link
                            v-if="clients.prev_page_url"
                            :href="clients.prev_page_url"
                            class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Anterior
                        </Link>
                        <span v-else></span>

                        <span class="text-sm text-gray-500">
                            Página {{ clients.current_page }} de {{ clients.last_page }}
                        </span>

                        <Link
                            v-if="clients.next_page_url"
                            :href="clients.next_page_url"
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
