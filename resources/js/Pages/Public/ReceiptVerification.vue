<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

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
    status: 'issued' | 'canceled';
    issued_at: string;
    formatted_amount: string;
    amount_in_words: string;
    payment_method: string;
    description: string;
    payer_name: string;
    payer_document?: string | null;
    issuer_snapshot: IssuerSnapshot;
    canceled_at?: string | null;
    cancel_reason?: string | null;
}

defineProps<{
    receipt: Receipt;
    pdfUrl: string;
}>();

const formatDate = (dateStr: string): string => {
    if (!dateStr) return '';
    return new Date(dateStr).toLocaleString('pt-BR');
};
</script>

<template>
    <Head :title="'Verificação de Recibo Nº ' + receipt.formatted_number + ' - GestÁgil'" />

    <div class="min-h-screen bg-gray-50 flex flex-col justify-center py-12 sm:px-6 lg:px-8">
        <div class="sm:mx-auto sm:w-full sm:max-w-xl text-center">
            <Link href="/" class="inline-flex items-center space-x-2">
                <img src="/assets/gestagil-icon-removebg.png" alt="GestÁgil" class="h-10 w-10 object-contain" />
                <span class="text-2xl font-black text-indigo-900 tracking-tight">GestÁgil</span>
            </Link>
            <h2 class="mt-3 text-lg font-bold text-gray-900">
                Verificador Oficial de Autenticidade Digital
            </h2>
            <p class="text-xs text-gray-500">
                Consulta pública e instantânea de comprovante comercial
            </p>
        </div>

        <div class="mt-6 sm:mx-auto sm:w-full sm:max-w-xl">
            <div class="bg-white py-8 px-6 shadow-sm rounded-xl sm:px-10 border border-gray-200 space-y-6">
                <!-- Status Badge -->
                <div
                    class="p-4 rounded-lg flex items-center justify-between"
                    :class="receipt.status === 'issued' ? 'bg-emerald-50 border border-emerald-200' : 'bg-rose-50 border border-rose-200'"
                >
                    <div class="flex items-center space-x-3">
                        <span class="text-2xl">{{ receipt.status === 'issued' ? '✅' : '⚠️' }}</span>
                        <div>
                            <div class="font-bold text-sm" :class="receipt.status === 'issued' ? 'text-emerald-900' : 'text-rose-900'">
                                {{ receipt.status === 'issued' ? 'Recibo Válido e Autêntico' : 'Este Recibo foi CANCELADO' }}
                            </div>
                            <div class="text-xs" :class="receipt.status === 'issued' ? 'text-emerald-700' : 'text-rose-700'">
                                {{ receipt.status === 'issued' ? 'Emitido diretamente pelo comerciante' : 'Cancelado em: ' + formatDate(receipt.canceled_at || '') }}
                            </div>
                        </div>
                    </div>

                    <a
                        :href="pdfUrl"
                        target="_blank"
                        class="text-xs font-semibold px-3 py-1.5 rounded bg-white shadow-sm border border-gray-200 hover:bg-gray-50 text-gray-700"
                    >
                        Baixar PDF
                    </a>
                </div>

                <!-- Detalhes do Recibo -->
                <div class="border-b border-gray-100 pb-4 space-y-1">
                    <div class="flex justify-between items-baseline">
                        <span class="text-xs text-gray-400 uppercase font-bold">Identificação</span>
                        <span class="text-base font-black text-indigo-700">Recibo Nº {{ receipt.formatted_number }}</span>
                    </div>
                    <div class="flex justify-between items-baseline text-xs text-gray-500">
                        <span>Data de Emissão:</span>
                        <span>{{ formatDate(receipt.issued_at) }}</span>
                    </div>
                </div>

                <!-- Valor -->
                <div class="bg-gray-50 p-4 rounded-lg flex justify-between items-center">
                    <span class="text-xs font-bold text-gray-500 uppercase">Valor do Pagamento</span>
                    <span class="text-2xl font-black text-emerald-600">{{ receipt.formatted_amount }}</span>
                </div>
                <div class="text-xs italic text-gray-600 px-1">
                    ({{ receipt.amount_in_words }})
                </div>

                <!-- Dados do Emitente e Pagador -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-xs border-t border-b border-gray-100 py-4">
                    <div>
                        <span class="font-bold text-gray-400 uppercase">Emitente / Prestador</span>
                        <div class="font-semibold text-gray-900 text-sm mt-0.5">
                            {{ receipt.issuer_snapshot.store_name || receipt.issuer_snapshot.display_name }}
                        </div>
                        <div v-if="receipt.issuer_snapshot.document_number" class="text-gray-500 mt-0.5">
                            Doc: {{ receipt.issuer_snapshot.document_number }}
                        </div>
                        <div v-if="receipt.issuer_snapshot.phone" class="text-gray-500">
                            Tel: {{ receipt.issuer_snapshot.phone }}
                        </div>
                    </div>

                    <div>
                        <span class="font-bold text-gray-400 uppercase">Pagador / Cliente</span>
                        <div class="font-semibold text-gray-900 text-sm mt-0.5">
                            {{ receipt.payer_name }}
                        </div>
                        <div v-if="receipt.payer_document" class="text-gray-500 mt-0.5">
                            CPF/CNPJ: {{ receipt.payer_document }}
                        </div>
                        <div class="text-gray-500 mt-0.5">
                            Forma: <strong class="uppercase text-gray-700">{{ receipt.payment_method }}</strong>
                        </div>
                    </div>
                </div>

                <!-- Descrição -->
                <div>
                    <span class="text-xs font-bold text-gray-400 uppercase">Descrição do Serviço / Venda</span>
                    <p class="text-sm text-gray-800 mt-1 whitespace-pre-line leading-relaxed">
                        {{ receipt.description }}
                    </p>
                </div>

                <!-- Chave de Autenticidade -->
                <div class="pt-4 border-t border-gray-100 text-center">
                    <span class="text-xs text-gray-400">Código Hash do Documento:</span>
                    <div class="font-mono text-xs font-bold text-indigo-600 break-all mt-0.5">
                        {{ receipt.code }}
                    </div>
                </div>
            </div>

            <div class="mt-6 text-center text-xs text-gray-400">
                Sistema GestÁgil — Gestão Ágil, Simples e Segura para Microempreendedores.
            </div>
        </div>
    </div>
</template>
