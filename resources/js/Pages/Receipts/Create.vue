<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface Client {
    id: number;
    name: string;
    document_number?: string | null;
    phone_e164?: string | null;
}

interface Transaction {
    id: number;
    amount_cents: number;
    description: string;
    payment_method: string;
    occurred_on: string;
}

interface BusinessProfile {
    store_name?: string | null;
    display_name?: string | null;
    document_number?: string | null;
    phone?: string | null;
}

const props = defineProps<{
    transaction: Transaction;
    client?: Client | null;
    businessProfile?: BusinessProfile | null;
}>();

const form = useForm({
    transaction_id: props.transaction.id,
    payer_name: props.client?.name || '',
    payer_document: props.client?.document_number || '',
    description: props.transaction.description || 'Prestação de serviços / venda comercial',
    payment_method: props.transaction.payment_method || 'pix',
});

const submit = () => {
    form.post(route('receipts.store'));
};

const formatCurrency = (cents: number): string => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(cents / 100);
};
</script>

<template>
    <Head title="Emitir Recibo - GestÁgil" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-gray-900">
                        Emitir Recibo de Pagamento
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Confirme os dados antes de gerar o comprovante oficial
                    </p>
                </div>
                <Link
                    :href="route('transactions.index')"
                    class="text-sm font-semibold text-gray-600 hover:text-gray-900"
                >
                    &larr; Voltar ao Caixa
                </Link>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <form @submit.prevent="submit" class="bg-white p-6 sm:p-8 rounded-xl shadow-sm border border-gray-100 space-y-6">
                    <!-- Banner de Resumo da Transação -->
                    <div class="p-4 rounded-lg bg-emerald-50 border border-emerald-100 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-semibold text-emerald-800 uppercase tracking-wide">Valor do Lançamento</span>
                            <div class="text-2xl font-black text-emerald-700">
                                {{ formatCurrency(transaction.amount_cents) }}
                            </div>
                        </div>
                        <div class="text-right text-xs text-emerald-800">
                            <span>Data da Entrada: <strong>{{ transaction.occurred_on }}</strong></span>
                        </div>
                    </div>

                    <!-- Dados do Pagador -->
                    <div class="border-b border-gray-100 pb-6 space-y-4">
                        <h3 class="text-base font-semibold text-gray-900">1. Dados do Cliente / Pagador</h3>

                        <div>
                            <InputLabel for="payer_name" value="Nome Completo do Cliente *" />
                            <TextInput
                                id="payer_name"
                                v-model="form.payer_name"
                                type="text"
                                class="mt-1 block w-full"
                                required
                                placeholder="Ex: Maria Fernandes ou João da Silva"
                            />
                            <InputError class="mt-2" :message="form.errors.payer_name" />
                        </div>

                        <div>
                            <InputLabel for="payer_document" value="CPF ou CNPJ do Cliente (Opcional)" />
                            <TextInput
                                id="payer_document"
                                v-model="form.payer_document"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="000.000.000-00"
                            />
                            <InputError class="mt-2" :message="form.errors.payer_document" />
                        </div>
                    </div>

                    <!-- Dados do Recibo -->
                    <div class="border-b border-gray-100 pb-6 space-y-4">
                        <h3 class="text-base font-semibold text-gray-900">2. Descrição e Forma de Pagamento</h3>

                        <div>
                            <InputLabel for="description" value="Referente a (Descrição detalhada) *" />
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="3"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                placeholder="Ex: Prestação de serviços de manutenção elétrica automotiva no veículo..."
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.description" />
                        </div>

                        <div>
                            <InputLabel for="payment_method" value="Forma de Pagamento *" />
                            <select
                                id="payment_method"
                                v-model="form.payment_method"
                                required
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                            >
                                <option value="pix">PIX</option>
                                <option value="cash">Dinheiro em Espécie</option>
                                <option value="card">Cartão de Crédito / Débito</option>
                                <option value="other">Outro</option>
                            </select>
                            <InputError class="mt-2" :message="form.errors.payment_method" />
                        </div>
                    </div>

                    <!-- Dados do Emitente (Preview) -->
                    <div class="p-4 rounded-lg bg-gray-50 border border-gray-200 text-xs text-gray-600">
                        <span class="font-bold text-gray-700">Emitente cadastrado:</span>
                        {{ businessProfile?.store_name || businessProfile?.display_name || 'Minha Loja' }}
                        <span v-if="businessProfile?.document_number"> • Doc: {{ businessProfile.document_number }}</span>
                        <span v-if="businessProfile?.phone"> • Tel: {{ businessProfile.phone }}</span>
                    </div>

                    <div class="flex items-center justify-end space-x-3 pt-4">
                        <Link
                            :href="route('transactions.index')"
                            class="rounded-md px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-100"
                        >
                            Cancelar
                        </Link>
                        <PrimaryButton
                            class="bg-indigo-600 hover:bg-indigo-500"
                            :class="{ 'opacity-25': form.processing }"
                            :disabled="form.processing"
                        >
                            Emitir Recibo Oficial
                        </PrimaryButton>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
