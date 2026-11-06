<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import Modal from '@/Components/Modal.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Client {
    id: number;
    name: string;
    email?: string | null;
    phone_e164?: string | null;
}

interface IssuerSnapshot {
    store_name?: string;
    display_name?: string;
    document_type?: string;
    document_number?: string;
    phone?: string;
    full_address?: string | null;
}

interface Receipt {
    id: number;
    formatted_number: string;
    code: string;
    year: number;
    number: number;
    status: 'issued' | 'canceled';
    issued_at: string;
    amount_cents: number;
    formatted_amount: string;
    amount_in_words: string;
    payment_method: string;
    description: string;
    payer_name: string;
    payer_document?: string | null;
    issuer_snapshot: IssuerSnapshot;
    canceled_at?: string | null;
    cancel_reason?: string | null;
    client?: Client | null;
}

const props = defineProps<{
    receipt: Receipt;
    publicUrl: string;
}>();

const showCancelModal = ref(false);
const showEmailModal = ref(false);
const copied = ref(false);

const cancelForm = useForm({
    cancel_reason: '',
});

const emailForm = useForm({
    email: props.receipt.client?.email || '',
});

const submitCancel = () => {
    cancelForm.post(route('receipts.cancel', props.receipt.id), {
        onSuccess: () => {
            showCancelModal.value = false;
        },
    });
};

const submitEmail = () => {
    emailForm.post(route('receipts.email', props.receipt.id), {
        onSuccess: () => {
            showEmailModal.value = false;
        },
    });
};

const copyPublicUrl = () => {
    navigator.clipboard.writeText(props.publicUrl);
    copied.value = true;
    setTimeout(() => {
        copied.value = false;
    }, 2500);
};

const getWhatsAppShareUrl = () => {
    const store = props.receipt.issuer_snapshot.store_name || 'nossa loja';
    const text = `Olá, ${props.receipt.payer_name}! Segue o comprovante de pagamento do seu atendimento em ${store}:\n\n` +
                 `📄 Recibo Nº ${props.receipt.formatted_number}\n` +
                 `💰 Valor: ${props.receipt.formatted_amount}\n` +
                 `🔗 Acesse o comprovante oficial completo aqui: ${props.publicUrl}`;

    const cleanPhone = props.receipt.client?.phone_e164 ? props.receipt.client.phone_e164.replace(/\D/g, '') : '';
    return cleanPhone
        ? `https://wa.me/${cleanPhone}?text=${encodeURIComponent(text)}`
        : `https://wa.me/?text=${encodeURIComponent(text)}`;
};

const formatDate = (dateStr: string): string => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleString('pt-BR');
};
</script>

<template>
    <Head :title="'Recibo Nº ' + receipt.formatted_number + ' - GestÁgil'" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <div class="flex items-center space-x-3">
                        <h2 class="text-xl font-bold leading-tight text-gray-900">
                            Recibo Nº {{ receipt.formatted_number }}
                        </h2>
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold"
                            :class="receipt.status === 'issued' ? 'bg-emerald-100 text-emerald-800' : 'bg-rose-100 text-rose-800'"
                        >
                            {{ receipt.status === 'issued' ? 'Emitido / Válido' : 'Cancelado' }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Emitido em {{ formatDate(receipt.issued_at) }}
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <!-- Baixar PDF -->
                    <a
                        :href="route('receipts.pdf', { receipt: receipt.id, disposition: 'attachment' })"
                        target="_blank"
                        class="inline-flex items-center rounded-md bg-white px-3.5 py-2 text-sm font-semibold text-gray-700 shadow-sm ring-1 ring-inset ring-gray-300 hover:bg-gray-50"
                    >
                        📄 Baixar PDF
                    </a>

                    <!-- Compartilhar WhatsApp -->
                    <a
                        :href="getWhatsAppShareUrl()"
                        target="_blank"
                        class="inline-flex items-center rounded-md bg-emerald-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500"
                    >
                        💬 Enviar no WhatsApp
                    </a>

                    <!-- Enviar por E-mail -->
                    <button
                        @click="showEmailModal = true"
                        class="inline-flex items-center rounded-md bg-indigo-600 px-3.5 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                    >
                        ✉️ Enviar por E-mail
                    </button>

                    <!-- Cancelar Recibo -->
                    <button
                        v-if="receipt.status === 'issued'"
                        @click="showCancelModal = true"
                        class="inline-flex items-center rounded-md bg-rose-50 px-3 py-2 text-sm font-semibold text-rose-700 ring-1 ring-inset ring-rose-200 hover:bg-rose-100"
                    >
                        ❌ Cancelar
                    </button>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8 space-y-6">
                <!-- Banner de Cancelamento se houver -->
                <div
                    v-if="receipt.status === 'canceled'"
                    class="rounded-xl border border-rose-200 bg-rose-50 p-5 shadow-sm"
                >
                    <div class="flex items-start">
                        <span class="text-2xl mr-3">⚠️</span>
                        <div>
                            <h3 class="text-base font-bold text-rose-900">
                                Este recibo foi oficialmente cancelado
                            </h3>
                            <p class="text-sm text-rose-700 mt-1">
                                Data do cancelamento: <strong>{{ formatDate(receipt.canceled_at || '') }}</strong>
                            </p>
                            <p v-if="receipt.cancel_reason" class="text-sm text-rose-800 mt-1">
                                Motivo: <em>"{{ receipt.cancel_reason }}"</em>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Box de Compartilhamento e Link Público -->
                <div class="bg-indigo-50 border border-indigo-100 p-4 rounded-xl flex flex-col sm:flex-row items-center justify-between gap-3">
                    <div class="text-sm text-indigo-900">
                        <span class="font-bold">Link de Verificação Pública:</span>
                        <a :href="publicUrl" target="_blank" class="underline text-indigo-700 ml-1 break-all">
                            {{ publicUrl }}
                        </a>
                    </div>
                    <button
                        @click="copyPublicUrl"
                        class="text-xs font-semibold px-3 py-1.5 rounded bg-white text-indigo-700 border border-indigo-200 hover:bg-indigo-100 transition whitespace-nowrap"
                    >
                        {{ copied ? '✓ Link Copiado!' : 'Copiar Link' }}
                    </button>
                </div>

                <!-- Simulação do Recibo Impresso / Visual do Comprovante -->
                <div class="bg-white p-8 rounded-xl shadow-sm border border-gray-200 space-y-6 relative overflow-hidden">
                    <!-- Tarja d'água de Cancelado -->
                    <div
                        v-if="receipt.status === 'canceled'"
                        class="absolute inset-0 flex items-center justify-center pointer-events-none"
                    >
                        <span class="text-6xl sm:text-8xl font-black text-rose-500/10 tracking-widest uppercase transform -rotate-12 border-8 border-rose-500/20 p-6 rounded-2xl">
                            CANCELADO
                        </span>
                    </div>

                    <!-- Cabeçalho do Recibo -->
                    <div class="border-b border-gray-200 pb-6 flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                        <div>
                            <h3 class="text-2xl font-black text-gray-900 uppercase tracking-tight">
                                {{ receipt.issuer_snapshot.store_name || receipt.issuer_snapshot.display_name }}
                            </h3>
                            <p class="text-xs text-gray-500 mt-1">
                                <span v-if="receipt.issuer_snapshot.document_number">
                                    {{ receipt.issuer_snapshot.document_type?.toUpperCase() }}: {{ receipt.issuer_snapshot.document_number }} •
                                </span>
                                <span v-if="receipt.issuer_snapshot.phone">
                                    Tel: {{ receipt.issuer_snapshot.phone }}
                                </span>
                            </p>
                            <p v-if="receipt.issuer_snapshot.full_address" class="text-xs text-gray-400 mt-0.5">
                                {{ receipt.issuer_snapshot.full_address }}
                            </p>
                        </div>

                        <div class="text-left sm:text-right bg-indigo-50/50 p-4 rounded-lg border border-indigo-100">
                            <span class="text-xs font-bold text-indigo-600 uppercase">Recibo Digital</span>
                            <div class="text-xl font-black text-indigo-900">
                                Nº {{ receipt.formatted_number }}
                            </div>
                            <span class="text-xs text-gray-500">
                                {{ formatDate(receipt.issued_at) }}
                            </span>
                        </div>
                    </div>

                    <!-- Valor Destaque -->
                    <div class="bg-gray-50 rounded-lg p-5 border border-gray-200 flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                        <span class="text-xs font-bold text-gray-500 uppercase tracking-wider">Valor Recebido</span>
                        <div class="text-3xl font-black text-emerald-600">
                            {{ receipt.formatted_amount }}
                        </div>
                    </div>

                    <!-- Valor por Extenso -->
                    <div class="p-4 rounded-lg bg-emerald-50/50 border-l-4 border-emerald-500 text-sm italic text-gray-800">
                        <strong>({{ receipt.amount_in_words }})</strong>
                    </div>

                    <!-- Dados do Pagador -->
                    <div class="space-y-2 border-t border-gray-100 pt-4">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Pagador / Cliente</h4>
                        <div class="text-base font-semibold text-gray-900">
                            {{ receipt.payer_name }}
                        </div>
                        <p v-if="receipt.payer_document" class="text-xs text-gray-500">
                            CPF/CNPJ: {{ receipt.payer_document }}
                        </p>
                    </div>

                    <!-- Descrição / Referente a -->
                    <div class="space-y-2 border-t border-gray-100 pt-4">
                        <h4 class="text-xs font-bold text-gray-400 uppercase tracking-wider">Referente a</h4>
                        <p class="text-sm text-gray-800 whitespace-pre-line leading-relaxed">
                            {{ receipt.description }}
                        </p>
                    </div>

                    <!-- Forma de Pagamento -->
                    <div class="border-t border-gray-100 pt-4 flex justify-between items-center text-sm">
                        <span class="text-gray-500">Forma de Pagamento:</span>
                        <span class="font-bold text-gray-900 uppercase bg-gray-100 px-3 py-1 rounded">
                            {{ receipt.payment_method }}
                        </span>
                    </div>

                    <!-- Rodapé de Autenticação -->
                    <div class="border-t border-gray-200 pt-6 text-center text-xs text-gray-400 space-y-1">
                        <p>Documento digital emitido através da plataforma GestÁgil.</p>
                        <p>Código de Autenticidade: <span class="font-mono font-bold text-indigo-600">{{ receipt.code }}</span></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Cancelamento -->
        <Modal :show="showCancelModal" @close="showCancelModal = false">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900">
                    Cancelar Recibo Nº {{ receipt.formatted_number }}
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Atenção: uma vez cancelado, o recibo permanecerá no histórico com a tarja de "CANCELADO" para auditoria e prestação de contas.
                </p>

                <div class="mt-4">
                    <InputLabel for="cancel_reason" value="Motivo do Cancelamento *" />
                    <textarea
                        id="cancel_reason"
                        v-model="cancelForm.cancel_reason"
                        rows="3"
                        required
                        class="mt-1 block w-full rounded-md border-gray-300 text-sm focus:border-rose-500 focus:ring-rose-500"
                        placeholder="Ex: Valor incorreto, devolução do pagamento, etc."
                    ></textarea>
                    <InputError class="mt-2" :message="cancelForm.errors.cancel_reason" />
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showCancelModal = false">
                        Voltar
                    </SecondaryButton>
                    <button
                        @click="submitCancel"
                        :disabled="cancelForm.processing"
                        class="inline-flex items-center rounded-md bg-rose-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-rose-500 disabled:opacity-50"
                    >
                        Confirmar Cancelamento
                    </button>
                </div>
            </div>
        </Modal>

        <!-- Modal de Envio por E-mail -->
        <Modal :show="showEmailModal" @close="showEmailModal = false">
            <div class="p-6">
                <h2 class="text-lg font-bold text-gray-900">
                    Enviar Recibo por E-mail
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    O cliente receberá uma mensagem com o resumo e o arquivo PDF oficial em anexo.
                </p>

                <div class="mt-4">
                    <InputLabel for="email" value="Endereço de E-mail do Cliente *" />
                    <TextInput
                        id="email"
                        v-model="emailForm.email"
                        type="email"
                        required
                        class="mt-1 block w-full"
                        placeholder="cliente@exemplo.com"
                    />
                    <InputError class="mt-2" :message="emailForm.errors.email" />
                </div>

                <div class="mt-6 flex justify-end space-x-3">
                    <SecondaryButton @click="showEmailModal = false">
                        Cancelar
                    </SecondaryButton>
                    <PrimaryButton
                        @click="submitEmail"
                        :disabled="emailForm.processing"
                    >
                        Enviar Recibo Agora
                    </PrimaryButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
