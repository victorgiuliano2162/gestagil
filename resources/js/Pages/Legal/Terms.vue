<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Termos de Uso - GestÁgil" />

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

            <h1 class="text-2xl font-bold text-gray-900 dark:text-gray-100 mb-2">Termos de Uso do GestÁgil</h1>
            <p class="text-xs text-gray-400 mb-8">Última atualização: Novembro de 2026</p>

            <div class="prose prose-sm text-gray-700 dark:text-gray-300 space-y-6">
                <section>
                    <h2 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-2">1. Objeto e Natureza da Aplicação</h2>
                    <p class="text-sm leading-relaxed">
                        O GestÁgil é um sistema de apoio à gestão para microempreendedores individuais (MEI) e autônomos, oferecendo ferramentas para cadastro de clientes, agendamento de serviços, controle simplificado de fluxo de caixa e emissão de recibos comerciais digitais.
                    </p>
                </section>

                <section>
                    <h2 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-2">2. Responsabilidade pelas Informações</h2>
                    <p class="text-sm leading-relaxed">
                        O usuário comerciante é o único responsável pela veracidade dos dados inseridos, incluindo preços, descrições de serviços prestados e dados de contato de seus respectivos clientes.
                    </p>
                </section>

                <section>
                    <h2 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-2">3. Emissão e Cancelamento de Recibos</h2>
                    <p class="text-sm leading-relaxed">
                        Os recibos gerados pela plataforma possuem numeração sequencial atômica anual e chave digital de autenticidade. Caso um recibo seja cancelado, o histórico do documento é mantido com a devida sinalização para garantia de conformidade contábil e auditoria.
                    </p>
                </section>

                <section>
                    <h2 class="text-base font-bold text-gray-900 dark:text-gray-100 mb-2">4. Exclusão e Privacidade dos Dados (LGPD)</h2>
                    <p class="text-sm leading-relaxed">
                        Em respeito à Lei Geral de Proteção de Dados (Lei nº 13.709/2018), o usuário pode solicitar ou executar a exclusão total de sua conta a qualquer momento na aba de perfil, efetuando o expurgo definitivo de todos os seus registros de clientes e histórico comercial.
                    </p>
                </section>
            </div>

            <div class="mt-10 pt-6 border-t border-gray-100 dark:border-gray-800 flex justify-between items-center text-xs text-gray-500 dark:text-gray-400">
                <span>© 2026 GestÁgil — Todos os direitos reservados.</span>
                <Link :href="route('privacy')" class="text-indigo-600 dark:text-indigo-400 hover:underline">
                    Ver Política de Privacidade &rarr;
                </Link>
            </div>
        </div>
    </div>
</template>
