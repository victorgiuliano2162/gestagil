<script setup lang="ts">
import { ref } from 'vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import DangerButton from '@/Components/DangerButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import InputError from '@/Components/InputError.vue';
import Modal from '@/Components/Modal.vue';
import Checkbox from '@/Components/Checkbox.vue';

interface Stats {
    total_clients: number;
    total_appointments: number;
    total_transactions: number;
    total_receipts: number;
}

const props = defineProps<{
    stats: Stats;
}>();

const page = usePage();

const form = useForm({
    backup_file: null as File | null,
    mode: 'merge' as 'merge' | 'replace',
    confirmed_overwrite: false,
});

const isModalOpen = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

const onFileChange = (e: Event) => {
    const target = e.target as HTMLInputElement;
    if (target.files && target.files.length > 0) {
        form.backup_file = target.files[0];
    }
};

const submitRestore = () => {
    if (form.mode === 'replace' && !form.confirmed_overwrite) {
        isModalOpen.value = true;
        return;
    }

    form.post(route('backup.restore'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            if (fileInput.value) {
                fileInput.value.value = '';
            }
            isModalOpen.value = false;
        },
    });
};

const confirmReplaceAndSubmit = () => {
    form.confirmed_overwrite = true;
    isModalOpen.value = false;
    submitRestore();
};
</script>

<template>
    <Head title="Backup & Restauração" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Backup e Restauração de Dados
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
                <!-- Flash Messages -->
                <div
                    v-if="$page.props.flash?.success"
                    class="rounded-lg bg-emerald-50 p-4 border border-emerald-200 text-emerald-800 flex items-center justify-between"
                >
                    <div class="flex items-center space-x-2">
                        <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>{{ $page.props.flash.success }}</span>
                    </div>
                </div>

                <!-- Resumo dos Dados Atuais -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 mb-4">
                        Status do Banco de Dados Local
                    </h3>
                    <p class="text-sm text-gray-600 mb-6">
                        Aqui está o resumo dos seus registros cadastrados no GestAgil neste computador. Seus dados são salvos localmente e pertencem exclusivamente a você.
                    </p>

                    <div class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 text-center">
                            <span class="text-2xl font-bold text-slate-800">{{ stats.total_clients }}</span>
                            <span class="block text-xs font-medium text-slate-500 uppercase mt-1">Clientes</span>
                        </div>
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 text-center">
                            <span class="text-2xl font-bold text-slate-800">{{ stats.total_appointments }}</span>
                            <span class="block text-xs font-medium text-slate-500 uppercase mt-1">Atendimentos</span>
                        </div>
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 text-center">
                            <span class="text-2xl font-bold text-slate-800">{{ stats.total_transactions }}</span>
                            <span class="block text-xs font-medium text-slate-500 uppercase mt-1">Transações</span>
                        </div>
                        <div class="bg-slate-50 border border-slate-200 rounded-lg p-4 text-center">
                            <span class="text-2xl font-bold text-slate-800">{{ stats.total_receipts }}</span>
                            <span class="block text-xs font-medium text-slate-500 uppercase mt-1">Recibos</span>
                        </div>
                    </div>
                </div>

                <!-- Exportar Dados -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <div class="max-w-2xl">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            Exportar Backup Completo
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Gera um arquivo de segurança estruturado (<code class="bg-gray-100 px-1 py-0.5 rounded text-xs font-mono">.json</code>) contendo todos os seus clientes, atendimentos, movimentações financeiras, recibos e perfil do negócio.
                        </p>
                        <p class="text-xs text-gray-500 mb-6">
                            Você pode guardar este arquivo em seu computador, pen drive ou enviar para o seu Google Drive para manter seus dados protegidos contra falhas de equipamento.
                        </p>

                        <a
                            :href="route('backup.export')"
                            class="inline-flex items-center px-4 py-2 bg-indigo-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
                        >
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                            </svg>
                            Baixar Arquivo de Backup (.json)
                        </a>
                    </div>
                </div>

                <!-- Restaurar Dados -->
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <div class="max-w-2xl">
                        <h3 class="text-lg font-medium text-gray-900 mb-2">
                            Restaurar Cópia de Segurança
                        </h3>
                        <p class="text-sm text-gray-600 mb-4">
                            Envie um arquivo de backup do GestAgil previamente exportado para recuperar ou mesclar informações.
                        </p>

                        <form @submit.prevent="submitRestore" class="space-y-6">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Arquivo de Backup (.json)
                                </label>
                                <input
                                    ref="fileInput"
                                    type="file"
                                    accept=".json,application/json"
                                    @change="onFileChange"
                                    class="block w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-indigo-50 file:text-indigo-700 hover:file:bg-indigo-100 border border-gray-300 rounded-md p-1 cursor-pointer"
                                />
                                <InputError :message="form.errors.backup_file" class="mt-2" />
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-2">
                                    Modo de Restauração
                                </label>
                                <div class="space-y-3">
                                    <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-gray-50 transition" :class="form.mode === 'merge' ? 'border-indigo-500 bg-indigo-50/30' : 'border-gray-200'">
                                        <input
                                            type="radio"
                                            value="merge"
                                            v-model="form.mode"
                                            class="mt-1 text-indigo-600 focus:ring-indigo-500"
                                        />
                                        <div class="ml-3">
                                            <span class="block text-sm font-semibold text-gray-900">Mesclar Registros (Recomendado)</span>
                                            <span class="block text-xs text-gray-500">Mantém os dados existentes na máquina e adiciona ou atualiza registros usando identificadores universais únicos (UUIDs). Não apaga nada.</span>
                                        </div>
                                    </label>

                                    <label class="flex items-start p-3 border rounded-lg cursor-pointer hover:bg-red-50/20 transition" :class="form.mode === 'replace' ? 'border-rose-500 bg-rose-50/30' : 'border-gray-200'">
                                        <input
                                            type="radio"
                                            value="replace"
                                            v-model="form.mode"
                                            class="mt-1 text-rose-600 focus:ring-rose-500"
                                        />
                                        <div class="ml-3">
                                            <span class="block text-sm font-semibold text-rose-700">Substituir Todos os Dados (Avançado)</span>
                                            <span class="block text-xs text-rose-600">Substitui completamente os clientes, atendimentos, transações e recibos locais pelos dados do arquivo enviado. Requer confirmação de segurança.</span>
                                        </div>
                                    </label>
                                </div>
                                <InputError :message="form.errors.mode" class="mt-2" />
                            </div>

                            <div class="flex items-center space-x-3 pt-2">
                                <PrimaryButton
                                    v-if="form.mode === 'merge'"
                                    :disabled="form.processing || !form.backup_file"
                                >
                                    {{ form.processing ? 'Processando...' : 'Restaurar e Mesclar Dados' }}
                                </PrimaryButton>

                                <DangerButton
                                    v-else
                                    type="button"
                                    :disabled="form.processing || !form.backup_file"
                                    @click="isModalOpen = true"
                                >
                                    {{ form.processing ? 'Processando...' : 'Substituir Dados com Backup...' }}
                                </DangerButton>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Confirmação para Sobrescrita -->
        <Modal :show="isModalOpen" @close="isModalOpen = false">
            <div class="p-6">
                <div class="flex items-center space-x-3 text-rose-600 mb-4">
                    <svg class="w-8 h-8 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <h3 class="text-lg font-bold text-gray-900">
                        Atenção: Substituição Total de Dados
                    </h3>
                </div>

                <p class="text-sm text-gray-600 mb-4">
                    Esta ação removerá todos os seus atendimentos, clientes, transações financeiras e recibos atuais antes de carregar o arquivo selecionado.
                </p>

                <p class="text-xs text-gray-500 mb-6 bg-amber-50 p-3 rounded-md border border-amber-200">
                    <strong>Cópia de Segurança Automática:</strong> Antes de efetuar a substituição, o sistema salvará um instantâneo de segurança preventivo na pasta de backups do sistema.
                </p>

                <div class="mb-6">
                    <label class="flex items-center cursor-pointer">
                        <Checkbox v-model:checked="form.confirmed_overwrite" />
                        <span class="ml-2 text-sm font-medium text-gray-800">
                            Estou ciente e confirmo que desejo substituir meus dados locais pelo arquivo de backup.
                        </span>
                    </label>
                    <InputError :message="form.errors.confirmed_overwrite" class="mt-2" />
                </div>

                <div class="flex justify-end space-x-3">
                    <SecondaryButton @click="isModalOpen = false">
                        Cancelar
                    </SecondaryButton>

                    <DangerButton
                        :disabled="!form.confirmed_overwrite || form.processing"
                        @click="confirmReplaceAndSubmit"
                    >
                        Confirmar e Substituir Dados
                    </DangerButton>
                </div>
            </div>
        </Modal>
    </AuthenticatedLayout>
</template>
