<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import Modal from '@/Components/Modal.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';
import { getLocalDateTimeString } from '@/utils/localDate';
import { maskCpfCnpj, maskPhone } from '@/utils/masks';

interface Client {
    id: number;
    name: string;
    phone_e164: string | null;
}

const props = defineProps<{
    clients: Client[];
    selectedClientId?: number | null;
}>();

// Data/hora atual no fuso local padrão formato YYYY-MM-DDTHH:mm
const nowIso = getLocalDateTimeString();

const form = useForm({
    client_id: props.selectedClientId || (props.clients.length > 0 ? props.clients[0].id : ''),
    service_description: '',
    service_at: nowIso,
    expected_amount: '',
    technical_notes: '',
});

const clientList = ref<Client[]>([...props.clients]);
const showQuickClientModal = ref(false);
const quickClientSaving = ref(false);
const quickClientError = ref('');
const quickClient = ref({
    name: '',
    phone_e164: '',
    document_number: '',
});

const openQuickClientModal = () => {
    quickClientError.value = '';
    quickClient.value = { name: '', phone_e164: '', document_number: '' };
    showQuickClientModal.value = true;
};

const submitQuickClient = async () => {
    quickClientSaving.value = true;
    quickClientError.value = '';
    try {
        const res = await axios.post(route('clients.quick-store'), quickClient.value);
        const created: Client = res.data.client;
        clientList.value.push(created);
        form.client_id = created.id;
        showQuickClientModal.value = false;
    } catch (err: any) {
        const errors = err?.response?.data?.errors;
        if (errors) {
            quickClientError.value = Object.values(errors).flat().join(' ');
        } else {
            quickClientError.value = 'Não foi possível cadastrar o cliente.';
        }
    } finally {
        quickClientSaving.value = false;
    }
};

const submit = () => {
    form.post(route('appointments.store'));
};
</script>

<template>
    <Head title="Novo Atendimento - GestÁgil" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Novo Atendimento / Ordem de Serviço
                </h2>
                <Link
                    :href="route('appointments.index')"
                    class="text-sm font-medium text-gray-600 hover:text-gray-900"
                >
                    &larr; Voltar
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Seleção de Cliente -->
                        <div>
                            <div class="flex items-center justify-between">
                                <InputLabel for="client_id" value="Cliente *" />
                                <button
                                    type="button"
                                    @click="openQuickClientModal"
                                    class="text-xs font-semibold text-indigo-600 hover:text-indigo-800"
                                >
                                    + Cadastrar novo cliente
                                </button>
                            </div>

                            <select
                                id="client_id"
                                v-model="form.client_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
                            >
                                <option value="" disabled>Selecione um cliente...</option>
                                <option v-for="c in clientList" :key="c.id" :value="c.id">
                                    {{ c.name }} {{ c.phone_e164 ? `(${maskPhone(c.phone_e164)})` : '' }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.client_id" />
                        </div>

                        <!-- Data / Hora e Valor Previsto -->
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="service_at" value="Data e Horário *" />
                                <TextInput
                                    id="service_at"
                                    type="datetime-local"
                                    class="mt-1 block w-full"
                                    v-model="form.service_at"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.service_at" />
                            </div>

                            <div>
                                <InputLabel for="expected_amount" value="Valor Previsto (R$)" />
                                <TextInput
                                    id="expected_amount"
                                    type="number"
                                    step="0.01"
                                    min="0"
                                    class="mt-1 block w-full"
                                    v-model="form.expected_amount"
                                    placeholder="Ex: 150.00"
                                />
                                <InputError class="mt-2" :message="form.errors.expected_amount" />
                            </div>
                        </div>

                        <!-- Descrição do Serviço / Pedido -->
                        <div>
                            <InputLabel for="service_description" value="Descrição do Serviço ou Pedido *" />
                            <textarea
                                id="service_description"
                                v-model="form.service_description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Ex: Conserto de placa eletrônica, ajuste de costura, troca de óleo e filtro..."
                                required
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.service_description" />
                        </div>

                        <!-- Observações Técnicas -->
                        <div>
                            <InputLabel for="technical_notes" value="Observações / Detalhes Técnicos" />
                            <textarea
                                id="technical_notes"
                                v-model="form.technical_notes"
                                rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Peças necessárias, número de série do aparelho, defeito relatado..."
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.technical_notes" />
                        </div>

                        <!-- Ações -->
                        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                            <Link :href="route('appointments.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>

                            <PrimaryButton :disabled="form.processing">
                                Iniciar Atendimento
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Cadastro Rápido de Cliente (RF-ATD-06) -->
        <Modal :show="showQuickClientModal" @close="showQuickClientModal = false">
            <form @submit.prevent="submitQuickClient" class="p-6 space-y-4">
                <h3 class="text-lg font-bold text-gray-900">Cadastro Rápido de Cliente</h3>
                <p v-if="quickClientError" class="text-sm text-rose-600">{{ quickClientError }}</p>
                <div>
                    <InputLabel for="quick_name" value="Nome Completo *" />
                    <TextInput id="quick_name" v-model="quickClient.name" type="text" class="mt-1 block w-full" required />
                </div>
                <div>
                    <InputLabel for="quick_phone" value="Celular / WhatsApp" />
                    <TextInput
                        id="quick_phone"
                        :model-value="quickClient.phone_e164"
                        @input="quickClient.phone_e164 = maskPhone(($event.target as HTMLInputElement).value)"
                        type="text"
                        maxlength="15"
                        class="mt-1 block w-full"
                        placeholder="(81) 99999-9999"
                    />
                </div>
                <div>
                    <InputLabel for="quick_doc" value="CPF / CNPJ" />
                    <TextInput
                        id="quick_doc"
                        :model-value="quickClient.document_number"
                        @input="quickClient.document_number = maskCpfCnpj(($event.target as HTMLInputElement).value)"
                        type="text"
                        maxlength="18"
                        class="mt-1 block w-full"
                        placeholder="000.000.000-00"
                    />
                </div>
                <div class="flex justify-end space-x-3 pt-2">
                    <SecondaryButton type="button" @click="showQuickClientModal = false">Cancelar</SecondaryButton>
                    <PrimaryButton :disabled="quickClientSaving">Salvar e Selecionar</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
