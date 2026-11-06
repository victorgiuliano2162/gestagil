<script setup lang="ts">
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

interface CategoryReport {
    type: 'income' | 'expense';
    category_name: string;
    total_cents: number;
    count: number;
}

interface PaymentMethodReport {
    payment_method: string;
    total_cents: number;
    count: number;
}

interface RevenueData {
    income_cents: number;
    expense_cents: number;
    net_cents: number;
    by_category: CategoryReport[];
    by_payment_method: PaymentMethodReport[];
}

interface AppointmentsData {
    total_appointments: number;
    in_progress_count: number;
    completed_count: number;
    canceled_count: number;
    expected_total_cents: number;
}

interface TopClient {
    id: number;
    name: string;
    phone_e164: string | null;
    total_revenue_cents: number;
    transactions_count: number;
}

interface ClientsData {
    top_clients: TopClient[];
}

interface AnnualMonth {
    month: number;
    name: string;
    income_cents: number;
    expense_cents: number;
    net_cents: number;
}

interface AnnualData {
    year: number;
    months: AnnualMonth[];
    total_income_cents: number;
    total_expense_cents: number;
    total_net_cents: number;
}

const props = defineProps<{
    activeTab: 'faturamento' | 'atendimentos' | 'clientes' | 'anual';
    filters: {
        start_date: string;
        end_date: string;
        year: number;
    };
    report: any;
}>();

const startDate = ref(props.filters.start_date);
const endDate = ref(props.filters.end_date);
const selectedYear = ref(props.filters.year);

const switchTab = (tab: string) => {
    router.get(
        route('reports.index'),
        {
            tab,
            start_date: startDate.value,
            end_date: endDate.value,
            year: selectedYear.value,
        },
        { preserveState: true }
    );
};

const applyDateFilter = () => {
    router.get(
        route('reports.index'),
        {
            tab: props.activeTab,
            start_date: startDate.value,
            end_date: endDate.value,
            year: selectedYear.value,
        },
        { preserveState: true }
    );
};

const formatCurrency = (cents: number): string => {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(cents / 100);
};

const maxAnnualAmount = () => {
    if (props.activeTab !== 'anual' || !props.report.months) return 1;
    const maxVal = Math.max(
        ...props.report.months.map((m: AnnualMonth) => Math.max(m.income_cents, m.expense_cents)),
        1000
    );
    return maxVal;
};
</script>

<template>
    <Head title="Relatórios Financeiros e Operacionais - GestÁgil" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <div>
                    <h2 class="text-xl font-bold leading-tight text-gray-900">
                        Relatórios e Indicadores Comerciais
                    </h2>
                    <p class="text-sm text-gray-500 mt-0.5">
                        Acompanhe o faturamento, atendimentos, melhores clientes e desempenho anual
                    </p>
                </div>

                <!-- Filtros de Período -->
                <div class="flex items-center gap-2">
                    <template v-if="activeTab === 'anual'">
                        <select
                            v-model="selectedYear"
                            @change="applyDateFilter"
                            class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        >
                            <option :value="2024">Ano 2024</option>
                            <option :value="2025">Ano 2025</option>
                            <option :value="2026">Ano 2026</option>
                            <option :value="2027">Ano 2027</option>
                        </select>
                    </template>
                    <template v-else>
                        <input
                            v-model="startDate"
                            type="date"
                            class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <span class="text-gray-400 text-xs">até</span>
                        <input
                            v-model="endDate"
                            type="date"
                            class="rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500"
                        />
                        <button
                            @click="applyDateFilter"
                            class="px-3 py-2 bg-indigo-600 text-white text-xs font-semibold rounded-lg hover:bg-indigo-500 transition"
                        >
                            Filtrar
                        </button>
                    </template>
                </div>
            </div>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-7xl sm:px-6 lg:px-8 space-y-6">
                <!-- Abas de Navegação do Relatório -->
                <div class="border-b border-gray-200">
                    <nav class="-mb-px flex space-x-6">
                        <button
                            @click="switchTab('faturamento')"
                            class="whitespace-nowrap py-3 px-1 border-b-2 font-semibold text-sm transition"
                            :class="activeTab === 'faturamento' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        >
                            💰 Faturamento & Caixa
                        </button>
                        <button
                            @click="switchTab('atendimentos')"
                            class="whitespace-nowrap py-3 px-1 border-b-2 font-semibold text-sm transition"
                            :class="activeTab === 'atendimentos' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        >
                            📅 Atendimentos & Serviços
                        </button>
                        <button
                            @click="switchTab('clientes')"
                            class="whitespace-nowrap py-3 px-1 border-b-2 font-semibold text-sm transition"
                            :class="activeTab === 'clientes' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        >
                            👥 Melhores Clientes
                        </button>
                        <button
                            @click="switchTab('anual')"
                            class="whitespace-nowrap py-3 px-1 border-b-2 font-semibold text-sm transition"
                            :class="activeTab === 'anual' ? 'border-indigo-600 text-indigo-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                        >
                            📊 Comparativo Anual
                        </button>
                    </nav>
                </div>

                <!-- ABA 1: FATURAMENTO & CAIXA -->
                <div v-if="activeTab === 'faturamento'" class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                            <span class="text-xs font-semibold text-emerald-700 uppercase">Receita Bruta (Entradas)</span>
                            <div class="text-2xl font-black text-emerald-600 mt-1">
                                {{ formatCurrency((report as RevenueData).income_cents) }}
                            </div>
                            <span class="text-xs text-gray-400">Total recebido no período</span>
                        </div>

                        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                            <span class="text-xs font-semibold text-rose-700 uppercase">Despesas Operacionais</span>
                            <div class="text-2xl font-black text-rose-600 mt-1">
                                {{ formatCurrency((report as RevenueData).expense_cents) }}
                            </div>
                            <span class="text-xs text-gray-400">Custos e compras no período</span>
                        </div>

                        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                            <span class="text-xs font-semibold text-indigo-700 uppercase">Resultado Líquido</span>
                            <div class="text-2xl font-black mt-1"
                                :class="(report as RevenueData).net_cents >= 0 ? 'text-indigo-600' : 'text-rose-600'">
                                {{ formatCurrency((report as RevenueData).net_cents) }}
                            </div>
                            <span class="text-xs text-gray-400">Lucro apurado</span>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        <!-- Distribuição por Categoria -->
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <h3 class="text-base font-semibold text-gray-900 border-b pb-4 mb-4">
                                Lançamentos por Categoria
                            </h3>
                            <div v-if="(report as RevenueData).by_category.length === 0" class="text-center py-8 text-sm text-gray-500">
                                Nenhum lançamento no período filtrado.
                            </div>
                            <div v-else class="space-y-3">
                                <div v-for="(cat, idx) in (report as RevenueData).by_category" :key="idx" class="flex items-center justify-between p-2 rounded hover:bg-gray-50 text-sm">
                                    <div class="flex items-center space-x-2">
                                        <span class="h-2.5 w-2.5 rounded-full" :class="cat.type === 'income' ? 'bg-emerald-500' : 'bg-rose-500'"></span>
                                        <span class="font-medium text-gray-800">{{ cat.category_name }}</span>
                                        <span class="text-xs text-gray-400">({{ cat.count }})</span>
                                    </div>
                                    <span class="font-bold" :class="cat.type === 'income' ? 'text-emerald-600' : 'text-rose-600'">
                                        {{ formatCurrency(cat.total_cents) }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Entradas por Meio de Pagamento -->
                        <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                            <h3 class="text-base font-semibold text-gray-900 border-b pb-4 mb-4">
                                Recebimentos por Forma de Pagamento
                            </h3>
                            <div v-if="(report as RevenueData).by_payment_method.length === 0" class="text-center py-8 text-sm text-gray-500">
                                Nenhum recebimento no período filtrado.
                            </div>
                            <div v-else class="space-y-3">
                                <div v-for="(m, idx) in (report as RevenueData).by_payment_method" :key="idx" class="flex items-center justify-between p-2 rounded hover:bg-gray-50 text-sm">
                                    <div class="flex items-center space-x-2">
                                        <span class="font-bold text-xs uppercase px-2 py-0.5 rounded bg-gray-100 text-gray-700">
                                            {{ m.payment_method }}
                                        </span>
                                        <span class="text-xs text-gray-400">({{ m.count }} recebimentos)</span>
                                    </div>
                                    <span class="font-bold text-emerald-600">
                                        {{ formatCurrency(m.total_cents) }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ABA 2: ATENDIMENTOS & SERVIÇOS -->
                <div v-else-if="activeTab === 'atendimentos'" class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-4 gap-5">
                        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                            <span class="text-xs font-semibold text-gray-500 uppercase">Total Atendimentos</span>
                            <div class="text-3xl font-black text-indigo-600 mt-1">
                                {{ (report as AppointmentsData).total_appointments }}
                            </div>
                            <span class="text-xs text-gray-400">Ordens cadastradas</span>
                        </div>

                        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                            <span class="text-xs font-semibold text-emerald-700 uppercase">Concluídos</span>
                            <div class="text-3xl font-black text-emerald-600 mt-1">
                                {{ (report as AppointmentsData).completed_count }}
                            </div>
                            <span class="text-xs text-gray-400">Serviços entregues</span>
                        </div>

                        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                            <span class="text-xs font-semibold text-amber-700 uppercase">Em Andamento</span>
                            <div class="text-3xl font-black text-amber-600 mt-1">
                                {{ (report as AppointmentsData).in_progress_count }}
                            </div>
                            <span class="text-xs text-gray-400">Em execução na loja</span>
                        </div>

                        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                            <span class="text-xs font-semibold text-rose-700 uppercase">Cancelados</span>
                            <div class="text-3xl font-black text-rose-600 mt-1">
                                {{ (report as AppointmentsData).canceled_count }}
                            </div>
                            <span class="text-xs text-gray-400">Ordens canceladas</span>
                        </div>
                    </div>

                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-semibold text-gray-900">Volume Total de Atendimentos Orçado</h4>
                            <p class="text-xs text-gray-500">Soma dos valores previstos dos serviços no período</p>
                        </div>
                        <div class="text-2xl font-black text-indigo-600">
                            {{ formatCurrency((report as AppointmentsData).expected_total_cents) }}
                        </div>
                    </div>
                </div>

                <!-- ABA 3: MELHORES CLIENTES -->
                <div v-else-if="activeTab === 'clientes'" class="space-y-6">
                    <div class="bg-white shadow-sm rounded-xl border border-gray-100 overflow-hidden">
                        <div class="p-6 border-b border-gray-100">
                            <h3 class="text-base font-semibold text-gray-900">Ranking Top 10 Clientes por Faturamento</h3>
                            <p class="text-xs text-gray-500 mt-0.5">Identifique os clientes mais lucrativos e frequentes da sua loja</p>
                        </div>

                        <div v-if="(report as ClientsData).top_clients.length === 0" class="p-12 text-center text-sm text-gray-500">
                            Nenhuma movimentação vinculada a clientes no período.
                        </div>

                        <div v-else class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200 text-left text-sm">
                                <thead class="bg-gray-50 text-gray-500 uppercase text-xs">
                                    <tr>
                                        <th class="px-6 py-3 font-semibold">Posição</th>
                                        <th class="px-6 py-3 font-semibold">Cliente</th>
                                        <th class="px-6 py-3 font-semibold">Telefone</th>
                                        <th class="px-6 py-3 font-semibold text-center">Transações</th>
                                        <th class="px-6 py-3 font-semibold text-right">Total Comprado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <tr v-for="(client, index) in (report as ClientsData).top_clients" :key="client.id" class="hover:bg-gray-50">
                                        <td class="px-6 py-4 font-bold text-gray-500 whitespace-nowrap">
                                            <span class="inline-flex h-6 w-6 items-center justify-center rounded-full text-xs"
                                                :class="index === 0 ? 'bg-amber-100 text-amber-800 font-black' : 'bg-gray-100 text-gray-700'">
                                                {{ index + 1 }}º
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 font-semibold text-gray-900 whitespace-nowrap">
                                            <Link :href="route('clients.show', client.id)" class="hover:text-indigo-600">
                                                {{ client.name }}
                                            </Link>
                                        </td>
                                        <td class="px-6 py-4 text-gray-500 whitespace-nowrap">
                                            {{ client.phone_e164 || '-' }}
                                        </td>
                                        <td class="px-6 py-4 text-center font-bold text-gray-700 whitespace-nowrap">
                                            {{ client.transactions_count }}
                                        </td>
                                        <td class="px-6 py-4 text-right font-black text-emerald-600 whitespace-nowrap">
                                            {{ formatCurrency(client.total_revenue_cents) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- ABA 4: COMPARATIVO ANUAL -->
                <div v-else-if="activeTab === 'anual'" class="space-y-6">
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
                        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                            <span class="text-xs font-semibold text-emerald-700 uppercase">Receita Acumulada no Ano</span>
                            <div class="text-2xl font-black text-emerald-600 mt-1">
                                {{ formatCurrency((report as AnnualData).total_income_cents) }}
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                            <span class="text-xs font-semibold text-rose-700 uppercase">Despesas Acumuladas no Ano</span>
                            <div class="text-2xl font-black text-rose-600 mt-1">
                                {{ formatCurrency((report as AnnualData).total_expense_cents) }}
                            </div>
                        </div>

                        <div class="bg-white p-6 rounded-xl border border-gray-100 shadow-sm">
                            <span class="text-xs font-semibold text-indigo-700 uppercase">Resultado Anual Líquido</span>
                            <div class="text-2xl font-black mt-1"
                                :class="(report as AnnualData).total_net_cents >= 0 ? 'text-indigo-600' : 'text-rose-600'">
                                {{ formatCurrency((report as AnnualData).total_net_cents) }}
                            </div>
                        </div>
                    </div>

                    <!-- Gráfico Visual de Barras SVG -->
                    <div class="bg-white p-6 rounded-xl shadow-sm border border-gray-100">
                        <h3 class="text-base font-semibold text-gray-900 border-b pb-4 mb-4">
                            Evolução Mês a Mês ({{ (report as AnnualData).year }})
                        </h3>
                        <div class="grid grid-cols-12 gap-2 pt-8 pb-4 items-end h-64 border-b border-gray-100">
                            <div
                                v-for="m in (report as AnnualData).months"
                                :key="m.month"
                                class="flex flex-col items-center h-full justify-end group"
                            >
                                <div class="w-full flex justify-center space-x-1 items-end h-48">
                                    <!-- Barra Receitas -->
                                    <div
                                        class="w-3 bg-emerald-500 rounded-t transition-all duration-300"
                                        :style="{ height: Math.max((m.income_cents / maxAnnualAmount()) * 100, 4) + '%' }"
                                        :title="`Receitas: ${formatCurrency(m.income_cents)}`"
                                    ></div>
                                    <!-- Barra Despesas -->
                                    <div
                                        class="w-3 bg-rose-400 rounded-t transition-all duration-300"
                                        :style="{ height: Math.max((m.expense_cents / maxAnnualAmount()) * 100, 4) + '%' }"
                                        :title="`Despesas: ${formatCurrency(m.expense_cents)}`"
                                    ></div>
                                </div>
                                <span class="text-xs text-gray-500 mt-2 font-medium capitalize">{{ m.name }}</span>
                            </div>
                        </div>
                        <div class="flex justify-center space-x-6 mt-4 text-xs font-medium">
                            <div class="flex items-center space-x-2">
                                <span class="h-3 w-3 bg-emerald-500 rounded"></span>
                                <span>Entradas / Receitas</span>
                            </div>
                            <div class="flex items-center space-x-2">
                                <span class="h-3 w-3 bg-rose-400 rounded"></span>
                                <span>Saídas / Despesas</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
