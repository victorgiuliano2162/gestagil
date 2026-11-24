<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import { maskCpfCnpj, maskPhone } from '@/utils/masks';

interface Client {
    id: number;
    name: string;
    phone_e164: string | null;
    document_type: string | null;
    document_number: string | null;
    email: string | null;
    notes: string | null;
    created_at: string;
}

interface ClientAppointment {
    id: number;
    service_description: string;
    service_at: string;
    status: string;
    status_label: string;
    expected_amount_cents: number | null;
}

interface ClientTransaction {
    id: number;
    type: 'income' | 'expense';
    amount_cents: number;
    occurred_on: string;
    description: string;
    payment_method_label: string;
    category_name: string | null;
}

const props = defineProps<{
    client: Client;
    appointments: ClientAppointment[];
    transactions: ClientTransaction[];
    stats: {
        total_spent_cents: number;
        appointments_count: number;
        transactions_count: number;
    };
}>();

const formatCurrency = (cents: number) => {
    return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
};

const formatDateOnly = (iso: string) => {
    const [y, m, d] = iso.slice(0, 10).split('-');
    return `${d}/${m}/${y}`;
};

const confirmingClientDeletion = ref(false);
const deleteForm = useForm({});

const confirmClientDeletion = () => {
    confirmingClientDeletion.value = true;
};

const closeModal = () => {
    confirmingClientDeletion.value = false;
};

const deleteClient = () => {
    deleteForm.delete(route('clients.destroy', props.client.id), {
        onSuccess: () => closeModal(),
    });
};
</script>

<template>
    <Head :title="`${client.name} - GestÁgil`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-gray-100">
                        {{ client.name }}
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Cadastrado em {{ new Date(client.created_at).toLocaleDateString('pt-BR') }}
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('clients.index')"
                        class="rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800/60"
                    >
                        &larr; Voltar
                    </Link>
                    <Link
                        :href="route('clients.edit', client.id)"
                        class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                    >
                        Editar
                    </Link>
                    <DangerButton @click="confirmClientDeletion">
                        Excluir
                    </DangerButton>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8 space-y-6">
                <!-- Card de Dados do Cliente -->
                <div class="bg-white dark:bg-gray-900 p-6 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-800 pb-3 mb-4">
                        Informações Gerais
                    </h3>

                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Nome Completo</dt>
                            <dd class="mt-1 text-base text-gray-900 dark:text-gray-100">{{ client.name }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Telefone / WhatsApp</dt>
                            <dd class="mt-1 text-base text-gray-900 dark:text-gray-100">
                                <a
                                    v-if="client.phone_e164"
                                    :href="`https://wa.me/${client.phone_e164.replace(/\D/g, '')}`"
                                    target="_blank"
                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 underline inline-flex items-center"
                                >
                                    {{ maskPhone(client.phone_e164) }}
                                </a>
                                <span v-else class="text-gray-400">Não informado</span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Documento</dt>
                            <dd class="mt-1 text-base text-gray-900 dark:text-gray-100">
                                <span v-if="client.document_number">
                                    {{ client.document_type?.toUpperCase() }}: {{ maskCpfCnpj(client.document_number, client.document_type) }}
                                </span>
                                <span v-else class="text-gray-400">Não informado</span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">E-mail</dt>
                            <dd class="mt-1 text-base text-gray-900 dark:text-gray-100">
                                <a
                                    v-if="client.email"
                                    :href="`mailto:${client.email}`"
                                    class="text-indigo-600 hover:text-indigo-900 dark:text-indigo-400 dark:hover:text-indigo-300 underline"
                                >
                                    {{ client.email }}
                                </a>
                                <span v-else class="text-gray-400">Não informado</span>
                            </dd>
                        </div>

                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500 dark:text-gray-400">Observações</dt>
                            <dd class="mt-1 text-base text-gray-900 dark:text-gray-100 whitespace-pre-line bg-gray-50 dark:bg-gray-800/60 p-4 rounded-md border border-gray-200 dark:border-gray-800">
                                {{ client.notes || 'Nenhuma anotação registrada para este cliente.' }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Resumo de Relacionamento (RF-CLI-05) -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="bg-white dark:bg-gray-900 p-5 shadow sm:rounded-lg">
                        <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Total Faturado</span>
                        <p class="mt-1 text-2xl font-bold text-emerald-600 dark:text-emerald-400">{{ formatCurrency(stats.total_spent_cents) }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 p-5 shadow sm:rounded-lg">
                        <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Atendimentos</span>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.appointments_count }}</p>
                    </div>
                    <div class="bg-white dark:bg-gray-900 p-5 shadow sm:rounded-lg">
                        <span class="text-xs font-semibold uppercase text-gray-500 dark:text-gray-400">Lançamentos</span>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-gray-100">{{ stats.transactions_count }}</p>
                    </div>
                </div>

                <!-- Histórico de Atendimentos -->
                <div class="bg-white dark:bg-gray-900 p-6 shadow sm:rounded-lg">
                    <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-3 mb-4">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Histórico de Atendimentos</h3>
                        <Link
                            :href="route('appointments.create', { client_id: client.id })"
                            class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300"
                        >
                            + Novo Atendimento
                        </Link>
                    </div>
                    <div v-if="appointments.length === 0" class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                        Nenhum atendimento registrado para este cliente.
                    </div>
                    <div v-else class="divide-y divide-gray-200 dark:divide-gray-800">
                        <div
                            v-for="a in appointments"
                            :key="a.id"
                            class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2"
                        >
                            <div>
                                <Link :href="route('appointments.show', a.id)" class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 hover:underline">
                                    {{ a.service_description }}
                                </Link>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ new Date(a.service_at).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' }) }} · {{ a.status_label }}
                                </p>
                            </div>
                            <span v-if="a.expected_amount_cents !== null" class="text-sm font-semibold text-gray-800 dark:text-gray-100">
                                {{ formatCurrency(a.expected_amount_cents) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Histórico Financeiro -->
                <div class="bg-white dark:bg-gray-900 p-6 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 border-b border-gray-200 dark:border-gray-800 pb-3 mb-4">Histórico Financeiro</h3>
                    <div v-if="transactions.length === 0" class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                        Nenhum lançamento financeiro vinculado a este cliente.
                    </div>
                    <div v-else class="divide-y divide-gray-200 dark:divide-gray-800">
                        <div
                            v-for="t in transactions"
                            :key="t.id"
                            class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-2"
                        >
                            <div>
                                <p class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ t.description }}</p>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    {{ formatDateOnly(t.occurred_on) }} · {{ t.payment_method_label }}
                                    <span v-if="t.category_name"> · {{ t.category_name }}</span>
                                </p>
                            </div>
                            <span
                                class="text-sm font-bold"
                                :class="t.type === 'income' ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                            >
                                {{ t.type === 'income' ? '+' : '-' }} {{ formatCurrency(t.amount_cents) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Modal de Confirmação de Exclusão -->
                <Modal :show="confirmingClientDeletion" @close="closeModal">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                            Confirmar exclusão de cliente
                        </h2>

                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-300">
                            Tem certeza que deseja excluir o cliente <strong>{{ client.name }}</strong>? Esta ação pode ser revertida administrativamente.
                        </p>

                        <div class="mt-6 flex justify-end space-x-3">
                            <SecondaryButton @click="closeModal">Cancelar</SecondaryButton>
                            <DangerButton :disabled="deleteForm.processing" @click="deleteClient">
                                Excluir Cliente
                            </DangerButton>
                        </div>
                    </div>
                </Modal>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
