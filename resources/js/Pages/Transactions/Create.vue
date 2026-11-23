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
import { computed, ref, watch } from 'vue';
import { getLocalDateString } from '@/utils/localDate';
import { maskCpfCnpj, maskPhone } from '@/utils/masks';

interface Category {
    id: number;
    name: string;
    color: string | null;
    type: 'income' | 'expense';
}

interface Client {
    id: number;
    name: string;
}

interface PaymentMethodOption {
    value: string;
    label: string;
}

const props = defineProps<{
    categories: Category[];
    clients: Client[];
    defaultData: {
        type: 'income' | 'expense';
        appointment_id: number | null;
        client_id: number | null;
        description: string;
        amount: string;
    };
    paymentMethods: PaymentMethodOption[];
}>();

const todayIso = getLocalDateString();

const form = useForm({
    type: props.defaultData.type,
    amount: props.defaultData.amount,
    occurred_on: todayIso,
    description: props.defaultData.description,
    category_id: '' as number | '',
    payment_method: 'pix',
    client_id: props.defaultData.client_id || '',
    appointment_id: props.defaultData.appointment_id || '',
    notes: '',
});

const filteredCategories = computed(() =>
    props.categories.filter((c) => c.type === form.type)
);

watch(
    () => form.type,
    () => {
        form.category_id = '';
    }
);

const clientList = ref<Client[]>([...props.clients]);
const showQuickClientModal = ref(false);
const quickClientSaving = ref(false);
const quickClientError = ref('');
const quickClient = ref({
    name: '',
    phone_e164: '',
    document_type: 'cpf' as 'cpf' | 'cnpj',
    document_number: '',
    email: '',
});

const openQuickClientModal = () => {
    quickClientError.value = '';
    quickClient.value = { name: '', phone_e164: '', document_type: 'cpf', document_number: '', email: '' };
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
    form.post(route('transactions.store'));
};
</script>

<template>
    <Head :title="form.type === 'income' ? 'Nova Entrada - GestÁgil' : 'Nova Despesa - GestÁgil'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold leading-tight" :class="form.type === 'income' ? 'text-emerald-700' : 'text-rose-700'">
                        {{ form.type === 'income' ? '+ Registrar Entrada (Receita)' : '- Registrar Saída (Despesa)' }}
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Lançamento rápido no fluxo de caixa
                    </p>
                </div>
                <Link
                    :href="route('transactions.index')"
                    class="text-sm font-medium text-gray-600 hover:text-gray-900"
                >
                    &larr; Voltar ao Caixa
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <!-- Alternador de Tipo -->
                    <div class="mb-6 grid grid-cols-2 gap-2 p-1 bg-gray-100 rounded-lg">
                        <button
                            type="button"
                            @click="form.type = 'income'"
                            class="py-2 text-sm font-bold rounded-md transition"
                            :class="form.type === 'income' ? 'bg-emerald-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                        >
                            + Entrada (Venda/Serviço)
                        </button>
                        <button
                            type="button"
                            @click="form.type = 'expense'"
                            class="py-2 text-sm font-bold rounded-md transition"
                            :class="form.type === 'expense' ? 'bg-rose-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                        >
                            - Saída (Despesa/Custo)
                        </button>
                    </div>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Valor & Data -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="amount" value="Valor (R$) *" />
                                <TextInput
                                    id="amount"
                                    type="number"
                                    step="0.01"
                                    min="0.01"
                                    class="mt-1 block w-full text-lg font-bold"
                                    :class="form.type === 'income' ? 'text-emerald-700' : 'text-rose-700'"
                                    v-model="form.amount"
                                    placeholder="0,00"
                                    required
                                    autofocus
                                />
                                <InputError class="mt-2" :message="form.errors.amount" />
                            </div>

                            <div>
                                <InputLabel for="occurred_on" value="Data do Lançamento *" />
                                <TextInput
                                    id="occurred_on"
                                    type="date"
                                    class="mt-1 block w-full"
                                    v-model="form.occurred_on"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.occurred_on" />
                            </div>
                        </div>

                        <!-- Descrição -->
                        <div>
                            <InputLabel for="description" value="Descrição *" />
                            <TextInput
                                id="description"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.description"
                                placeholder="Ex: Venda de mercadorias no balcão, Pagamento de energia..."
                                required
                            />
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>

                        <!-- Meio de Pagamento & Categoria -->
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <InputLabel for="payment_method" value="Forma de Pagamento *" />
                                <select
                                    id="payment_method"
                                    v-model="form.payment_method"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    required
                                >
                                    <option v-for="m in paymentMethods" :key="m.value" :value="m.value">
                                        {{ m.label }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.payment_method" />
                            </div>

                            <div>
                                <InputLabel for="category_id" value="Categoria" />
                                <select
                                    id="category_id"
                                    v-model="form.category_id"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option value="">Sem categoria</option>
                                    <option v-for="cat in filteredCategories" :key="cat.id" :value="cat.id">
                                        {{ cat.name }}
                                    </option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.category_id" />
                            </div>
                        </div>

                        <!-- Cliente Opcional -->
                        <div>
                            <div class="flex items-center justify-between">
                                <InputLabel for="client_id" value="Cliente / Fornecedor Vinculado" />
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
                            >
                                <option value="">Nenhum (Venda anônima ou geral)</option>
                                <option v-for="c in clientList" :key="c.id" :value="c.id">
                                    {{ c.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.client_id" />
                        </div>

                        <!-- Ações -->
                        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                            <Link :href="route('transactions.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>

                            <PrimaryButton
                                :disabled="form.processing"
                                :class="form.type === 'income' ? '!bg-emerald-600 hover:!bg-emerald-500' : '!bg-rose-600 hover:!bg-rose-500'"
                            >
                                Confirmar {{ form.type === 'income' ? 'Recebimento' : 'Pagamento' }}
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Modal Cadastro Rápido de Cliente -->
        <Modal :show="showQuickClientModal" @close="showQuickClientModal = false">
            <form @submit.prevent="submitQuickClient" class="p-6 space-y-4">
                <h3 class="text-lg font-bold text-gray-900">Cadastrar Cliente na Venda</h3>
                <p v-if="quickClientError" class="text-sm text-rose-600">{{ quickClientError }}</p>
                <div>
                    <InputLabel for="tx_quick_name" value="Nome Completo *" />
                    <TextInput id="tx_quick_name" v-model="quickClient.name" type="text" class="mt-1 block w-full" required />
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <InputLabel for="tx_quick_phone" value="Celular / WhatsApp" />
                        <TextInput
                            id="tx_quick_phone"
                            :model-value="quickClient.phone_e164"
                            @input="quickClient.phone_e164 = maskPhone(($event.target as HTMLInputElement).value)"
                            type="text"
                            maxlength="15"
                            class="mt-1 block w-full"
                            placeholder="(81) 99999-8888"
                        />
                    </div>
                    <div>
                        <InputLabel for="tx_quick_email" value="E-mail" />
                        <TextInput id="tx_quick_email" v-model="quickClient.email" type="email" class="mt-1 block w-full" placeholder="cliente@exemplo.com" />
                    </div>
                </div>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <InputLabel for="tx_quick_doc_type" value="Tipo" />
                        <select
                            id="tx_quick_doc_type"
                            v-model="quickClient.document_type"
                            @change="quickClient.document_number = maskCpfCnpj(quickClient.document_number, quickClient.document_type)"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                        >
                            <option value="cpf">CPF</option>
                            <option value="cnpj">CNPJ</option>
                        </select>
                    </div>
                    <div class="sm:col-span-2">
                        <InputLabel for="tx_quick_doc" value="CPF / CNPJ" />
                        <TextInput
                            id="tx_quick_doc"
                            :model-value="quickClient.document_number"
                            @input="quickClient.document_number = maskCpfCnpj(($event.target as HTMLInputElement).value, quickClient.document_type)"
                            type="text"
                            :maxlength="quickClient.document_type === 'cpf' ? 14 : 18"
                            class="mt-1 block w-full"
                            :placeholder="quickClient.document_type === 'cpf' ? '000.000.000-00' : '00.000.000/0000-00'"
                        />
                    </div>
                </div>
                <div class="flex justify-end space-x-3 pt-2">
                    <SecondaryButton type="button" @click="showQuickClientModal = false">Cancelar</SecondaryButton>
                    <PrimaryButton :disabled="quickClientSaving">Salvar e Selecionar</PrimaryButton>
                </div>
            </form>
        </Modal>
    </AuthenticatedLayout>
</template>
