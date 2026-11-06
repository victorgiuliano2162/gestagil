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
}

interface AppointmentData {
    id: number;
    client_id: number;
    service_description: string;
    service_at_formatted: string;
    expected_amount: number | null;
    technical_notes: string | null;
}

const props = defineProps<{
    appointment: AppointmentData;
    clients: Client[];
}>();

const form = useForm({
    client_id: props.appointment.client_id,
    service_description: props.appointment.service_description,
    service_at: props.appointment.service_at_formatted,
    expected_amount: props.appointment.expected_amount !== null ? props.appointment.expected_amount.toString() : '',
    technical_notes: props.appointment.technical_notes || '',
});

const submit = () => {
    form.put(route('appointments.update', props.appointment.id));
};
</script>

<template>
    <Head :title="`Editar Atendimento #${appointment.id} - GestÁgil`" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">
                    Editar Atendimento #{{ appointment.id }}
                </h2>
                <Link
                    :href="route('appointments.show', appointment.id)"
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
                        <!-- Seleção de Cliente -->
                        <div>
                            <InputLabel for="client_id" value="Cliente *" />
                            <select
                                id="client_id"
                                v-model="form.client_id"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                required
                            >
                                <option v-for="c in clients" :key="c.id" :value="c.id">
                                    {{ c.name }} {{ c.phone_e164 ? `(${c.phone_e164})` : '' }}
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
                                />
                                <InputError class="mt-2" :message="form.errors.expected_amount" />
                            </div>
                        </div>

                        <!-- Descrição do Serviço -->
                        <div>
                            <InputLabel for="service_description" value="Descrição do Serviço ou Pedido *" />
                            <textarea
                                id="service_description"
                                v-model="form.service_description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
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
                            ></textarea>
                            <InputError class="mt-2" :message="form.errors.technical_notes" />
                        </div>

                        <!-- Ações -->
                        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                            <Link :href="route('appointments.show', appointment.id)">
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
