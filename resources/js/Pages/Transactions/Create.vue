<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface Category {
    id: number;
    name: string;
    color: string | null;
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

const todayIso = new Date().toISOString().slice(0, 10);

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
                                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
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
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="">Nenhum (Venda anônima ou geral)</option>
                                <option v-for="c in clients" :key="c.id" :value="c.id">
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
    </AuthenticatedLayout>
</template>
