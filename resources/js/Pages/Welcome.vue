<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';

defineProps<{
    canLogin?: boolean;
    canRegister?: boolean;
}>();
</script>

<template>
    <Head title="GestÁgil - Gestão Ágil e Simples para seu Comércio" />

    <div class="min-h-screen bg-gradient-to-b from-gray-50 to-white text-gray-800">
        <!-- Navegação Superior -->
        <header class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6 flex items-center justify-between border-b border-gray-100">
            <div class="flex items-center space-x-3">
                <img src="/images/logo.png" alt="GestÁgil Logo" class="h-10 sm:h-12 w-auto object-contain" />
            </div>

            <nav v-if="canLogin" class="flex items-center space-x-4">
                <Link
                    v-if="$page.props.auth.user"
                    :href="route('dashboard')"
                    class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                >
                    Acessar Painel &rarr;
                </Link>

                <template v-else>
                    <Link
                        :href="route('login')"
                        class="text-sm font-semibold text-gray-700 hover:text-indigo-600"
                    >
                        Entrar
                    </Link>

                    <Link
                        v-if="canRegister"
                        :href="route('register')"
                        class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500"
                    >
                        Criar Conta Grátis
                    </Link>
                </template>
            </nav>
        </header>

        <!-- Seção Hero Principal -->
        <main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 pt-16 pb-24 text-center">
            <div class="inline-flex items-center gap-2 rounded-full bg-indigo-50 px-3 py-1 text-xs font-semibold text-indigo-700 mb-8 border border-indigo-100">
                <span>🚀 Feito para o Comerciante e Lojista Local</span>
            </div>

            <h1 class="text-4xl font-extrabold tracking-tight text-gray-900 sm:text-6xl max-w-4xl mx-auto leading-tight">
                Controle suas vendas, clientes e caixa com <span class="text-indigo-600">agilidade total</span>.
            </h1>

            <p class="mt-6 text-lg text-gray-600 max-w-2xl mx-auto leading-relaxed">
                Chega de cadernos rasgados e sistemas lentos. O <strong>GestÁgil</strong> foi desenhado para quem atende no balcão: rápido, direto e sem complicação.
            </p>

            <div class="mt-10 flex flex-col sm:flex-row items-center justify-center gap-4">
                <Link
                    :href="route('register')"
                    class="w-full sm:w-auto rounded-lg bg-indigo-600 px-8 py-3.5 text-base font-bold text-white shadow-lg hover:bg-indigo-500 transition shadow-indigo-200"
                >
                    Começar Agora
                </Link>
                <Link
                    :href="route('login')"
                    class="w-full sm:w-auto rounded-lg bg-white px-8 py-3.5 text-base font-bold text-gray-700 shadow-sm border border-gray-200 hover:bg-gray-50 transition"
                >
                    Já tenho uma conta
                </Link>
            </div>

            <!-- Destaques das Funcionalidades -->
            <div class="mt-20 grid grid-cols-1 md:grid-cols-3 gap-8 text-left">
                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition">
                    <div class="h-12 w-12 rounded-xl bg-indigo-50 flex items-center justify-center text-2xl mb-4">
                        👥
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Carteira de Clientes</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Cadastre contatos no balcão em segundos, com atalho direto para conversar e enviar orçamentos no WhatsApp.
                    </p>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition">
                    <div class="h-12 w-12 rounded-xl bg-emerald-50 flex items-center justify-center text-2xl mb-4">
                        🏪
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Marca da sua Loja</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Personalize seus recibos com o nome fantasia, logotipo e CNPJ/CPF do seu comércio, passando mais credibilidade.
                    </p>
                </div>

                <div class="bg-white p-6 rounded-2xl border border-gray-100 shadow-sm hover:shadow-md transition">
                    <div class="h-12 w-12 rounded-xl bg-rose-50 flex items-center justify-center text-2xl mb-4">
                        📊
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Fluxo de Caixa Simples</h3>
                    <p class="mt-2 text-sm text-gray-500">
                        Saiba com exatidão suas entradas diárias e pagamentos a fornecedores. Sem relatórios contábeis difíceis.
                    </p>
                </div>
            </div>
        </main>

        <!-- Rodapé -->
        <footer class="border-t border-gray-200 bg-white py-8">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 flex flex-col sm:flex-row items-center justify-between text-xs text-gray-500 gap-4">
                <div class="flex items-center space-x-2">
                    <img src="/images/icon.png" alt="GestÁgil Ícone" class="h-5 w-auto" />
                    <span>GestÁgil &copy; 2026. Todos os direitos reservados.</span>
                </div>
                <div class="flex space-x-6">
                    <Link :href="route('terms')" class="hover:underline">Termos de Uso e Privacidade</Link>
                </div>
            </div>
        </footer>
    </div>
</template>
