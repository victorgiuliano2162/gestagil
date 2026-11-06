<script setup lang="ts">
import Checkbox from '@/Components/Checkbox.vue';
import GuestLayout from '@/Layouts/GuestLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
});

const passwordRules = computed(() => [
    {
        label: 'Mínimo de 8 caracteres',
        valid: form.password.length >= 8,
    },
    {
        label: 'Pelo menos uma letra',
        valid: /[a-zA-Z]/.test(form.password),
    },
    {
        label: 'Pelo menos um número',
        valid: /[0-9]/.test(form.password),
    },
]);

const passwordsMatch = computed(() => {
    return form.password_confirmation.length > 0 && form.password === form.password_confirmation;
});

const submit = () => {
    form.post(route('register'), {
        onFinish: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};
</script>

<template>
    <GuestLayout>
        <Head title="Criar Conta - GestÁgil" />

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Nome Completo" />

                <TextInput
                    id="name"
                    type="text"
                    class="mt-1 block w-full"
                    v-model="form.name"
                    required
                    autofocus
                    autocomplete="name"
                />

                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="E-mail" />

                <TextInput
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    v-model="form.email"
                    required
                    autocomplete="username"
                />

                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Senha" />

                <TextInput
                    id="password"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password"
                    required
                    autocomplete="new-password"
                />

                <!-- Regras visuais da senha -->
                <div class="mt-2 rounded-md bg-gray-50 p-3 border border-gray-200">
                    <p class="text-xs font-semibold text-gray-600 mb-1.5">Requisitos para sua senha:</p>
                    <ul class="space-y-1 text-xs">
                        <li
                            v-for="(rule, idx) in passwordRules"
                            :key="idx"
                            class="flex items-center space-x-1.5"
                            :class="rule.valid ? 'text-emerald-700 font-medium' : 'text-gray-500'"
                        >
                            <svg v-if="rule.valid" class="w-3.5 h-3.5 text-emerald-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                            </svg>
                            <svg v-else class="w-3.5 h-3.5 text-gray-400 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                <circle cx="10" cy="10" r="3" />
                            </svg>
                            <span>{{ rule.label }}</span>
                        </li>
                    </ul>
                </div>

                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel
                    for="password_confirmation"
                    value="Confirmar Senha"
                />

                <TextInput
                    id="password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    v-model="form.password_confirmation"
                    required
                    autocomplete="new-password"
                />

                <p
                    v-if="form.password_confirmation.length > 0 && !passwordsMatch"
                    class="mt-1.5 text-xs text-amber-600 font-medium flex items-center space-x-1"
                >
                    <span>⚠️ As senhas digitadas ainda não conferem.</span>
                </p>
                <p
                    v-else-if="passwordsMatch"
                    class="mt-1.5 text-xs text-emerald-600 font-medium flex items-center space-x-1"
                >
                    <span>✓ As senhas conferem perfeitamente.</span>
                </p>

                <InputError
                    class="mt-2"
                    :message="form.errors.password_confirmation"
                />
            </div>

            <div class="mt-4">
                <label class="flex items-center">
                    <Checkbox name="terms" v-model:checked="form.terms" required />
                    <span class="ms-2 text-sm text-gray-600">
                        Concordo com os
                        <a target="_blank" href="/termos" class="underline text-indigo-600 hover:text-indigo-900">Termos de Uso</a>
                        e Política de Privacidade.
                    </span>
                </label>
                <InputError class="mt-2" :message="form.errors.terms" />
            </div>

            <div class="mt-6 flex items-center justify-between">
                <Link
                    :href="route('login')"
                    class="rounded-md text-sm text-gray-600 underline hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2"
                >
                    Já possui conta? Entrar
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing"
                >
                    Criar Conta
                </PrimaryButton>
            </div>
        </form>
    </GuestLayout>
</template>
