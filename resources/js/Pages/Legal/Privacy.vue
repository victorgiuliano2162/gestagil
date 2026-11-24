<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Política de Privacidade - GestÁgil" />

    <div class="min-h-screen bg-gray-50 dark:bg-gray-950 py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-900 p-8 sm:p-10 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
            <div class="flex items-center justify-between border-b border-gray-200 dark:border-gray-800 pb-6 mb-6">
                <Link href="/" class="flex items-center space-x-2">
                    <img src="/assets/gestagil-icon-removebg.png" alt="GestÁgil" class="h-9 w-9 object-contain" />
                    <span class="text-2xl font-black text-indigo-900 dark:text-indigo-300 tracking-tight">GestÁgil</span>
                </Link>
                <Link href="/" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                    &larr; Voltar ao Início
                </Link>
            </div>

            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">Política de Privacidade</h1>
            <p class="text-xs text-gray-400 mb-8">Conformidade com a LGPD (Lei nº 13.709/2018)</p>

            <div class="prose prose-sm text-gray-700 dark:text-gray-300 space-y-6">
                <section>
                    <h2 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-2">1. Coleta e Finalidade dos Dados</h2>
                    <p class="text-sm leading-relaxed">
                        Coletamos apenas as informações estritamente necessárias para a prestação do serviço: nome, e-mail e senha com criptografia forte para acesso à conta; dados cadastrais do estabelecimento comercial para cabeçalho dos recibos; e dados de clientes fornecidos voluntariamente pelo comerciante para controle de atendimento.
                    </p>
                </section>

                <section>
                    <h2 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-2">2. Não Compartilhamento com Terceiros</h2>
                    <p class="text-sm leading-relaxed">
                        Nenhum dado cadastral ou financeiro é vendido, compartilhado ou utilizado para fins de publicidade. Cada conta opera sob rigoroso isolamento multi-inquilino (*multi-tenant*), de forma que nenhum outro comerciante tem acesso às informações da sua loja.
                    </p>
                </section>

                <section>
                    <h2 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-2">3. Armazenamento e Backups Seguros</h2>
                    <p class="text-sm leading-relaxed">
                        Os dados são armazenados em ambiente seguro com backups criptografados automáticos diários. As imagens e logotipos passam por descarte de metadados EXIF e compressão local antes da gravação.
                    </p>
                </section>

                <section>
                    <h2 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-2">4. Direitos do Titular (Eliminação e Acesso)</h2>
                    <p class="text-sm leading-relaxed">
                        Você possui pleno direito de acessar, retificar e solicitar a exclusão irrevogável de todos os dados de sua conta a qualquer momento na opção "Excluir Conta", o que desencadeia a eliminação automática de todos os seus registros em cascata.
                    </p>
                </section>
            </div>

            <div class="mt-10 pt-6 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
                <span>© 2026 GestÁgil — Todos os direitos reservados.</span>
                <Link :href="route('terms')" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                    Ver Termos de Uso &rarr;
                </Link>
            </div>
        </div>
    </div>
</template>
