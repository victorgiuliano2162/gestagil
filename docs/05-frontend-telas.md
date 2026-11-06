# 05 — Frontend (Inertia + Vue): Rotas, Telas e Componentes

> **Versão:** 0.3 (revisão enxuta: Inertia + SQLite) · **Data:** 06/11/2026 · Depende de: `02-requisitos.md`, `04-backend-rotas.md`
> Versões de pacotes: fixar as estáveis vigentes no Sprint 0. O starter kit Vue do Laravel define a base (Inertia, Vue 3, TypeScript, Tailwind, shadcn-vue): **usar o que ele traz antes de adicionar bibliotecas**.
> Perfis: **V** = Visitante, **P** = Prestador. As páginas são componentes Vue renderizados pelos controllers (`Inertia::render`); não há Vue Router nem chamadas a uma API REST.

## 1. Stack do frontend

| Necessidade | Escolha | Observação |
| :--- | :--- | :--- |
| Framework | Vue 3 (Composition API, `<script setup>`) + TypeScript | Do starter kit |
| Ponte com o servidor | Inertia.js (`@inertiajs/vue3`), sem SSR | Navegação sem recarregar a página; props vindas do controller |
| Build/dev | Vite (plugin do Laravel) | Em dev, servidor Vite com HMR (container `vite`); em produção, assets compilados na imagem |
| Roteamento | Rotas do Laravel + helper de rotas do kit (tipado, conforme a versão do kit) | Sem Vue Router |
| Estado de servidor | Props do Inertia (`usePage`, *deferred props*, *partial reloads*) | Substitui TanStack Query |
| Formulários | `useForm` do Inertia | Estado, erros por campo, `processing`, `isDirty`; validação real no servidor (Form Requests) |
| Estado global | Props compartilhadas (`auth.user`, *flash*) | Pinia **não** é usada; entra só se surgir necessidade real |
| UI | Tailwind CSS + shadcn-vue (Reka UI) | Do kit (**PV-19 resolvido**) |
| Ícones / toasts | `lucide-vue-next` / `vue-sonner` | Conferir o que o kit já inclui |
| Gráficos | Chart.js + `vue-chartjs` | Carregado sob demanda (Dashboard e Relatórios) |
| Datas | `date-fns` (locale `pt-BR`) | Datas locais como string `YYYY-MM-DD` |
| Máscaras | `maska` | Telefone, CPF/CNPJ, CEP |
| Utilitários | `@vueuse/core` | Debounce, online/offline, media queries |
| Testes | Pest (`assertInertia`) e testes de navegador do Pest/Playwright; Vitest só para funções puras (`money`, `dates`, `useMoneyInput`) | Ver `08` |
| Qualidade | ESLint + Prettier + `vue-tsc` | Do kit |

Removidos em relação à v0.1: Vue Router, Pinia (uso amplo), TanStack Query, Axios, vee-validate, Zod, `libphonenumber-js` como validador obrigatório.

## 2. Estrutura de pastas

```
resources/js/
├── app.ts                 inicialização do Inertia, título da página
├── pages/                 uma pasta por área (espelha os controllers)
│   ├── auth/              login, cadastro, recuperação (do kit)
│   ├── dashboard/         Index.vue
│   ├── appointments/      Index.vue · Form.vue · Show.vue
│   ├── transactions/      Index.vue · Form.vue
│   ├── clients/           Index.vue · Form.vue · Show.vue
│   ├── receipts/          Index.vue · Create.vue · Show.vue
│   ├── reports/           Index.vue (abas)
│   ├── settings/          Business.vue · Account.vue · Categories.vue
│   ├── legal/             Terms.vue · PrivacyPolicy.vue
│   ├── public/            ReceiptVerification.vue
│   └── Error.vue
├── layouts/               AuthLayout · AppLayout · SettingsLayout · PublicLayout
├── components/
│   ├── ui/                componentes base do shadcn-vue
│   └── app/               DataList, FilterBar, PeriodChips, MoneyInput, ClientCombobox, StatusBadge…
├── composables/           useMoneyInput, useFlashToast, useShareReceipt
├── lib/                   money.ts, dates.ts, phone.ts, labels.ts (enums → pt-BR)
└── types/                 tipos das props por página
```

Cada página recebe suas props tipadas (`defineProps<…>()`); o mapeamento *enum → rótulo pt-BR* fica centralizado em `lib/labels.ts`.

## 3. Mapa de rotas

Todas as rotas são do Laravel (ver `04` §5). "Componente" indica a página Vue renderizada.

| URL | Tela | Layout | Acesso | Componente / observação |
| :--- | :--- | :--- | :--- | :--- |
| `/login` | S-01 Login | Auth | V | `auth/Login` · redireciona logado para `/dashboard` |
| `/register` | S-02 Cadastro | Auth | V | `auth/Register` |
| `/forgot-password` | S-03 Esqueci a senha | Auth | V | `auth/ForgotPassword` |
| `/reset-password/{token}` | S-04 Redefinir senha | Auth | V | `auth/ResetPassword` |
| `/` | Redireciona para `/dashboard` | — | P | |
| `/dashboard` | S-06 Dashboard | App | P | `dashboard/Index` |
| `/atendimentos` | S-07 Lista | App | P | `appointments/Index` |
| `/atendimentos/novo` · `/atendimentos/{id}/editar` | S-08 Formulário | App | P | `appointments/Form` |
| `/atendimentos/{id}` | S-09 Detalhe | App | P | `appointments/Show` |
| `/caixa` | S-10 Fluxo de caixa | App | P | `transactions/Index` |
| `/caixa/novo?tipo=&atendimento=` · `/caixa/{id}/editar` | S-11 Lançamento | App | P | `transactions/Form` |
| `/clientes` | S-12 Lista | App | P | `clients/Index` |
| `/clientes/novo` · `/clientes/{id}/editar` | S-13 Formulário | App | P | `clients/Form` |
| `/clientes/{id}` | S-14 Detalhe | App | P | `clients/Show` |
| `/recibos` | S-15 Lista | App | P | `receipts/Index` |
| `/recibos/novo?lancamento={id}` | S-16 Emitir | App | P | `receipts/Create` · exige perfil completo |
| `/recibos/{id}` | S-17 Detalhe | App | P | `receipts/Show` |
| `/relatorios/{aba?}` | S-18 Relatórios (`faturamento`, `atendimentos`, `clientes`, `anual`) | App | P | `reports/Index` |
| `/configuracoes` | Redireciona para `/configuracoes/negocio` | App | P | |
| `/configuracoes/negocio` | S-19 Negócio | App (Settings) | P | `settings/Business` |
| `/configuracoes/conta` | S-20 Conta e Privacidade (LGPD) | App (Settings) | P | `settings/Account` |
| `/configuracoes/categorias` | S-21 Categorias | App (Settings) | P | `settings/Categories` |
| `/termos` · `/privacidade` | S-23 Termos e Política | Public | V, P | `legal/*` |
| `/r/{code}` | S-24 Verificação e download de recibo | Public | V | `public/ReceiptVerification` |
| Erros do servidor | S-25 Erros | Public | Todos | `Error.vue` |

**Proteções (middlewares, no servidor):** `auth` (sem sessão → `/login`, com retorno ao destino), `guest` (com sessão → `/dashboard`), `EnsureBusinessProfile` (perfil incompleto → alerta com CTA para S-19, apenas nas rotas de emissão). Filtros de listas ficam na **query string** (compartilháveis, restauráveis com o botão voltar).

## 4. Layouts

| Layout | Uso | Estrutura |
| :--- | :--- | :--- |
| **AuthLayout** | Login, cadastro, recuperação | Cartão centralizado, logotipo, links secundários; sem navegação |
| **AppLayout** | Telas autenticadas | **Mobile:** cabeçalho compacto + **barra inferior** (Início, Atendimentos, Caixa, Recibos, Mais) + botão flutuante **"+"** (Nova receita, Nova despesa, Novo atendimento). **Desktop (≥ 1024 px):** menu lateral fixo, conteúdo com largura máxima, botão "+" no cabeçalho. Menu "Mais": Clientes, Relatórios, Configurações, Sair |
| **SettingsLayout** | Configurações | Dentro do AppLayout, com navegação secundária (Negócio, Conta, Categorias, Privacidade) — vem do kit e é adaptada |
| **PublicLayout** | Termos, política, verificação de recibo, erros | Cabeçalho mínimo + rodapé com links legais |

Os layouts persistentes do Inertia mantêm o menu e o estado da barra entre navegações.

## 5. Estado e tratamento de respostas

| Tipo | Ferramenta | Conteúdo |
| :--- | :--- | :--- |
| Sessão | Props compartilhadas (`HandleInertiaRequests`) | `auth.user`, `profileComplete`, versão dos termos |
| Mensagens | Props *flash* + `vue-sonner` | Sucesso/erro após redirecionamentos; composable `useFlashToast` |
| Servidor | Props de cada página | Listas, detalhes, relatórios; `Inertia::defer` para blocos pesados; `router.reload({ only: [...] })` para atualizar partes |
| URL | Query string via `router.get` | Filtros, página, aba, ordenação (`preserveState`) |
| Formulário | `useForm` | Valores, erros por campo, `processing`, `isDirty`; composable `useUnsavedChanges` avisa ao sair com alterações |
| Local efêmero | `ref`/`reactive` | Modais, seleção, estado visual |

**Tratamento de respostas (Inertia):** sessão expirada (`419`) → o servidor redireciona ao login com aviso; `422` → erros mapeados aos campos e foco no primeiro; `429` → página de erro/toast com o tempo de espera; `5xx` ou falha de rede → `router.on('exception')` mostra toast com "Tentar novamente" e o identificador da requisição em "detalhes técnicos"; respostas não-Inertia inesperadas → `router.on('invalid')`.

## 6. Componentes compartilhados

| Componente | Função |
| :--- | :--- |
| `PageHeader` | Título, ações primárias, *breadcrumb* simples |
| `DataList` | Tabela no desktop / cartões no mobile; skeleton, vazio e erro embutidos |
| `FilterBar` / `PeriodChips` | Hoje · Semana · Mês · Personalizado; sincroniza com a URL |
| `MoneyInput` / `MoneyText` | Entrada `inputmode="decimal"` em reais ↔ centavos; exibição BRL |
| `PhoneInput`, `DocumentInput` | Máscara (`maska`); validação no servidor |
| `ClientCombobox` / `AppointmentCombobox` | Seleção rápida com filtro local sobre a lista de clientes/atendimentos enviada nas props; ação "Novo cliente" (painel lateral) |
| `StatusBadge` | Atendimento (em andamento/concluído/cancelado) e recibo (emitido/cancelado) |
| `PaymentMethodPicker` | Botões grandes: Pix · Dinheiro · Cartão · Outro |
| `KpiCard`, `ChartCard` | Indicadores e gráficos com estados de carregamento/erro |
| `EmptyState`, `ErrorState`, `SkeletonBlock` | Estados padronizados (skeleton enquanto `<Deferred>` carrega) |
| `ConfirmDialog` | Confirmação de ações destrutivas com texto claro |
| `QuickAddFab`, `BottomNav`, `SideNav` | Navegação e entrada rápida |
| `OfflineBanner` | Aviso "Sem conexão — é necessário internet" (TAP §6) |

## 7. Telas

> Cada tela traz: objetivo, rota, perfis, componentes principais, estados, rotas do backend acionadas (ver `04`), validações e tecnologias específicas (as globais estão na seção 1). **Validações:** a fonte da verdade é o FormRequest do servidor (`04` §6); os erros voltam por campo no `useForm`. Quando a coluna cita regras, elas descrevem o que o usuário vê.

### S-01 Login

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Autenticar o prestador e retornar à rota pretendida |
| Rota | `/login` |
| Perfis | V |
| Componentes | `AuthLayout`, `LoginForm` (e-mail, senha com alternar visibilidade, "manter conectado"), links "Esqueci a senha" e "Criar conta" |
| Estados | Inicial · erros inline · enviando (botão desabilitado + spinner) · credenciais inválidas (mensagem genérica) · `429` (aguarde N s) · e-mail não verificado (**PV-16**) · erro de rede ou sessão expirada (419 → volta ao login com aviso) |
| Rotas (backend) | `GET /login` → `POST /login` (redireciona ao destino pretendido) |
| Validações | E-mail obrigatório e válido; senha obrigatória |
| Tecnologias | Inertia `useForm`, componentes do starter kit (shadcn-vue), *throttle* do Laravel |

### S-02 Cadastro

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Criar conta e iniciar o onboarding |
| Rota | `/register` |
| Perfis | V |
| Componentes | `RegisterForm` (nome, e-mail, senha, confirmação, aceite de termos com links), indicador de requisitos de senha |
| Estados | Inicial · validação inline · enviando · e-mail já cadastrado (`422` no campo) · sucesso → redireciona a `/configuracoes/negocio` (ou `/verify-email`) |
| Rotas (backend) | `GET /register` → `POST /register` |
| Validações | Nome 2–120; e-mail válido; senha ≥ 8 e confirmação igual; aceite obrigatório |
| Tecnologias | Inertia `useForm`, componentes do starter kit |

### S-03 Esqueci a senha

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Solicitar link de redefinição por e-mail |
| Rota | `/forgot-password` |
| Perfis | V |
| Componentes | Formulário de e-mail, mensagem de confirmação neutra ("se o e-mail existir, enviaremos…") |
| Estados | Inicial · enviando · confirmação · `429` · erro de rede |
| Rotas (backend) | `GET /forgot-password` → `POST /forgot-password` |
| Validações | E-mail obrigatório e válido |
| Tecnologias | Inertia `useForm` |

### S-04 Redefinir senha

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Definir nova senha a partir do link recebido |
| Rota | `/reset-password/{token}?email=` |
| Perfis | V |
| Componentes | Formulário (nova senha, confirmação), aviso de token inválido/expirado com botão para solicitar novo |
| Estados | Inicial · enviando · sucesso (toast + redireciona ao login) · token inválido/expirado |
| Rotas (backend) | `GET /reset-password/{token}` → `POST /reset-password` |
| Validações | Senha ≥ 8; confirmação igual |
| Tecnologias | Inertia `useForm` |

### S-06 Dashboard

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Visão rápida do negócio e atalhos de registro |
| Rota | `/dashboard` |
| Perfis | P |
| Componentes | `PeriodChips` (Hoje/Semana/Mês), 3 `KpiCard` (Receitas, Despesas, Resultado), `ChartCard` (barras por dia), lista dos últimos atendimentos e dos "em andamento", atalhos (Nova receita/despesa/atendimento), checklist de onboarding (perfil do negócio, primeiro cliente) |
| Estados | Carregando · sucesso · vazio (CTA de primeiro uso) · erro |
| Rotas (backend) | `GET /dashboard?periodo=` |
| Validações | `periodo` ∈ {dia, semana, mes} (fallback para `semana`) |
| Tecnologias | `vue-chartjs` (import dinâmico), `Intl.NumberFormat('pt-BR')` |

### S-07 Atendimentos — lista

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Consultar e filtrar atendimentos; agir rapidamente sobre o status |
| Rota | `/atendimentos?status=&cliente=&de=&ate=&q=&page=` |
| Perfis | P |
| Componentes | `PageHeader` (+ "Novo atendimento"), `FilterBar` (chips de status, período, `ClientCombobox`, busca com debounce de 300 ms), `DataList` (cliente, descrição resumida, data/hora, `StatusBadge`, ação rápida "Concluir"), paginação |
| Estados | Carregando · vazio sem filtros (CTA) · vazio com filtros ("Limpar filtros") · erro |
| Rotas (backend) | `GET /atendimentos` (filtros na URL) · `PATCH /atendimentos/{id}/status` |
| Validações | Parâmetros da URL validados no controller, com valores padrão em caso de inválidos |
| Tecnologias | `router.get` com `preserveState`/`preserveScroll`/`replace`, `useDebounceFn` (VueUse), `<Link>` na paginação |

### S-08 Atendimento — formulário (novo/editar)

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Registrar ou editar um atendimento |
| Rota | `/atendimentos/novo` · `/atendimentos/:id/editar` |
| Perfis | P |
| Componentes | `ClientCombobox` + drawer "Novo cliente" (inline), descrição do serviço, data/hora (padrão: agora), status (padrão: em andamento), anotações técnicas, valor previsto (**PV-03**, opcional); ações "Salvar" e "Salvar e registrar recebimento" |
| Estados | Novo · carregando edição · salvando · erro `422` por campo · `404` |
| Rotas (backend) | `GET /atendimentos/novo` · `GET /atendimentos/{id}/editar` · `POST /atendimentos` · `PUT /atendimentos/{id}` · `POST /clientes` (criação inline; o cliente novo volta em *flash* e é selecionado no combobox) |
| Validações | Cliente obrigatório; descrição 3–2.000; data/hora válida (**PV-02**); anotações ≤ 5.000; valor previsto ≥ 0 |
| Tecnologias | Inertia `useForm`, shadcn-vue (`Combobox`, `Sheet`, `Textarea`), `<input type="datetime-local">` |

### S-09 Atendimento — detalhe

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Acompanhar o atendimento e encadear o fluxo até o recibo |
| Rota | `/atendimentos/:id` |
| Perfis | P |
| Componentes | Cabeçalho (cliente com atalho WhatsApp, data/hora, `StatusBadge`), ações de status (Concluir · Cancelar com motivo · Reabrir, **PV-21**), abas *Detalhes / Lançamentos / Recibos*, botões "Registrar recebimento", "Emitir recibo" (por receita), Editar, Excluir |
| Estados | Carregando · `404` · mutação em andamento (botão desabilitado e indicador de progresso) · sem lançamentos · `409` (transição inválida / exclusão bloqueada) |
| Rotas (backend) | `GET /atendimentos/{id}` (props: lançamentos e recibos vinculados) · `PATCH /atendimentos/{id}/status` · `DELETE /atendimentos/{id}` |
| Validações | Motivo de cancelamento ≤ 500; regras de transição respondidas pelo servidor (mensagem exibida) |
| Tecnologias | shadcn-vue (`Tabs`, `AlertDialog`), `router.patch`/`router.delete`, link `https://wa.me/` |

### S-10 Fluxo de caixa — lista e resumo

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Ver entradas, saídas e saldo do período e acessar lançamentos |
| Rota | `/caixa?tipo=&de=&ate=&categoria=&forma=&cliente=&q=&page=` |
| Perfis | P |
| Componentes | `PeriodChips`, barra de resumo (Entradas · Saídas · Saldo), alternador Todos/Receitas/Despesas, filtros (categoria, forma), lista **agrupada por dia** com subtotal diário (ícone do tipo, descrição, categoria, forma de pagamento, valor colorido, selo "Recibo nº"), botões "+ Receita" e "+ Despesa", menu de linha (Editar, Excluir, Emitir recibo) |
| Estados | Carregando · vazio (CTA) · erro · exclusão em andamento · `409` (lançamento com recibo) |
| Rotas (backend) | `GET /caixa` (props: lista e resumo do período) · `DELETE /caixa/{id}` |
| Validações | `de ≤ ate`; intervalo ≤ 366 dias |
| Tecnologias | `router.get` com filtros na URL, `date-fns`, formatação BRL; cor **e** ícone/sinal para não depender só de cor |

### S-11 Lançamento — formulário

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Registrar/editar receita ou despesa com o mínimo de toques |
| Rota | `/caixa/novo?tipo=&atendimento=` · `/caixa/:id/editar` |
| Perfis | P |
| Componentes | Alternador Receita/Despesa, `MoneyInput` (foco automático), data (padrão: hoje), `PaymentMethodPicker`, seleção de categoria, descrição, `AppointmentCombobox` (somente receita; mostra cliente), `ClientCombobox` (opcional; bloqueado se houver atendimento), observações; ações "Salvar" e "Salvar e emitir recibo" (receita) |
| Estados | Novo · carregando edição · salvando · erro `422` · campos bloqueados por recibo emitido (aviso com link ao recibo) · pré-preenchido por atendimento |
| Rotas (backend) | `GET /caixa/novo` · `GET /caixa/{id}/editar` · `POST /caixa` (com `emitir_recibo=1` redireciona a S-16) · `PUT /caixa/{id}` (categorias, clientes e atendimentos vêm nas props) |
| Validações | Valor > 0 e ≤ limite; data ≤ hoje (**PV-23**); forma obrigatória; categoria compatível com o tipo; atendimento não cancelado; descrição obrigatória ≤ 255 |
| Tecnologias | Composable `useMoneyInput` (Intl + `inputmode="decimal"`), Inertia `useForm`, shadcn-vue (`ToggleGroup`, `Sheet`) |

### S-12 Clientes — lista

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Localizar e gerenciar clientes |
| Rota | `/clientes?q=&page=` |
| Perfis | P |
| Componentes | Busca (nome/telefone), `DataList` (nome, telefone com atalho WhatsApp, nº de atendimentos, último atendimento), "Novo cliente", menu de linha (Ver, Editar, Excluir) |
| Estados | Carregando · vazio (CTA) · sem resultados · erro · `409` na exclusão |
| Rotas (backend) | `GET /clientes?q=&page=` · `DELETE /clientes/{id}` |
| Validações | Termo de busca ≤ 100 |
| Tecnologias | `router.get` (`preserveState`), `useDebounceFn` |

### S-13 Cliente — formulário

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Criar ou editar cliente |
| Rota | `/clientes/novo` · `/clientes/:id/editar` |
| Perfis | P |
| Componentes | Nome, `PhoneInput` (WhatsApp), e-mail, `DocumentInput` (CPF/CNPJ), observações, texto de apoio LGPD ("usado somente para atendimento e recibos"), aviso de possível duplicidade por telefone (**PV-29**) |
| Estados | Novo · carregando · salvando · `422` · aviso de duplicidade · alterações não salvas |
| Rotas (backend) | `GET /clientes/novo` · `GET /clientes/{id}/editar` · `POST /clientes` · `PUT /clientes/{id}` |
| Validações | Nome 2–150; telefone BR válido (obrigatório); e-mail válido; CPF/CNPJ com dígitos verificadores; observações ≤ 2.000 |
| Tecnologias | `maska` (máscaras), Inertia `useForm`; a validação de telefone/CPF/CNPJ é do servidor |

### S-14 Cliente — detalhe

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Ver histórico e totais do cliente |
| Rota | `/clientes/:id` |
| Perfis | P |
| Componentes | Cabeçalho (contatos com botões WhatsApp/ligar/e-mail), `KpiCard` (atendimentos, total recebido, último atendimento), abas *Atendimentos / Recibos*, ações Editar/Excluir/Anonimizar (C) |
| Estados | Carregando · `404` · abas vazias · confirmação de anonimização (irreversível) |
| Rotas (backend) | `GET /clientes/{id}` (props: resumo, atendimentos e recibos) · `DELETE /clientes/{id}` · `POST /clientes/{id}/anonimizar` |
| Validações | Confirmação textual para anonimizar |
| Tecnologias | shadcn-vue (`Tabs`, `AlertDialog`), `router.post` |

### S-15 Recibos — lista

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Consultar recibos e abrir/baixar o PDF |
| Rota | `/recibos?status=&cliente=&de=&ate=&q=&page=` |
| Perfis | P |
| Componentes | Filtros (status, período, cliente, busca por número `NNNN/AAAA`), `DataList` (nº/ano, cliente/pagador, valor, data, `StatusBadge`), ações "Baixar PDF" e "Compartilhar" |
| Estados | Carregando · vazio (orienta emitir a partir de uma receita) · erro |
| Rotas (backend) | `GET /recibos?…` · `GET /recibos/{id}/pdf` |
| Validações | Parâmetros de URL validados no controller (valores padrão se inválidos) |
| Tecnologias | `router.get` com filtros na URL; download por link direto (`<a>`), sem passar pelo Inertia |

### S-16 Recibo — emitir

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Revisar os dados e emitir o recibo de uma receita |
| Rota | `/recibos/novo?lancamento=:id` |
| Perfis | P |
| Componentes | `ReceiptPreviewCard` (prévia em HTML do documento), campos editáveis (nome/documento do pagador quando não há cliente; descrição), alerta de perfil incompleto com CTA para S-19, botão "Emitir recibo" |
| Estados | Carregando · perfil incompleto (bloqueia emissão) · recibo já existente (redireciona ao detalhe) · emitindo · sucesso (vai ao detalhe) · erro |
| Rotas (backend) | `GET /recibos/novo?lancamento={id}` · `POST /recibos` |
| Validações | Lançamento deve ser receita; pagador 2–150; documento do pagador válido (opcional); descrição ≤ 500 |
| Tecnologias | Inertia `useForm` (`processing` bloqueia duplo clique), componente de prévia em Vue |

### S-17 Recibo — detalhe

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Ver, baixar, compartilhar, enviar por e-mail e cancelar o recibo |
| Rota | `/recibos/:id` |
| Perfis | P |
| Componentes | Prévia do recibo (HTML, número no formato `NNNN/AAAA`) e botão "Abrir PDF", ações: **Baixar PDF**, **Compartilhar no WhatsApp** (Web Share API com arquivo quando disponível; senão `wa.me` com link `/r/{code}`; senão copiar link), **Enviar por e-mail** (diálogo com e-mail do cliente pré-preenchido), **Cancelar recibo** (diálogo com motivo) |
| Estados | Carregando · `404` · enviando e-mail · cancelado (tarja e ações restritas) · compartilhamento indisponível no dispositivo (fallback) |
| Rotas (backend) | `GET /recibos/{id}` · `GET /recibos/{id}/pdf` · `POST /recibos/{id}/email` · `POST /recibos/{id}/cancelar` |
| Validações | E-mail de destino válido; motivo ≤ 500 |
| Tecnologias | `navigator.canShare/share` (mobile), Clipboard API, visualização do PDF por prévia HTML + link direto para `/recibos/{id}/pdf` |

### S-18 Relatórios

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Analisar faturamento, atendimentos e clientes recorrentes |
| Rota | `/relatorios/:aba?` (`faturamento` · `atendimentos` · `clientes` · `anual`) |
| Perfis | P |
| Componentes | Abas; seletor de período e agrupamento (dia/semana/mês); **Faturamento:** KPIs, gráfico de barras (receitas × despesas) com linha do resultado, tabela detalhada, quebras por categoria e forma de pagamento; **Atendimentos:** contagem por status + histórico; **Clientes:** ranking (atendimentos concluídos, total recebido, último atendimento); **Anual:** tabela de 12 meses e total; botão "Exportar CSV" (C) |
| Estados | Carregando · vazio ("sem dados no período") · erro · período longo (aviso de agrupamento) |
| Rotas (backend) | `GET /relatorios/{aba}` (+ `/relatorios/{aba}/exportar`, C) |
| Validações | `de ≤ ate`; intervalo ≤ 366 dias (dia ≤ 92); ano válido |
| Tecnologias | `vue-chartjs`, `router.get` com `only` para trocar aba/período, `date-fns`; gráfico acompanhado de tabela (acessibilidade) |

### S-19 Configurações — Negócio (emitente)

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Manter os dados que aparecem no cabeçalho dos recibos |
| Rota | `/configuracoes/negocio` |
| Perfis | P |
| Componentes | Formulário (nome/razão, nome fantasia, CPF/CNPJ, telefone, e-mail, endereço, rodapé do recibo), envio de logo com prévia, prévia do cabeçalho do recibo, indicador de completude |
| Estados | Carregando · salvando · `422` · logo enviando/erro · perfil completo/incompleto |
| Rotas (backend) | `GET /configuracoes/negocio` · `PUT /configuracoes/negocio` · `POST/DELETE /configuracoes/negocio/logo` · (ViaCEP no navegador — C) |
| Validações | Nome obrigatório para emitir; CPF/CNPJ válido (**PV-15**); telefone BR; UF; CEP 8 dígitos; logo PNG/JPG/WebP ≤ 1 MB, sem SVG |
| Tecnologias | `maska`, Inertia `useForm` com upload (`forceFormData`), `FileReader` para prévia |

### S-20 Configurações — Conta e Privacidade (LGPD)

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Editar nome/e-mail, alterar senha e permitir a exclusão completa da conta (LGPD) |
| Rota | `/configuracoes/conta` |
| Perfis | P |
| Componentes | Formulário de dados (nome, e-mail), formulário de senha (atual, nova, confirmação), seção "Privacidade e Exclusão de Conta" (data do aceite dos termos + botão "Excluir minha conta" com confirmação por senha) |
| Estados | Carregando · salvando · sucesso (toast) · `422` (senha atual incorreta) · confirmação de exclusão · excluindo |
| Rotas (backend) | `GET /configuracoes/conta` · `PUT /configuracoes/conta` · `PUT /configuracoes/senha` · `DELETE /configuracoes/conta` |
| Validações | Nome 2–120; e-mail válido único; nova senha ≥ 8; senha atual obrigatória para excluir conta |
| Tecnologias | Inertia `useForm` (base do starter kit), `AlertDialog` (shadcn-vue) |

### S-21 Configurações — Categorias

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Gerenciar categorias de receitas e despesas |
| Rota | `/configuracoes/categorias` |
| Perfis | P |
| Componentes | Duas listas (Receitas/Despesas), adicionar/renomear em linha, arquivar, alternar "mostrar arquivadas" |
| Estados | Carregando · vazio · salvando · `422` (nome duplicado) · confirmação de arquivamento |
| Rotas (backend) | `GET /configuracoes/categorias` · `POST /configuracoes/categorias` · `PUT/DELETE /configuracoes/categorias/{id}` |
| Validações | Nome 2–60, único por tipo |
| Tecnologias | shadcn-vue (`Dialog`, `Input`), Inertia `useForm` |

### S-23 Termos de uso e Política de privacidade

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Informar finalidade, dados coletados, direitos e contato (LGPD) |
| Rota | `/termos` · `/privacidade` |
| Perfis | V, P |
| Componentes | Texto estático versionado, índice lateral, data de vigência |
| Estados | Estático |
| Rotas (backend) | `GET /termos` · `GET /privacidade` |
| Validações | — |
| Tecnologias | Páginas Vue estáticas |

### S-24 Verificação e download de recibo

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Permitir que o cliente final confira a autenticidade e baixe o PDF pelo link enviado no WhatsApp |
| Rota | `/r/:code` |
| Perfis | V |
| Componentes | Cartão com status (válido/cancelado), número (`NNNN/AAAA`), emitente, valor e data, botão **"Baixar PDF"** (`/r/{code}/pdf`) |
| Estados | Carregando · válido · cancelado · não encontrado · limite de requisições (`429`) |
| Rotas (backend) | `GET /r/{code}` · `GET /r/{code}/pdf` |
| Validações | Formato UUID do código |
| Tecnologias | `PublicLayout`, página Inertia sem autenticação |

### S-25 Erros e conectividade

| Item | Detalhe |
| :--- | :--- |
| Objetivo | Tratar 403, 404, falhas inesperadas e ausência de rede |
| Rota | Páginas de erro geradas pelo servidor (403/404/419/429/500/503) · `OfflineBanner` |
| Perfis | Todos |
| Componentes | Páginas de erro com ação "Voltar/Ir ao início", banner offline, tela de erro fatal com `request_id` |
| Estados | 403 · 404 · erro inesperado · offline · retorno da conexão |
| Rotas (backend) | Nenhuma (o handler de exceções renderiza `Error.vue` com o identificador da requisição) |
| Validações | — |
| Tecnologias | Página de erro do Inertia, `router.on('exception')`, `app.config.errorHandler`, `useOnline` (VueUse), Sentry (opcional) |

## 8. Padrões de validação e feedback de UX

| Situação | Padrão |
| :--- | :--- |
| Validação | **Servidor é a fonte**: erros `422` aparecem por campo, com mensagens curtas em pt-BR e foco no primeiro erro. O navegador complementa com `required`, `inputmode`, máscaras e limites de tamanho (sem duplicar regras de negócio) |
| Envio | Botão desabilitado com spinner enquanto `form.processing`; proteção contra duplo clique; o Inertia preserva os valores digitados quando há erro |
| Sucesso | Toast breve (mensagem *flash*) + navegação lógica (ex.: salvar atendimento → detalhe; salvar lançamento → lista) |
| Erro recuperável | Toast/alerta com "Tentar novamente"; erro fatal mostra o identificador da requisição |
| Destrutivo | `ConfirmDialog` descrevendo a consequência; sem confirmação para ações reversíveis |
| Carregamento | Skeletons no lugar do conteúdo (`<Deferred>`); nunca tela em branco; listas mantêm dados anteriores durante nova busca (`preserveState`) |
| Vazio | Explica o motivo e oferece a próxima ação |
| Alterações não salvas | Confirmação ao sair de formulários alterados (`useUnsavedChanges`) |
| Mobile | Alvos de toque ≥ 44 px; teclado numérico para valores/telefone; ação principal ao alcance do polegar; formulários curtos |
| Acessibilidade | Rótulos associados, foco visível, contraste AA, `lang="pt-BR"`, navegação por teclado, gráficos com tabela equivalente, não depender somente de cor |
| Desempenho | Páginas carregadas sob demanda (divisão de código por página); Chart.js só nas telas com gráfico; `<Link prefetch>` opcional em itens de menu; logo do recibo com dimensões fixas |
| Textos | Linguagem simples, sem jargão contábil; valores sempre em R$; datas `dd/MM/yyyy` |
| PWA (C) | Manifesto e ícones para instalar na tela inicial; **sem** cache offline de dados (TAP §6) |

## 9. Pontos a validar

| PV | Assunto |
| :--- | :--- |
| PV-02 | Data/hora do atendimento: agendamento futuro ou registro do que foi feito |
| PV-03 | Valor previsto no atendimento |
| PV-16 | Verificação de e-mail (define S-05 e ajustes em S-01/S-20) |
| PV-17 | Exportação CSV/PDF e PWA instalável |
| PV-21 | Reabertura de atendimento concluído/cancelado |
| PV-23 | Lançamentos com data futura |
| PV-29 | Comportamento diante de telefone duplicado |
| PV-30 | Busca sem acento (clientes normalizados; atendimentos com limitação aceita?) |

*(PV-19 — UI kit — foi resolvido: shadcn-vue do starter kit.)*
