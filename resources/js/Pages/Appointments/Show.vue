<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Client {
    id: number;
    name: string;
    phone_e164: string | null;
    document_number: string | null;
    email: string | null;
}

interface Appointment {
    id: number;
    client: Client;
    service_description: string;
    service_at: string;
    status: string;
    status_label: string;
    status_color: string;
    technical_notes: string | null;
    expected_amount_cents: number | null;
    completed_at: string | null;
    canceled_at: string | null;
    cancel_reason: string | null;
    created_at: string;
}

const props = defineProps<{
    appointment: Appointment;
}>();

const confirmingCancel = ref(false);
const cancelForm = useForm({
    status: 'canceled',
    cancel_reason: '',
});

const completeForm = useForm({
    status: 'completed',
});

const openCancelModal = () => {
    confirmingCancel.value = true;
};

const closeCancelModal = () => {
    confirmingCancel.value = false;
};

const submitCancel = () => {
    cancelForm.patch(route('appointments.status', props.appointment.id), {
        onSuccess: () => closeCancelModal(),
    });
};

const markAsCompleted = () => {
    if (confirm('Deseja marcar este atendimento como concluído?')) {
        completeForm.patch(route('appointments.status', props.appointment.id));
    }
};

const formatCurrency = (cents: number | null) => {
    if (cents === null) return 'Não informado';
    return (cents / 100).toLocaleString('pt-BR', { style: 'currency', currency: 'BRL' });
};

const getStatusBadge = (s: string) => {
    switch (s) {
        case 'completed':
            return { label: 'Concluído', bg: 'bg-emerald-100 text-emerald-800 border-emerald-200' };
        case 'canceled':
            return { label: 'Cancelado', bg: 'bg-rose-100 text-rose-800 border-rose-200' };
        default:
            return { label: 'Em Andamento', bg: 'bg-amber-100 text-amber-800 border-amber-200' };
    }
};
</script>

<template>
    <Head :title="`Atendimento #${appointment.id} - GestÁgil`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-3">
                        <h2 class="text-xl font-bold leading-tight text-gray-900">
                            Atendimento #{{ appointment.id }}
                        </h2>
                        <span
                            class="inline-flex rounded-full px-3 py-1 text-xs font-semibold border"
                            :class="getStatusBadge(appointment.status).bg"
                        >
                            {{ getStatusBadge(appointment.status).label }}
                        </span>
                    </div>
                    <p class="text-xs text-gray-500 mt-1">
                        Agendado para {{ new Date(appointment.service_at).toLocaleString('pt-BR', { dateStyle: 'long', timeStyle: 'short' }) }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        :href="route('appointments.index')"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                    >
                        &larr; Voltar
                    </Link>

                    <!-- Ações de Transição de Estado (RN-04) -->
                    <template v-if="appointment.status === 'in_progress'">
                        <Link
                            :href="route('appointments.edit', appointment.id)"
                            class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                        >
                            Editar
                        </Link>

                        <button
                            @click="openCancelModal"
                            class="rounded-md bg-white border border-rose-300 px-3 py-2 text-sm font-semibold text-rose-700 shadow-sm hover:bg-rose-50"
                        >
                            Cancelar
                        </button>

                        <PrimaryButton
                            @click="markAsCompleted"
                            :disabled="completeForm.processing"
                            class="!bg-emerald-600 hover:!bg-emerald-500"
                        >
                            ✓ Concluir Atendimento
                        </PrimaryButton>
                    </template>
                </div>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl sm:px-6 lg:px-8 space-y-6">
                <!-- Informações do Cliente -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <h3 class="text-base font-semibold text-gray-900 border-b pb-3 mb-4 flex items-center justify-between">
                        <span>Dados do Cliente</span>
                        <Link :href="route('clients.show', appointment.client.id)" class="text-xs text-indigo-600 hover:underline">
                            Ver ficha completa &rarr;
                        </Link>
                    </h3>

                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-3">
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Nome</dt>
                            <dd class="mt-1 text-sm font-semibold text-gray-900">{{ appointment.client.name }}</dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Telefone / WhatsApp</dt>
                            <dd class="mt-1 text-sm text-gray-900">
                                <a
                                    v-if="appointment.client.phone_e164"
                                    :href="`https://wa.me/${appointment.client.phone_e164.replace(/\D/g, '')}`"
                                    target="_blank"
                                    class="text-indigo-600 underline font-medium"
                                >
                                    {{ appointment.client.phone_e164 }} (Conversar)
                                </a>
                                <span v-else class="text-gray-400">Não informado</span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-xs font-medium text-gray-500">Valor Previsto</dt>
                            <dd class="mt-1 text-sm font-bold text-gray-900">{{ formatCurrency(appointment.expected_amount_cents) }}</dd>
                        </div>
                    </dl>
                </div>

                <!-- Detalhes do Serviço -->
                <div class="bg-white p-6 shadow sm:rounded-lg space-y-4">
                    <h3 class="text-base font-semibold text-gray-900 border-b pb-3">
                        Descrição do Serviço
                    </h3>
                    <p class="text-sm text-gray-800 whitespace-pre-line bg-gray-50 p-4 rounded-md border">
                        {{ appointment.service_description }}
                    </p>

                    <div v-if="appointment.technical_notes" class="pt-2">
                        <h4 class="text-xs font-semibold text-gray-700 mb-1">Notas Técnicas e Internas:</h4>
                        <p class="text-sm text-gray-600 whitespace-pre-line bg-amber-50/50 p-3 rounded-md border border-amber-100">
                            {{ appointment.technical_notes }}
                        </p>
                    </div>

                    <!-- Dados de Conclusão / Cancelamento -->
                    <div v-if="appointment.status === 'completed'" class="p-4 rounded-md bg-emerald-50 border border-emerald-200 text-sm text-emerald-800">
                        ✓ Atendimento concluído em {{ new Date(appointment.completed_at!).toLocaleString('pt-BR') }}.
                    </div>

                    <div v-if="appointment.status === 'canceled'" class="p-4 rounded-md bg-rose-50 border border-rose-200 text-sm text-rose-800">
                        <p class="font-semibold">✕ Atendimento cancelado em {{ new Date(appointment.canceled_at!).toLocaleString('pt-BR') }}.</p>
                        <p v-if="appointment.cancel_reason" class="mt-1 text-xs">
                            Motivo informado: {{ appointment.cancel_reason }}
                        </p>
                    </div>
                </div>

                <!-- Modal de Cancelamento -->
                <Modal :show="confirmingCancel" @close="closeCancelModal">
                    <div class="p-6">
                        <h3 class="text-lg font-bold text-gray-900">
                            Cancelar Atendimento
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Informe o motivo do cancelamento para registro histórico do atendimento.
                        </p>

                        <div class="mt-4">
                            <textarea
                                v-model="cancelForm.cancel_reason"
                                rows="3"
                                class="w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                placeholder="Ex: Cliente desistiu, falta de peças no fornecedor, remarcação..."
                                required
                            ></textarea>
                        </div>

                        <div class="mt-6 flex justify-end space-x-3">
                            <SecondaryButton @click="closeCancelModal">Voltar</SecondaryButton>
                            <DangerButton
                                :disabled="cancelForm.processing"
                                @click="submitCancel"
                            >
                                Confirmar Cancelamento
                            </DangerButton>
                        </div>
                    </div>
                </Modal>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
