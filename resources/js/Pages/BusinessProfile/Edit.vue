<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';

interface BusinessProfile {
    id: number;
    store_name: string | null;
    display_name: string;
    activity_description: string | null;
    document_type: string | null;
    document_number: string | null;
    phone: string | null;
    postal_code: string | null;
    street: string | null;
    number: string | null;
    complement: string | null;
    neighborhood: string | null;
    city: string | null;
    state: string | null;
    logo_path: string | null;
    logo_url: string | null;
}

const props = defineProps<{
    profile: BusinessProfile;
    status?: string;
}>();

import { ref } from 'vue';

const logoPreview = ref<string | null>(props.profile.logo_url || null);

const form = useForm({
    store_name: props.profile.store_name || '',
    display_name: props.profile.display_name || '',
    activity_description: props.profile.activity_description || '',
    document_type: props.profile.document_type || 'cnpj',
    document_number: props.profile.document_number || '',
    phone: props.profile.phone || '',
    postal_code: props.profile.postal_code || '',
    street: props.profile.street || '',
    number: props.profile.number || '',
    complement: props.profile.complement || '',
    neighborhood: props.profile.neighborhood || '',
    city: props.profile.city || '',
    state: props.profile.state || '',
    logo: null as File | null,
});

const onLogoSelected = (event: Event) => {
    const target = event.target as HTMLInputElement;
    if (target.files && target.files[0]) {
        const file = target.files[0];
        form.logo = file;
        logoPreview.value = URL.createObjectURL(file);
    }
};

const submit = () => {
    form.post(route('business-profile.update'), {
        preserveScroll: true,
        forceFormData: true,
    });
};
</script>

<template>
    <Head title="Minha Loja / Negócio - GestÁgil" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Dados da Minha Loja / Comércio
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <div class="bg-white p-6 shadow sm:rounded-lg">
                    <header class="mb-6 border-b pb-4">
                        <h3 class="text-lg font-medium text-gray-900">
                            Identidade Comercial
                        </h3>
                        <p class="mt-1 text-sm text-gray-600">
                            Configure o nome da sua loja e a descrição das suas atividades comerciais para emissão de orçamentos e relatórios.
                        </p>
                    </header>

                    <form @submit.prevent="submit" class="space-y-6">
                        <!-- Logotipo da Loja -->
                        <div class="rounded-lg border border-gray-200 bg-gray-50/50 p-4">
                            <InputLabel value="Logotipo da Loja / Comércio" class="mb-2" />
                            <div class="flex flex-col sm:flex-row items-center gap-5">
                                <div class="h-24 w-24 shrink-0 rounded-lg border-2 border-dashed border-gray-300 bg-white flex items-center justify-center overflow-hidden shadow-inner">
                                    <img
                                        v-if="logoPreview"
                                        :src="logoPreview"
                                        alt="Logotipo da Loja"
                                        class="h-full w-full object-contain p-1"
                                    />
                                    <div v-else class="text-center p-2">
                                        <span class="text-2xl">🏪</span>
                                        <p class="text-[10px] text-gray-400 mt-1">Sem logo</p>
                                    </div>
                                </div>
                                <div class="space-y-1.5 text-center sm:text-left">
                                    <input
                                        type="file"
                                        accept="image/png,image/jpeg,image/jpg,image/webp"
                                        id="logo_file"
                                        class="hidden"
                                        @change="onLogoSelected"
                                    />
                                    <label
                                        for="logo_file"
                                        class="cursor-pointer inline-flex items-center rounded-md border border-gray-300 bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
                                    >
                                        Escolher imagem
                                    </label>
                                    <p class="text-xs text-gray-500">
                                        PNG, JPG ou WEBP até 2MB. Usado no cabeçalho de orçamentos e recibos emitidos.
                                    </p>
                                    <InputError :message="form.errors.logo" class="mt-1" />
                                </div>
                            </div>
                        </div>

                        <!-- Nome da Loja & Nome de Exibição -->
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
                            <div>
                                <InputLabel for="store_name" value="Nome Fantasia / Nome da Loja *" />
                                <TextInput
                                    id="store_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.store_name"
                                    placeholder="Ex: Mercadinho Esperança, Loja da Ana"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.store_name" />
                            </div>

                            <div>
                                <InputLabel for="display_name" value="Razão Social / Nome do Titular *" />
                                <TextInput
                                    id="display_name"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.display_name"
                                    required
                                />
                                <InputError class="mt-2" :message="form.errors.display_name" />
                            </div>
                        </div>

                        <!-- Descrição de Atividade Comercial -->
                        <div>
                            <InputLabel for="activity_description" value="Ramo de Atividade / Descrição do Negócio" />
                            <textarea
                                id="activity_description"
                                v-model="form.activity_description"
                                rows="3"
                                class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                placeholder="Ex: Comércio varejista de roupas e acessórios, minimercado e conveniência, serviços de manutenção..."
                            ></textarea>
                            <p class="mt-1 text-xs text-gray-500">
                                Ajuda a categorizar e organizar melhor o perfil do seu negócio no GestÁgil.
                            </p>
                            <InputError class="mt-2" :message="form.errors.activity_description" />
                        </div>

                        <!-- Documento e Telefone -->
                        <div class="grid grid-cols-1 gap-6 sm:grid-cols-3">
                            <div>
                                <InputLabel for="document_type" value="Tipo de Documento" />
                                <select
                                    id="document_type"
                                    v-model="form.document_type"
                                    class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                    <option value="cnpj">CNPJ</option>
                                    <option value="cpf">CPF</option>
                                </select>
                                <InputError class="mt-2" :message="form.errors.document_type" />
                            </div>

                            <div>
                                <InputLabel for="document_number" value="Número do Documento" />
                                <TextInput
                                    id="document_number"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.document_number"
                                    placeholder="Ex: 00.000.000/0001-00"
                                />
                                <InputError class="mt-2" :message="form.errors.document_number" />
                            </div>

                            <div>
                                <InputLabel for="phone" value="WhatsApp Comercial" />
                                <TextInput
                                    id="phone"
                                    type="text"
                                    class="mt-1 block w-full"
                                    v-model="form.phone"
                                    placeholder="Ex: (81) 99999-8888"
                                />
                                <InputError class="mt-2" :message="form.errors.phone" />
                            </div>
                        </div>

                        <!-- Endereço Comercial -->
                        <div class="border-t pt-4">
                            <h4 class="text-sm font-semibold text-gray-700 mb-3">Endereço do Ponto Comercial</h4>
                            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                                <div>
                                    <InputLabel for="postal_code" value="CEP" />
                                    <TextInput id="postal_code" v-model="form.postal_code" type="text" class="mt-1 block w-full" />
                                    <InputError class="mt-2" :message="form.errors.postal_code" />
                                </div>
                                <div class="sm:col-span-2">
                                    <InputLabel for="street" value="Logradouro (Rua, Av.)" />
                                    <TextInput id="street" v-model="form.street" type="text" class="mt-1 block w-full" />
                                    <InputError class="mt-2" :message="form.errors.street" />
                                </div>
                                <div>
                                    <InputLabel for="number" value="Número" />
                                    <TextInput id="number" v-model="form.number" type="text" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <InputLabel for="city" value="Cidade" />
                                    <TextInput id="city" v-model="form.city" type="text" class="mt-1 block w-full" />
                                </div>
                                <div>
                                    <InputLabel for="state" value="UF (Estado)" />
                                    <TextInput id="state" v-model="form.state" maxlength="2" type="text" class="mt-1 block w-full uppercase" placeholder="Ex: PE" />
                                    <InputError class="mt-2" :message="form.errors.state" />
                                </div>
                            </div>
                        </div>

                        <!-- Botão de Salvar e Feedback -->
                        <div class="flex items-center justify-end space-x-4 border-t pt-4">
                            <Transition
                                enter-active-class="transition ease-in-out"
                                enter-from-class="opacity-0"
                                leave-active-class="transition ease-in-out"
                                leave-to-class="opacity-0"
                            >
                                <p v-if="form.recentlySuccessful" class="text-sm text-emerald-600 font-medium">
                                    ✓ Informações da loja salvas com sucesso!
                                </p>
                            </Transition>

                            <PrimaryButton :disabled="form.processing">
                                Salvar Dados da Loja
                            </PrimaryButton>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
