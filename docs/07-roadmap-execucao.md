# 07 — Roadmap de Execução

> **Versão:** 0.3 (revisão enxuta para execução rápida) · **Data:** 06/11/2026 · Depende de: `00` a `06`
> **Premissa de calendário:** O planejamento documental (F0) foi concluído e o início prático da codificação (Sprint 0 / Sprint 1) ocorre a partir de **06/11/2026**. Com as simplificações adotadas na v0.3 (container único, 7 tabelas de domínio, sessão em `file`, PDF sob demanda, combobox via props), o esforço de desenvolvimento cabe com folga na janela restante do semestre.

## 1. Capacidade disponível

TAP: 12 h/semana × 12 semanas = **144 h**. O TAP reserva as semanas 6–9 (48 h) para prototipagem/desenvolvimento, mas as semanas 1–5 também precisam abrigar o ambiente técnico, e as semanas 10–12 concentram testes, monitoramento e encerramento.

| Semana | Período (estimado) | Foco (TAP §11) | Horas totais | Dev/infra | Gestão/documentação |
| :---: | :--- | :--- | :---: | :---: | :---: |
| 1 | 28/09–04/10 | TAP, escopo, EAP | 12 | 0 | 12 |
| 2 | 05/10–11/10 | TAP, escopo, EAP | 12 | 0 | 12 |
| 3 | 12/10–18/10 | Cronograma, custos, riscos | 12 | 0 | 12 |
| 4 | 19/10–25/10 | Cronograma, custos, riscos + **Sprint 0** | 12 | 4 | 8 |
| 5 | 26/10–01/11 | Cronograma, custos, riscos + **Sprint 0** | 12 | 8 | 4 |
| 6 | 02/11–08/11 | Desenvolvimento — **Sprint 1** | 12 | 10 | 2 |
| 7 | 09/11–15/11 | Desenvolvimento — **Sprint 2** | 12 | 10 | 2 |
| 8 | 16/11–22/11 | Desenvolvimento — **Sprint 3** | 12 | 10 | 2 |
| 9 | 23/11–29/11 | Desenvolvimento — **Sprint 4** | 12 | 10 | 2 |
| 10 | 30/11–06/12 | Testes + monitoramento/controle — **Sprint 5** | 12 | 6 | 6 |
| 11 | 07/12–13/12 | Testes + consolidação — **Sprint 6** | 12 | 4 | 8 |
| 12 | 14/12–20/12 | Apresentação e encerramento | 12 | 0 | 12 |
| **Total** | | | **144** | **62** | **82** |

**Leitura crítica:** o escopo técnico completo precisa caber em ~**62 h** de desenvolvimento/infra, e a janela nominal do TAP para desenvolvimento (semanas 6–9) tem apenas 48 h. Recomenda-se levar ao orientador o **início do Sprint 0 nas semanas 4–5** e a extensão parcial do desenvolvimento até a semana 11 (**PV-13**). Não há folga explícita: qualquer imprevisto (trabalho, TCC, família — TAP, justificativa) consome o escopo *Should/Could*.

**Efeito da revisão de stack (Inertia + SQLite):** a autenticação vem pronta do starter kit, não há contrato de API nem segunda camada de validação, e a infraestrutura caiu de ~10 serviços para 3. A economia estimada é de **8 a 12 h** dos ~62 h (a confirmar no fim do Sprint 0). Recomenda-se **não gastar essa economia em escopo novo**: ela é a folga que o cronograma não tinha, e cobre parte do custo dos novos itens de backup e de concorrência no SQLite.

## 2. Visão das fases e sprints

| Fase | Sprint | Semanas | Objetivo | Horas dev/infra (estim.) |
| :--- | :--- | :---: | :--- | :---: |
| F0 Planejamento | — | 1–3 | TAP, EAP, escopo, riscos iniciais; validação dos PVs críticos | 0 |
| F1 Fundação técnica | **Sprint 0** | 4–5 | Imagem Docker única, projeto Laravel com starter kit Vue, SQLite (WAL), CI, `/up`, `backup:run` básico | 12 |
| F2 Núcleo de acesso e clientes | **Sprint 1** | 6 | Personalização da autenticação do kit, layouts, clientes | 10 |
| F3 Atendimentos | **Sprint 2** | 7 | CRUD, status, detalhe, integração com clientes | 10 |
| F4 Fluxo de caixa | **Sprint 3** | 8 | Categorias, lançamentos, resumo, vínculo com atendimento | 10 |
| F5 Recibos | **Sprint 4** | 9 | Perfil do negócio, emissão, PDF, compartilhamento | 10 |
| F6 Dashboard e relatórios | **Sprint 5** | 10 | Dashboard, relatórios, teste de usabilidade rodada 1 | 6 |
| F7 Estabilização e entrega | **Sprint 6** | 11 | Correções, LGPD mínima, deploy de produção, usabilidade rodada 2 | 4 |
| F8 Encerramento | — | 12 | Apresentação e Relatório Final | 0 |

## 3. Detalhamento por fase

### F0 — Planejamento (semanas 1–3)

| Item | Descrição |
| :--- | :--- |
| Entregáveis | TAP aprovado; EAP; escopo priorizado (MoSCoW); lista de PVs enviada ao orientador; primeira versão da matriz de riscos |
| Dependências | Assinatura do orientador no TAP |
| **Critérios de aceite** | ☐ TAP assinado ☐ PV-01, PV-04, PV-05, PV-13 respondidos ou com decisão provisória registrada ☐ EAP cobre módulos M1–M4, fundação e documentação ☐ Plano de comunicação define cadência de reporte |

### F1 — Fundação técnica / Sprint 0 (~8 h)

| Item | Descrição |
| :--- | :--- |
| Entregáveis | Repositório único; `Dockerfile` multi-stage e `compose.yaml` + override (dev) funcionando com serviço `app`; projeto Laravel criado com o **starter kit Vue** (Inertia); SQLite em volume com WAL, `busy_timeout=5000`, `transaction_mode=IMMEDIATE`, chaves estrangeiras ativas e função `unaccent()`; `SESSION_DRIVER=file` e `CACHE_STORE=file`; Mailpit; `/up`; pipeline de CI enxuto (Pint + `vue-tsc` + Pest); `Makefile`; comando `backup:run` (`VACUUM INTO` + `gzip`) |
| **Critérios de aceite** | ☐ `docker compose up -d` sobe `app`, `vite`, `mailpit` saudáveis ☐ Cadastro e login do kit funcionam em `http://localhost:8080` ☐ Teste automatizado confirma `journal_mode=wal` e chaves estrangeiras ativas ☐ `make test` e `make lint` passam ☐ `backup:run` gera um `.sqlite.gz` válido (`integrity_check` ok) |

### F2 — Núcleo de acesso e clientes / Sprint 1 (~8 h)

| Item | Descrição |
| :--- | :--- |
| Entregáveis | Migrations iniciais (`users`, `business_profiles`, `transaction_categories`, `clients`); personalização do cadastro/login/recuperação do kit (aceite de termos, semeadura de categorias padrão e criação de `business_profile` vazio em transação, textos em pt-BR); `AppLayout`/`AuthLayout` adaptados; CRUD de clientes (S-12, S-13, S-14) com `search_name`; exclusão de conta em ordem topológica reversa (S-20); termos e política (S-23) |
| Dependências | Sprint 0 |
| **Critérios de aceite** | ☐ Cadastro cria usuário, perfil vazio e categorias padrão sem erro ☐ Rotas protegidas redirecionam corretamente ☐ Usuário B não acessa clientes do usuário A (teste IDOR) ☐ Validações de telefone/CPF/CNPJ no servidor ☐ Busca de cliente encontra "joao" para "João" ☐ Exclusão de conta apaga todos os registros sem violar FKs `RESTRICT` ☐ Telas responsivas em 360 px |

### F3 — Atendimentos / Sprint 2 (~8 h)

| Item | Descrição |
| :--- | :--- |
| Entregáveis | Migration e CRUD de atendimentos; transições de status (RN-04); lista com filtros e URL sincronizada (S-07); formulário com seleção de cliente via prop e criação inline (S-08); detalhe com ações (S-09) |
| Dependências | Clientes (Sprint 1) |
| **Critérios de aceite** | ☐ Status muda apenas por transições válidas ☐ Cancelamento registra motivo e data ☐ Filtros por status/período/cliente/texto (usando `unaccent`) funcionam ☐ Exclusão bloqueada com vínculos ☐ Testes de Policy e transições |

### F4 — Fluxo de caixa / Sprint 3 (~8 h)

| Item | Descrição |
| :--- | :--- |
| Entregáveis | Gestão de categorias (S-21); migration e CRUD de lançamentos (S-10, S-11); resumo do período; vínculo receita ↔ atendimento com herança de cliente (RN-07); botão flutuante "+" |
| Dependências | Atendimentos (Sprint 2) |
| **Critérios de aceite** | ☐ Valores em centavos sem erro de arredondamento ☐ Resumo (entradas/saídas/saldo) confere com dados de teste ☐ Formulário de lançamento em ≤ 3 toques a partir do botão "+" ☐ Receita vinculada herda o cliente ☐ **Teste de usabilidade informal** com 1–2 prestadores |

### F5 — Recibos / Sprint 4 (~8 h)

| Item | Descrição |
| :--- | :--- |
| Entregáveis | Tela de perfil do negócio (S-19); emissão de recibo com snapshot imutável e numeração `NNNN/AAAA` serializada via `BEGIN IMMEDIATE` + `MAX(number) + 1`; geração de PDF sob demanda (DomPDF) com valor por extenso; compartilhamento WhatsApp (Web Share/`wa.me` + link público `/r/{code}`); lista, detalhe e verificação pública (S-15, S-16, S-17, S-24); cancelamento; envio por e-mail |
| Dependências | Lançamentos; perfil do negócio |
| **Critérios de aceite** | ☐ 1 recibo ativo por receita (restrição no banco + teste) ☐ Numeração sequencial `NNNN/AAAA` sem duplicidade em emissões concorrentes ☐ PDF renderizado sob demanda corretamente (com tarja "CANCELADO" quando cancelado) ☐ Valor por extenso validado por testes unitários ☐ Compartilhamento e página `/r/{code}` funcionam no celular e desktop |

### F6 — Dashboard e relatórios / Sprint 5 (~6 h)

| Item | Descrição |
| :--- | :--- |
| Entregáveis | Dashboard (S-06); relatórios de faturamento, histórico de atendimentos, clientes recorrentes e anual (S-18); consultas SQLite com `strftime` cobertas por testes; **teste de usabilidade oficial — rodada 1** |
| Dependências | Dados de lançamentos/atendimentos |
| **Critérios de aceite** | ☐ Números dos relatórios conferem com massa de dados conhecida ☐ Tempo dos relatórios dentro da meta (RNF-03) ☐ Gráficos acompanhados de tabela ☐ Rodada 1 de usabilidade realizada e registrada |

### F7 — Estabilização e entrega / Sprint 6 (~4 h dev + gestão)

| Item | Descrição |
| :--- | :--- |
| Entregáveis | Correções priorizadas da usabilidade; deploy de produção com HTTPS; validação de backup e restauração (`backup:run` + `restore.sh`); rodada 2 de usabilidade; consolidação dos documentos PMBOK |
| **Critérios de aceite** | ☐ Aplicação acessível por HTTPS em produção ☐ Backup e restauração testados ☐ Meta de usabilidade medida (≥ 28 de 30 tentativas, ver `08` §6) ☐ Checklist de segurança de `08` sem itens críticos ☐ Nenhum bug bloqueante aberto |

### F8 — Encerramento (semana 12)

| Item | Descrição |
| :--- | :--- |
| Entregáveis | Apresentação final; demonstração ao vivo do fluxo F2; Relatório Final de Encerramento (lições aprendidas, métricas SMART); documentação atualizada |
| **Critérios de aceite** | ☐ 100% dos módulos do TAP demonstrados (com desvios documentados) ☐ Métricas SMART reportadas com evidências ☐ Relatório de encerramento entregue |

## 4. Ordem de implementação e dependências

```
Sprint 0 (infra, health, CI)
   └─► Auth + layouts ──► Clientes ──► Atendimentos ──► Lançamentos (Caixa) ──► Recibos ──► Dashboard/Relatórios
                                              │                  ▲                  ▲
                                              └── (vínculo) ─────┘                  │
                             Perfil do negócio ──────────────────────────────────────┘
   Transversais contínuos: auditoria · testes de isolamento (IDOR) · lint/tipos · backup e restauração · documentação PMBOK
```

| Dependência crítica | Motivo |
| :--- | :--- |
| Auth antes de qualquer módulo | Todo recurso é escopado por `user_id` |
| Clientes antes de Atendimentos | `client_id` obrigatório |
| Atendimentos antes de Lançamentos vinculados | Herança de cliente (RN-07) |
| Lançamentos antes de Recibos | Recibo nasce de uma receita |
| Perfil do negócio antes de emitir recibo | RN-18 |
| Dados de teste antes de Relatórios | Validar agregações e desempenho |
| Hospedagem (com disco persistente) e SMTP definidos antes do Sprint 4/6 | Envio por e-mail, deploy e banco SQLite |

## 5. Marcos

| Marco | Descrição | Semana (fim) | Data estimada |
| :--- | :--- | :---: | :--- |
| M1 | TAP aprovado; PVs críticos respondidos | 3 | 18/10/2026 |
| M2 | Ambiente Docker + CI verdes; login do kit e `backup:run` funcionando ("esqueleto andante") | 5 | 01/11/2026 |
| M3 | Login e Clientes em funcionamento | 6 | 08/11/2026 |
| M4 | Atendimentos completos | 7 | 15/11/2026 |
| M5 | Fluxo de caixa completo | 8 | 22/11/2026 |
| M6 | Recibos PDF emitidos e compartilhados (**MVP funcional**) | 9 | 29/11/2026 |
| M7 | Dashboard e relatórios; congelamento de escopo | 10 | 06/12/2026 |
| M8 | Produção no ar; backup off-site e restauração testados; usabilidade concluída; *release candidate* | 11 | 13/12/2026 |
| M9 | Apresentação final e Relatório de Encerramento | 12 | 20/12/2026 |

## 6. Mapeamento para os artefatos PMBOK (sugestão de EAP)

| Pacote de trabalho (EAP, nível 2) | Origem neste planejamento |
| :--- | :--- |
| 1. Gerenciamento do projeto | TAP, plano de comunicação, relatórios de status semanais, controle de mudanças |
| 2. Requisitos e escopo | `02-requisitos.md`, PVs |
| 3. Arquitetura e infraestrutura | `01`, `06`; Sprint 0 |
| 4. Módulo Acesso/Clientes | Sprint 1 |
| 5. Módulo Atendimentos | Sprint 2 |
| 6. Módulo Fluxo de Caixa | Sprint 3 |
| 7. Módulo Comprovantes | Sprint 4 |
| 8. Módulo Relatórios/Dashboard | Sprint 5 |
| 9. Qualidade e testes | `08`; testes por sprint; usabilidade |
| 10. Implantação e operação | Deploy, backup e restauração, monitoramento (Sprint 6) |
| 11. Encerramento | Apresentação e Relatório Final |

Os riscos de `09` alimentam a matriz de riscos; o cronograma desta seção alimenta o cronograma do PMBOK; custos permanecem R$ 0,00 diretos + 144 h de esforço (TAP §12).

## 7. Priorização e plano de contingência (corte de escopo)

Com ~62 h de desenvolvimento, a ordem de corte quando houver atraso é (do primeiro a cortar ao último):

| Ordem de corte | Item | Impacto |
| :---: | :--- | :--- |
| 1 | Exportações CSV/PDF, PWA, página pública de verificação, comparativos (**C**) | Nenhum sobre os critérios do TAP |
| 2 | Verificação de e-mail, tela de categorias (usar apenas padrão), exportação de dados LGPD | Baixo |
| 3 | Envio por e-mail do recibo, cancelamento de recibo, relatório anual | Médio (TAP cita e-mail como canal; WhatsApp cobre o requisito mínimo — **PV-05**) |
| 4 | Anonimização de cliente, histórico detalhado do cliente | Médio (LGPD parcial: manter exclusão de conta) |
| 5 | Gráficos (manter tabelas) | Médio |
| **Nunca cortar** | Auth, isolamento por usuário, clientes, atendimentos, lançamentos, recibo PDF + download + WhatsApp, resumo de caixa, relatório de faturamento, termos/privacidade, **backup diário + cópia off-site criptografada + restauração testada** | Critérios SMART do TAP |

**Cenário alternativo (se o orientador exigir desenvolvimento *apenas* nas semanas 6–9, 48 h):** fundir Sprint 0 ao Sprint 1 com escopo mínimo (login + clientes + atendimentos), entregar caixa e recibo como núcleo, e reduzir Relatórios a faturamento + clientes recorrentes em tabelas. Nesse cenário o TAP §2 ("100% dos módulos") só é atingível com escopo funcional reduzido — registrar como desvio aprovado.

## 8. Cadência e definições

| Tema | Definição |
| :--- | :--- |
| Sprint | 1 semana (12 h): planejamento de 30 min → execução → revisão/demonstração de 30 min → retrospectiva curta; registrar horas reais |
| Reporte | Status semanal curto ao orientador (1 página: feito, próximo, riscos, horas) |
| Branching | Trunk-based com branches curtas + Pull Requests (mesmo solo, para acionar o CI) e Conventional Commits |
| **Definition of Ready** | História com critério de aceite, telas identificadas em `05`, rotas em `04`, PVs relacionados resolvidos ou com decisão provisória |
| **Definition of Done** | Código no `main` com CI verde; testes do comportamento novo (incluindo autorização/isolamento e `assertInertia` das páginas); lint e tipos ok; migrations reversíveis e testadas em SQLite; responsivo em 360 px e desktop; estados vazio/erro/carregando tratados; demo realizada; documentação/EAP atualizadas se houve mudança |
| Controle de mudanças | Novo requisito entra no backlog com prioridade MoSCoW e estimativa; só entra na sprint se substituir item de mesmo peso; mudanças de escopo do TAP exigem aval do orientador |
