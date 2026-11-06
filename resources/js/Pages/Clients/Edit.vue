<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

interface Client {
    id: number;
    name: string;
    phone_e164: string | null;
    document_type: string | null;
    document_number: string | null;
    email: string | null;
    notes: string | null;
}

const props = defineProps<{
    client: Client;
}>();

const form = useForm({
    name: props.client.name,
    phone_e164: props.client.phone_e164 || '',
    document_type: props.client.document_type || 'cpf',
    document_number: props.client.document_number || '',
    email: props.client.email || '',
    notes: props.client.notes || '',
});

const submit = () => {
    form.put(route('clients.update', props.client.id));
};
</script>

<template>
    <Head :title="`Editar ${client.name} - GestÁgil`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Editar Cliente: {{ client.name }}
                </h2>
                <Link
                    :href="route('clients.show', client.id)"
                    class="text-sm font-medium text-gray-600 hover:text-gray-900"
                >
                    &larr; Voltar para detalhes
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Nome -->
                        <div>
                            <InputLabel for="name" value="Nome Completo *" />
                            <TextInput
                                id="name"
                                type="text"
                                class="mt-1 block w-full"
                                v-model="form.name"
                                required
                                autofocus
                            />
                            <InputError class="mt-2" :message="form.errors.name" />
                        </div>

                        <!-- Telefone & E-mail -->
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="phone_e164" value="Telefone / WhatsApp" />
                                <TextInput
                                    id="phone_e164"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.phone_e164"
                                    placeholder="Ex: 81999998888"
                                />
                                <InputError class="mt-2" :message="form.errors.phone_e164" />
                            </div>

                            <div>
                                <InputLabel for="email" value="E-mail" />
                                <TextInput
                                    id="email"
                                    type="email"
                                    class="mt-1 block w-full"
                                    v-model="form.email"
                                    placeholder="cliente@exemplo.com"
                                />
                                <InputError class="mt-2" :message="form.errors.email" />
                            </div>
                        </div>

                        <!-- Tipo e Número do Documento -->
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div>
                                <InputLabel for="document_type" value="Tipo de Documento" />
                                <select
                                    id="document_type"
                                    v-model="form.document_type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option value="cpf">CPF</option>
                                    <option value="cnpj">CNPJ</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.document_type" />
                            </div>

                            <div class="sm:col-span-2">
                                <InputLabel for="document_number" value="Número do Documento" />
                                <TextInput
                                    id="document_number"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.document_number"
                                />
                                <InputError class="mt-2" :message="form.errors.document_number" />
                            </div>
                        </div>

                        <!-- Observações -->
                        <div>
                            <InputLabel for="notes" value="Observações / Anotações" />
                            <textarea
                                id="notes"
                                v-model="form.notes"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.notes" />
                        </div>

                        <!-- Botões de ação -->
                        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                            <Link :href="route('clients.show', client.id)">
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
