<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import DangerButton from '@/Components/DangerButton.vue';
import Modal from '@/Components/Modal.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

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

const props = defineProps<{
    client: Client;
}>();

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
                    <h2 class="text-xl font-semibold leading-tight text-gray-800">
                        {{ client.name }}
                    </h2>
                    <p class="text-sm text-gray-500">
                        Cadastrado em {{ new Date(client.created_at).toLocaleDateString('pt-BR') }}
                    </p>
                </div>
                <div class="flex items-center space-x-3">
                    <Link
                        :href="route('clients.index')"
                        class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
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
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <h3 class="text-lg font-medium text-gray-900 border-b pb-3 mb-4">
                        Informações Gerais
                    </h3>

                    <dl class="grid grid-cols-1 gap-x-4 gap-y-6 sm:grid-cols-2">
                        <div>
                            <dt class="text-sm font-medium text-gray-500">Nome Completo</dt>
                            <dd class="mt-1 text-base text-gray-900">{{ client.name }}</dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Telefone / WhatsApp</dt>
                            <dd class="mt-1 text-base text-gray-900">
                                <a
                                    v-if="client.phone_e164"
                                    :href="`https://wa.me/${client.phone_e164.replace(/\D/g, '')}`"
                                    target="_blank"
                                    class="text-indigo-600 hover:text-indigo-900 underline inline-flex items-center"
                                >
                                    {{ client.phone_e164 }}
                                </a>
                                <span v-else class="text-gray-400">Não informado</span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">Documento</dt>
                            <dd class="mt-1 text-base text-gray-900">
                                <span v-if="client.document_number">
                                    {{ client.document_type?.toUpperCase() }}: {{ client.document_number }}
                                </span>
                                <span v-else class="text-gray-400">Não informado</span>
                            </dd>
                        </div>

                        <div>
                            <dt class="text-sm font-medium text-gray-500">E-mail</dt>
                            <dd class="mt-1 text-base text-gray-900">
                                <a
                                    v-if="client.email"
                                    :href="`mailto:${client.email}`"
                                    class="text-indigo-600 hover:text-indigo-900 underline"
                                >
                                    {{ client.email }}
                                </a>
                                <span v-else class="text-gray-400">Não informado</span>
                            </dd>
                        </div>

                        <div class="sm:col-span-2">
                            <dt class="text-sm font-medium text-gray-500">Observações</dt>
                            <dd class="mt-1 text-base text-gray-900 whitespace-pre-line bg-gray-50 p-4 rounded-md border">
                                {{ client.notes || 'Nenhuma anotação registrada para este cliente.' }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Modal de Confirmação de Exclusão -->
                <Modal :show="confirmingClientDeletion" @close="closeModal">
                    <div class="p-6">
                        <h2 class="text-lg font-medium text-gray-900">
                            Confirmar exclusão de cliente
                        </h2>

                        <p class="mt-1 text-sm text-gray-600">
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
