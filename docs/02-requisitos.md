# 02 — Requisitos

> **Versão:** 0.3 (revisão enxuta: Inertia + SQLite) · **Data:** 06/11/2026 · Depende de: `00-visao-geral.md`
> **Prioridade:** M = Must · S = Should · C = Could · W = Won't · **Origem:** TAP §n ou Proposto

## 1. Requisitos funcionais

### 1.1 Conta e autenticação (AUT)

| ID | Requisito | Prior. | Origem |
| :--- | :--- | :---: | :--- |
| RF-AUT-01 | Cadastro com nome, e-mail e senha, com aceite dos termos e da política de privacidade | M | Proposto (TAP não define acesso — **PV-01, PV-16**) |
| RF-AUT-02 | Login e logout com sessão segura; opção "manter conectado" | M | Proposto |
| RF-AUT-03 | Recuperação de senha por e-mail | S | Proposto |
| RF-AUT-04 | Verificação de e-mail no cadastro | W | Simplificado para o MVP (**PV-16**) |
| RF-AUT-05 | Editar nome/e-mail e alterar senha | S | Proposto |
| RF-AUT-06 | Excluir a própria conta e todos os dados (confirmação por senha) | S | Proposto (LGPD, TAP §8) |

### 1.2 Perfil do negócio (NEG)

| ID | Requisito | Prior. | Origem |
| :--- | :--- | :---: | :--- |
| RF-NEG-01 | Cadastrar dados do emitente: nome/razão, CPF/CNPJ, telefone, e-mail, endereço, logo | M | Proposto (pré-requisito do recibo — TAP §5) |
| RF-NEG-02 | Definir texto de rodapé/observação padrão do recibo | C | Proposto |

### 1.3 Clientes e atendimentos (CLI / ATD)

| ID | Requisito | Prior. | Origem |
| :--- | :--- | :---: | :--- |
| RF-CLI-01 | Cadastrar cliente com nome e telefone/WhatsApp | M | TAP §5 |
| RF-CLI-02 | Campos opcionais do cliente: e-mail, CPF/CNPJ, observações | S | Proposto (necessários para e-mail e recibo) |
| RF-CLI-03 | Listar, buscar (nome/telefone) e editar clientes | M | Proposto |
| RF-CLI-04 | Excluir cliente (exclusão lógica; preservado no histórico se houver vínculos) | S | Proposto |
| RF-CLI-05 | Ver histórico do cliente: atendimentos, recibos e total recebido | S | Proposto |
| RF-ATD-01 | Registrar atendimento: cliente, descrição do serviço, data/hora, status | M | TAP §5 |
| RF-ATD-02 | Registrar anotações técnicas do atendimento | M | TAP §5 |
| RF-ATD-03 | Alterar status: em andamento, concluído, cancelado (com regras de transição) | M | TAP §5 |
| RF-ATD-04 | Listar e filtrar atendimentos por status, período, cliente e texto | M | Proposto |
| RF-ATD-05 | Editar atendimento; excluir apenas se não houver lançamentos/recibos vinculados | S | Proposto |
| RF-ATD-06 | Criar cliente sem sair da tela de atendimento | S | Proposto |
| RF-ATD-07 | Ver lançamentos e recibos vinculados ao atendimento | S | Proposto |
| RF-ATD-08 | Informar valor previsto do atendimento | C | Proposto (**PV-03**) |

### 1.4 Fluxo de caixa (CX)

| ID | Requisito | Prior. | Origem |
| :--- | :--- | :---: | :--- |
| RF-CX-01 | Registrar receita (valor, data, forma de pagamento, descrição, categoria), com vínculo opcional a atendimento | M | TAP §5 |
| RF-CX-02 | Registrar despesa operacional (ex.: combustível, peças/insumos, ferramentas) | M | TAP §5 |
| RF-CX-03 | Formas de pagamento: Pix, dinheiro, cartão (+ "Outro") | M | TAP §5 (**PV-07**) |
| RF-CX-04 | Listar e filtrar lançamentos por período, tipo, categoria, forma de pagamento e cliente | M | Proposto |
| RF-CX-05 | Resumo do período: entradas, saídas e saldo | M | Proposto |
| RF-CX-06 | Editar e excluir lançamento (regras quando há recibo) | M | Proposto (**PV-22**) |
| RF-CX-07 | Categorias padrão (semeadas) e categorias personalizadas | S (padrão: M) | Proposto |
| RF-CX-08 | Entrada rápida de lançamento a partir de qualquer tela (≤ 3 toques até o formulário) | S | Proposto |

### 1.5 Comprovantes (REC)

| ID | Requisito | Prior. | Origem |
| :--- | :--- | :---: | :--- |
| RF-REC-01 | Emitir recibo a partir de um lançamento de receita | M | TAP §5 |
| RF-REC-02 | PDF padronizado: emitente, pagador, valor numérico e por extenso, forma de pagamento, descrição, data, número (`NNNN/AAAA`) e código | M | TAP §5 |
| RF-REC-03 | Baixar o PDF do recibo (gerado sob demanda a partir do snapshot) | M | TAP §5 |
| RF-REC-04 | Compartilhar via WhatsApp (Web Share API / `wa.me` + link público `/r/{code}`) | M | TAP §5 (**PV-05**) |
| RF-REC-05 | Enviar por e-mail com PDF anexo | S | TAP §5 |
| RF-REC-06 | Numeração sequencial por usuário/ano (`NNNN/AAAA`), sem reuso | M | Proposto |
| RF-REC-07 | Listar e buscar recibos; abrir/baixar PDF | M | Proposto |
| RF-REC-08 | Cancelar recibo mantendo o registro (marca "CANCELADO" no PDF) | S | Proposto |
| RF-REC-10 | Página pública de verificação e download do recibo por código (`/r/{code}`) | S | Proposto |

### 1.6 Relatórios e dashboard (REL)

| ID | Requisito | Prior. | Origem |
| :--- | :--- | :---: | :--- |
| RF-REL-01 | Dashboard com receitas, despesas e resultado do dia/semana/mês e atalhos rápidos | M | TAP §5 |
| RF-REL-02 | Relatório de faturamento líquido por dia/semana/mês com gráfico | M | TAP §5 |
| RF-REL-03 | Histórico de atendimentos por período (contagem por status) | M | TAP §5 |
| RF-REL-04 | Ranking de clientes mais recorrentes | M | TAP §5 (**PV-09**) |
| RF-REL-05 | Faturamento anual mensalizado (apoio à declaração) | S | TAP §1 (benefícios) |

### 1.7 Transversais (TRV)

| ID | Requisito | Prior. | Origem |
| :--- | :--- | :---: | :--- |
| RF-TRV-01 | Interface responsiva (a partir de 360 px) | M | TAP §2 |
| RF-TRV-02 | Termos de uso e política de privacidade acessíveis publicamente | M | TAP §8 (LGPD) |
| RF-TRV-03 | Estados vazios, de carregamento e de erro em todas as telas | M | Proposto |
| RF-TRV-04 | Logs de eventos sensíveis em `stderr` (login, exclusão, cancelamento, emissão) | S | Proposto |

## 2. Requisitos não funcionais

| ID | Categoria | Requisito | Meta/Métrica | Origem |
| :--- | :--- | :--- | :--- | :--- |
| RNF-01 | Usabilidade | Mobile-first, funcional de 360 a 1440 px; ações frequentes ao alcance do polegar | Fluxos F2 e F3 concluídos em ≤ 3 telas | TAP §2 + Proposto |
| RNF-02 | Usabilidade | Sucesso nas tarefas dos testes de usabilidade | **> 90%** das tentativas (protocolo em `08`) | TAP §2 |
| RNF-03 | Desempenho | Telas de leitura e relatórios "ágeis" (tempo de resposta do servidor) | p95 ≤ 500 ms (leituras); p95 ≤ 1,5 s (relatórios com até 5.000 lançamentos/usuário) | Proposto (**PV-26**) |
| RNF-04 | Desempenho | Emissão de recibo com PDF; carregamento inicial | p95 ≤ 5 s; LCP ≤ 2,5 s em 4G | Proposto |
| RNF-05 | Disponibilidade | Melhor esforço (sem SLA), com reinício automático e healthchecks | ≥ 95% durante a avaliação | Proposto |
| RNF-06 | Segurança | Sessão por cookie `HttpOnly`/`Secure`/`SameSite`, CSRF, rate limiting, hash de senha forte, proteção OWASP Top 10 | Checklist em `08` sem itens críticos abertos | Proposto |
| RNF-07 | Privacidade/LGPD | Minimização de dados, finalidade, exclusão/anonimização, TLS, acesso restrito, logs sem PII | Requisitos RN-16 atendidos | TAP §8 |
| RNF-08 | Compatibilidade | Duas últimas versões principais de Chrome, Edge, Firefox e Safari (desktop e mobile) | Teste manual + Playwright (Chromium/WebKit/Firefox) | TAP §10 (Risco 02) |
| RNF-09 | Acessibilidade | Alvo WCAG 2.1 AA nos fluxos principais (contraste, foco, rótulos, teclado) | Sem violações críticas no axe | Proposto |
| RNF-10 | Portabilidade | 100% em containers; ambiente novo sobe com poucos comandos | `docker compose up` funcional em ≤ 10 min | Instrução do projeto |
| RNF-11 | Manutenibilidade | Lint, análise estática, tipos e testes automatizados | Cobertura ≥ 70% no código de domínio (backend) | Proposto |
| RNF-12 | Observabilidade | Logs estruturados, `request_id`, healthchecks e captura de erros | Erros 5xx rastreáveis a um `request_id` | Proposto |
| RNF-13 | Recuperação | Backup diário do SQLite (`VACUUM INTO`) e dos logos; cópia off-site criptografada; restauração testada | RPO ≤ 24 h; RTO ≤ 4 h | Proposto (**PV-25**) |
| RNF-14 | Custo | Nenhum custo financeiro direto | R$ 0,00 | TAP §8 |
| RNF-15 | Capacidade | Dimensionamento para uso acadêmico/piloto | Até 100 usuários cadastrados e ~10 simultâneos | Proposto (**PV-28**) |
| RNF-16 | Localização | pt-BR, BRL, fuso America/Recife, formatos brasileiros | 100% das telas | Proposto |
| RNF-17 | Integridade | Dinheiro em centavos; operações compostas em transação de banco | Sem arredondamentos incorretos | Proposto |
| RNF-18 | Documentação | Rotas, telas e runbooks (deploy, restauração) documentados; artefatos PMBOK atualizados | Documentação revisada a cada sprint (DoD) | TAP §5 + Proposto |
| RNF-19 | Concorrência | Escritas simultâneas no SQLite não geram erro ao usuário nem numeração duplicada de recibos | Teste com emissões simultâneas sem duplicidade; `busy_timeout` configurado | Proposto |

## 3. Regras de negócio

| ID | Regra |
| :--- | :--- |
| RN-01 | **Isolamento:** cada usuário só enxerga e altera seus próprios dados. Acesso a recurso de outro usuário retorna 404. |
| RN-02 | **Valores monetários:** inteiros em centavos, > 0, moeda BRL; limite máximo por lançamento de R$ 9.999.999,99 (proposta). |
| RN-03 | **Data do lançamento:** não pode ser futura no MVP, pois todo lançamento é considerado realizado (**PV-06, PV-23**). |
| RN-04 | **Transições de status do atendimento:** `em andamento → concluído`; `em andamento → cancelado`. Reabrir `concluído`/`cancelado` para `em andamento` é permitido? **PV-21** (proposta: permitido, com registro em auditoria). |
| RN-05 | **Cancelar atendimento:** motivo recomendado (opcional, até 500 caracteres). Registra `canceled_at`. Atendimento cancelado não recebe novas receitas; lançamentos já existentes permanecem. |
| RN-06 | **Exclusão de cliente:** exclusão lógica. Cliente com atendimentos/recibos permanece no histórico; o cliente deixa de aparecer nas buscas de novos cadastros. |
| RN-07 | **Herança de cliente:** receita vinculada a atendimento herda o cliente do atendimento (não pode divergir). Receita avulsa pode ter cliente opcional. |
| RN-08 | **Recibo:** só para receita; no máximo **1 recibo ativo por receita** (**PV-04**); requer perfil do negócio completo (RN-18). O pagador é o cliente vinculado ou um nome informado na emissão. |
| RN-09 | **Cancelamento de recibo:** mantém o registro e o PDF com marca "CANCELADO"; o número não é reutilizado; a receita permanece; permite emitir novo recibo (novo número). |
| RN-10 | **Conteúdo do recibo:** emitente (perfil), pagador, valor em algarismos e por extenso, forma de pagamento, descrição do serviço, data, número/ano, código de verificação e aviso de documento não fiscal (**PV-15**). |
| RN-11 | **Faturamento líquido** = Σ receitas − Σ despesas, por `occurred_on` (regime de caixa) (**PV-08**). |
| RN-12 | **Cliente recorrente:** cliente com ≥ 2 atendimentos *concluídos* no período; ranking por quantidade e desempate por total recebido (**PV-09**). |
| RN-13 | **Períodos:** dia = data local; semana = segunda a domingo (ISO); mês = calendário; fuso America/Recife (**PV-10**). |
| RN-14 | **Relatórios:** atendimentos cancelados não entram na receita; aparecem nas contagens de histórico por status. |
| RN-15 | **Verificação e download público do recibo:** acessível via `/r/{code}` (UUID v4 imprevisível) e `/r/{code}/pdf`, com limite de requisições por IP (30/min) (**PV-20**). |
| RN-16 | **LGPD:** coleta mínima; o prestador é o controlador dos dados dos clientes e a plataforma atua como operador; ciência dos termos registrada no cadastro; exclusão de conta remove todos os dados do usuário em ordem reversa de dependência (**PV-14**). |
| RN-17 | **Sessão e senha:** senha ≥ 8 caracteres; sessão expira por inatividade (120 min; "manter conectado" 30 dias); limite de tentativas de login. |
| RN-18 | **Perfil do negócio completo:** nome/razão e CPF/CNPJ obrigatórios para emitir recibos (**PV-15**). |
| RN-19 | **Numeração de recibos:** sequencial por usuário e ano, exibida no formato **`NNNN/AAAA`** (ex.: `0001/2026`), gerada de forma atômica (`BEGIN IMMEDIATE` no SQLite + índice único `(user_id, year, number)`); nunca reutilizada. |
| RN-20 | **Categorias:** pertencem a um tipo (receita/despesa); nome único por tipo e usuário; categoria em uso é arquivada, não excluída. |
| RN-21 | **Telefone:** armazenado em formato E.164 (+55…), validado para Brasil. |
| RN-22 | **Lançamento com recibo emitido:** valor, data, forma de pagamento, tipo e descrição ficam bloqueados; para alterar, cancelar o recibo antes. Exclusão bloqueada (**PV-22**). |

## 4. Perfis e permissões

### 4.1 Perfis

| Perfil | Descrição | Autenticado |
| :--- | :--- | :---: |
| **Visitante** | Acessa apenas telas públicas (login, cadastro, recuperação, termos) | Não |
| **Prestador** | Dono da conta; opera todos os módulos sobre os próprios dados | Sim |
| **Cliente final** | Sem conta; acessa a verificação e o PDF do recibo via link `/r/{code}` | Não (link UUID) |

### 4.2 Matriz de permissões

Legenda: ✔ permitido · ✖ negado · (P) somente os próprios dados.

| Recurso / ação | Visitante | Prestador | Cliente final |
| :--- | :---: | :---: | :---: |
| Cadastrar-se, entrar, recuperar senha | ✔ | — | — |
| Ler termos e política de privacidade | ✔ | ✔ | ✔ |
| Editar conta, perfil do negócio e excluir conta | ✖ | ✔ (P) | ✖ |
| CRUD de clientes | ✖ | ✔ (P) | ✖ |
| CRUD de atendimentos e mudança de status | ✖ | ✔ (P) | ✖ |
| CRUD de lançamentos e categorias | ✖ | ✔ (P) | ✖ |
| Emitir, listar, cancelar recibos | ✖ | ✔ (P) | ✖ |
| Baixar PDF do recibo (autenticado) | ✖ | ✔ (P) | ✖ |
| Verificar e baixar PDF por link `/r/{code}` | ✔ (com UUID) | ✔ | ✔ (com UUID) |
| Ver dashboard e relatórios | ✖ | ✔ (P) | ✖ |

**Implementação:** Policies por modelo e *global scope* por `user_id`. Sem pacote de permissões e sem painel Admin no MVP. Detalhes em `04`.

## 5. Rastreabilidade resumida (módulo → telas → rotas)

| Módulo | Telas (`05`) | Grupos de rotas (`04`) |
| :--- | :--- | :--- |
| AUT / NEG | S-01 a S-04, S-19, S-20 | `/login`, `/register`, `/forgot-password`, `/configuracoes/*` |
| CLI | S-12, S-13, S-14 | `/clientes` |
| ATD | S-07, S-08, S-09 | `/atendimentos` |
| CX | S-10, S-11, S-21 | `/caixa`, `/configuracoes/categorias` |
| REC | S-15, S-16, S-17, S-24 | `/recibos`, `/r/{code}` |
| REL | S-06, S-18 | `/dashboard`, `/relatorios/*` |
