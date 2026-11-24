<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

interface Category {
    id: number;
    type: 'income' | 'expense';
    name: string;
    color: string | null;
    transactions_count: number;
}

const props = defineProps<{
    categories: Category[];
}>();

const createForm = useForm({
    type: 'income' as 'income' | 'expense',
    name: '',
    color: '#10b981',
});

const editingId = ref<number | null>(null);
const editForm = useForm({
    name: '',
    color: '',
});

const submitCreate = () => {
    createForm.post(route('categories.store'), {
        preserveScroll: true,
        onSuccess: () => createForm.reset('name'),
    });
};

const startEdit = (cat: Category) => {
    editingId.value = cat.id;
    editForm.name = cat.name;
    editForm.color = cat.color || '#6366f1';
};

const cancelEdit = () => {
    editingId.value = null;
};

const submitEdit = (id: number) => {
    editForm.put(route('categories.update', id), {
        preserveScroll: true,
        onSuccess: () => {
            editingId.value = null;
        },
    });
};

const deleteCategory = (cat: Category) => {
    if (confirm(`Deseja remover a categoria "${cat.name}"?`)) {
        router.delete(route('categories.destroy', cat.id), { preserveScroll: true });
    }
};
</script>

<template>
    <Head title="Categorias do Caixa - GestÁgil" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex items-center justify-between">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-gray-900 dark:text-gray-100">
                        Categorias de Lançamentos
                    </h2>
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                        Organize suas entradas e saídas por categorias personalizadas
                    </p>
                </div>
                <Link
                    :href="route('transactions.index')"
                    class="rounded-md border border-gray-300 dark:border-gray-700 bg-white dark:bg-gray-900 px-3 py-2 text-sm font-semibold text-gray-700 dark:text-gray-300 shadow-sm hover:bg-gray-50 dark:hover:bg-gray-800/60"
                >
                    &larr; Voltar ao Caixa
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8 space-y-6">
                <!-- Nova Categoria -->
                <div class="bg-white dark:bg-gray-900 p-6 shadow sm:rounded-lg">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Nova Categoria</h3>
                    <form @submit.prevent="submitCreate" class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-end">
                        <div>
                            <InputLabel for="cat_type" value="Tipo *" />
                            <select
                                id="cat_type"
                                v-model="createForm.type"
                                class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100 dark:placeholder-gray-400 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                            >
                                <option value="income">Entrada (Receita)</option>
                                <option value="expense">Saída (Despesa)</option>
                            </select>
                            <InputError class="mt-1" :message="createForm.errors.type" />
                        </div>

                        <div class="sm:col-span-2">
                            <InputLabel for="cat_name" value="Nome *" />
                            <TextInput
                                id="cat_name"
                                v-model="createForm.name"
                                type="text"
                                class="mt-1 block w-full"
                                placeholder="Ex: Manutenção, Peças, Aluguel..."
                                required
                            />
                            <InputError class="mt-1" :message="createForm.errors.name" />
                        </div>

                        <div class="flex items-center space-x-2">
                            <input
                                type="color"
                                v-model="createForm.color"
                                class="h-9 w-12 rounded border border-gray-300 dark:border-gray-700 dark:bg-gray-800 cursor-pointer"
                                title="Cor da categoria"
                            />
                            <PrimaryButton :disabled="createForm.processing" class="w-full justify-center">
                                Adicionar
                            </PrimaryButton>
                        </div>
                    </form>
                </div>

                <!-- Lista de Categorias -->
                <div class="bg-white dark:bg-gray-900 p-6 shadow sm:rounded-lg">
                    <h3 class="text-base font-semibold text-gray-900 dark:text-gray-100 mb-4">Categorias Cadastradas</h3>
                    <div class="divide-y divide-gray-200 dark:divide-gray-800">
                        <div
                            v-for="cat in categories"
                            :key="cat.id"
                            class="py-3 flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3"
                        >
                            <template v-if="editingId === cat.id">
                                <div class="flex items-center space-x-2 flex-1">
                                    <input type="color" v-model="editForm.color" class="h-8 w-10 rounded border border-gray-300 dark:border-gray-700 dark:bg-gray-800" />
                                    <TextInput v-model="editForm.name" type="text" class="w-full max-w-xs text-sm" />
                                    <InputError :message="editForm.errors.name" />
                                </div>
                                <div class="flex items-center space-x-2">
                                    <button
                                        type="button"
                                        @click="submitEdit(cat.id)"
                                        class="rounded bg-indigo-600 px-3 py-1 text-xs font-semibold text-white hover:bg-indigo-500"
                                    >
                                        Salvar
                                    </button>
                                    <button
                                        type="button"
                                        @click="cancelEdit"
                                        class="rounded border border-gray-300 dark:border-gray-700 px-3 py-1 text-xs font-semibold text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-800/60"
                                    >
                                        Cancelar
                                    </button>
                                </div>
                            </template>
                            <template v-else>
                                <div class="flex items-center space-x-3">
                                    <span
                                        class="h-3.5 w-3.5 rounded-full inline-block"
                                        :style="{ backgroundColor: cat.color || '#6b7280' }"
                                    ></span>
                                    <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ cat.name }}</span>
                                    <span
                                        class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                        :class="cat.type === 'income' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/50 dark:text-rose-300'"
                                    >
                                        {{ cat.type === 'income' ? 'Entrada' : 'Saída' }}
                                    </span>
                                    <span class="text-xs text-gray-400">
                                        ({{ cat.transactions_count }} lanç.)
                                    </span>
                                </div>
                                <div class="flex items-center space-x-3 text-xs">
                                    <button
                                        @click="startEdit(cat)"
                                        class="font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-300"
                                    >
                                        Editar
                                    </button>
                                    <button
                                        v-if="cat.transactions_count === 0"
                                        @click="deleteCategory(cat)"
                                        class="font-semibold text-rose-600 dark:text-rose-400 hover:text-rose-900 dark:hover:text-rose-300"
                                    >
                                        Excluir
                                    </button>
                                    <span
                                        v-else
                                        title="Categoria em uso por lançamentos"
                                        class="font-semibold text-gray-400 cursor-not-allowed"
                                    >
                                        Excluir
                                    </span>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
