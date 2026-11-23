<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, watch } from 'vue';

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

interface TransactionData {
    id: number;
    type: 'income' | 'expense';
    amount: string;
    occurred_on: string;
    description: string;
    category_id: number | null;
    payment_method: string;
    client_id: number | null;
    appointment_id: number | null;
    notes: string | null;
}

const props = defineProps<{
    transaction: TransactionData;
    categories: Category[];
    clients: Client[];
    hasActiveReceipt: boolean;
    paymentMethods: PaymentMethodOption[];
}>();

const form = useForm({
    type: props.transaction.type,
    amount: props.transaction.amount,
    occurred_on: props.transaction.occurred_on,
    description: props.transaction.description,
    category_id: (props.transaction.category_id ?? '') as number | '',
    payment_method: props.transaction.payment_method,
    client_id: (props.transaction.client_id ?? '') as number | '',
    appointment_id: props.transaction.appointment_id ?? '',
    notes: props.transaction.notes ?? '',
});

const filteredCategories = computed(() =>
    props.categories.filter((c) => c.type === form.type)
);

watch(
    () => form.type,
    () => {
        if (!props.hasActiveReceipt) {
            form.category_id = '';
        }
    }
);

const submit = () => {
    form.put(route('transactions.update', props.transaction.id));
};
</script>

<template>
    <Head :title="`Editar Lançamento #${transaction.id} - GestÁgil`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold leading-tight" :class="form.type === 'income' ? 'text-emerald-700' : 'text-rose-700'">
                        Editar Lançamento #{{ transaction.id }}
                    </h2>
                    <p class="text-xs text-gray-500 mt-0.5">
                        Atualize os dados do lançamento financeiro
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
                    <div
                        v-if="hasActiveReceipt"
                        class="mb-6 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800"
                    >
                        <strong>Recibo ativo vinculado:</strong> Para preservar a integridade fiscal/financeira (RN-22), tipo, valor, data, forma de pagamento e descrição estão bloqueados. Você pode editar apenas a categoria e as observações, ou cancelar o recibo antes.
                    </div>

                    <!-- Alternador de Tipo -->
                    <div class="mb-6 grid grid-cols-2 gap-2 p-1 bg-gray-100 rounded-lg">
                        <button
                            type="button"
                            :disabled="hasActiveReceipt"
                            @click="form.type = 'income'"
                            class="py-2 text-sm font-bold rounded-md transition disabled:opacity-60 disabled:cursor-not-allowed"
                            :class="form.type === 'income' ? 'bg-emerald-600 text-white shadow-sm' : 'text-gray-600 hover:text-gray-900'"
                        >
                            + Entrada (Venda/Serviço)
                        </button>
                        <button
                            type="button"
                            :disabled="hasActiveReceipt"
                            @click="form.type = 'expense'"
                            class="py-2 text-sm font-bold rounded-md transition disabled:opacity-60 disabled:cursor-not-allowed"
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
                                    :disabled="hasActiveReceipt"
                                    class="mt-1 block w-full text-lg font-bold disabled:bg-gray-100"
                                    :class="form.type === 'income' ? 'text-emerald-700' : 'text-rose-700'"
                                    v-model="form.amount"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.amount" />
                            </div>

                            <div>
                                <InputLabel for="occurred_on" value="Data do Lançamento *" />
                                <TextInput
                                    id="occurred_on"
                                    type="date"
                                    :disabled="hasActiveReceipt"
                                    class="mt-1 block w-full disabled:bg-gray-100"
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
                                :disabled="hasActiveReceipt"
                                class="mt-1 block w-full disabled:bg-gray-100"
                                v-model="form.description"
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
                                    :disabled="hasActiveReceipt"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100"
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
                            <InputLabel for="client_id" value="Cliente / Fornecedor Vinculado" />
                            <select
                                id="client_id"
                                v-model="form.client_id"
                                :disabled="hasActiveReceipt || Boolean(form.appointment_id)"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm disabled:bg-gray-100"
                            >
                                <option value="">Nenhum (Venda anônima ou geral)</option>
                                <option v-for="c in clients" :key="c.id" :value="c.id">
                                    {{ c.name }}
                                </option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.client_id" />
                        </div>

                        <!-- Observações -->
                        <div>
                            <InputLabel for="notes" value="Observações" />
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="2"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.notes" />
                        </div>

                        <!-- Ações -->
                        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                            <Link :href="route('transactions.index')">
                                <SecondaryButton type="button">Cancelar</SecondaryButton>
                            </Link>

                            <PrimaryButton :disabled="form.processing">
                                Salvar Alterações
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
