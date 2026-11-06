# Identidade Visual e Paleta de Cores — GestÁgil

> **Projeto:** GestÁgil — Gestão de Caixa e Atendimentos  
> **Documento:** Guia de Identidade Visual e Design System Base  
> **Público-alvo:** Microempreendedores Individuais (MEI), Comerciantes Locais e Prestadores de Serviços Autônomos  
> **Stack Frontend:** Laravel + Inertia.js + Vue 3 + Tailwind CSS

---

## 1. Diretrizes de Design e Justificativa Geral

A identidade visual do GestÁgil foi projetada a partir das restrições técnicas e operacionais das personas do projeto (uso intenso de smartphones Android intermediários, atendimento rápido no balcão ou em campo, ambientes com iluminação variável e rejeição a sistemas complexos).

### Pilares Visuais:
1. **Alto Contraste (Acessibilidade WCAG AA/AAA):** Garante leitura imediata em telas com brilho baixo ou sob luz solar direta, evitando erros de digitação de valores no caixa.
2. **Semântica Financeira Instantânea:** O usuário não precisa ler rótulos para diferenciar entradas de saídas ou serviços concluídos de pendentes; a cor comunica o estado em menos de um segundo.
3. **Profissionalismo nos Comprovantes:** Os recibos gerados em PDF e enviados via WhatsApp utilizam tons sóbrios para transmitir credibilidade ao cliente final do comerciante/prestador.
4. **Baixa Carga Cognitiva:** Uso predominante de fundos neutros claros para reduzir a fadiga visual após um dia inteiro de trabalho e destacar apenas as ações principais.

---

## 2. Conceito do Logotipo e Tipografia

### Símbolo
* **Construção:** Letra "G" geométrica integrada a um sinal de confirmação (*check*) apontando para o canto superior direito, acompanhada de linhas laterais de movimento.
* **Justificativa:** 
  * **Letra G:** Identificação direta do nome **GestÁgil**, facilitando o reconhecimento como ícone de atalho na tela inicial do celular (PWA).
  * **Sinal de Check:** Representa atendimento concluído, conta paga e recibo emitido sem burocracia.
  * **Vetor Ascendente e Linhas de Velocidade:** Simbolizam o crescimento do faturamento líquido e a promessa de registrar uma venda ou serviço em menos de 30 segundos.

### Tipografia Recomendada
* **Fonte Principal (UI e Textos):** `Inter` ou `Plus Jakarta Sans` (Google Fonts — gratuitas e open source).
* **Pesos Utilizados:** 
  * `Regular (400)` para textos corridos e anotações técnicas.
  * `Medium (500)` para rótulos de formulários e menus.
  * `Bold (700)` para títulos de telas, valores monetários no fluxo de caixa e cabeçalhos de recibos.
* **Justificativa:** Fontes sem serifa com excelente altura de "x" e números tabulares (`tabular-nums`), essenciais para alinhar colunas de valores em relatórios financeiros e garantir legibilidade em PDFs no celular.

---

## 3. Paleta de Cores e Justificativas

### 3.1. Cores da Marca e Ação Principal

#### Azul Confiança (Cor Primária)
* **HEX:** `#1E40AF` (Tailwind: `blue-800`)
* **Hover / Estado Ativo:** `#1D4ED8` (Tailwind: `blue-700`)
* **Fundo Suave (Seleção/Foco):** `#DBEAFE` (Tailwind: `blue-100`)
* **RGB:** `rgb(30, 64, 175)`
* **Onde usar:** Cabeçalho da aplicação, barra de navegação ativa, botões de ação padrão (ex.: "Salvar Cliente", "Filtrar Relatório"), títulos do recibo PDF.
* **Justificativa:** O azul escuro está associado a instituições bancárias, segurança e seriedade. Atende diretamente ao desejo da persona de profissionalizar seu negócio perante os clientes sem parecer um sistema corporativo frio.

#### Verde Ágil (Cor Secundária / Destaque)
* **HEX:** `#10B981` (Tailwind: `emerald-500`)
* **Hover / Estado Ativo:** `#059669` (Tailwind: `emerald-600`)
* **RGB:** `rgb(16, 185, 129)`
* **Onde usar:** Botão de Ação Flutuante (FAB `+`) para lançamento rápido, botão de "Enviar por WhatsApp", CTAs principais de conversão/fechamento.
* **Justificativa:** Conecta-se psicologicamente a três elementos centrais da rotina do usuário: dinheiro/lucro, o ecossistema do WhatsApp (principal ferramenta de trabalho da persona) e permissão para avançar com rapidez.

---

### 3.2. Cores Semânticas (Fluxo de Caixa e Status de Atendimento)

#### Receita e Concluído (Sucesso)
* **Cor do Texto / Ícone:** `#16A34A` (Tailwind: `green-600`)
* **Fundo de Badge / Card:** `#DCFCE7` (Tailwind: `green-100`)
* **Borda de Destaque:** `#86EFAC` (Tailwind: `green-300`)
* **Onde usar:** Valores de entrada (`income`), saldo positivo no dashboard, badge de atendimento `Concluído`, recibo válido e notificações de sucesso.
* **Justificativa:** Convenção universal para entradas financeiras e tarefas finalizadas. O fundo verde pastel (`#DCFCE7`) combinado com texto verde escuro (`#16A34A`) garante leitura confortável sem ofuscar a tela.

#### Despesa e Cancelado (Perigo / Saída)
* **Cor do Texto / Ícone:** `#DC2626` (Tailwind: `red-600`)
* **Fundo de Badge / Card:** `#FEE2E2` (Tailwind: `red-100`)
* **Borda de Destaque:** `#FCA5A5` (Tailwind: `red-300`)
* **Onde usar:** Valores de saída (`expense` — combustível, insumos, fornecedores), saldo negativo, badge de atendimento ou recibo `Cancelado`, botões destrutivos (ex.: "Excluir Conta", "Cancelar Recibo").
* **Justificativa:** Alerta visual imediato para saída de caixa e ações irreversíveis. Ajuda o microempreendedor a identificar rapidamente o peso das despesas no fechamento do dia.

#### Em Andamento e Atenção (Alerta)
* **Cor do Texto / Ícone:** `#D97706` (Tailwind: `amber-600`)
* **Fundo de Badge / Card:** `#FEF3C7` (Tailwind: `amber-100`)
* **Borda de Destaque:** `#FDE68A` (Tailwind: `amber-200`)
* **Onde usar:** Status de atendimento `Em andamento`, avisos de onboarding incompleto (ex.: "Configure os dados da sua loja antes de emitir recibos").
* **Justificativa:** O tom âmbar/laranja escuro evita o problema clássico do amarelo puro (que fica ilegível sobre fundo branco). Sinaliza pendências operacionais que exigem ação futura (concluir serviço e cobrar o cliente).

---

### 3.3. Tons Neutros (Estrutura e Interface Limpa)

#### Fundo da Aplicação (Background)
* **HEX:** `#F8FAFC` (Tailwind: `slate-50`)
* **RGB:** `rgb(248, 250, 252)`
* **Onde usar:** Plano de fundo geral de todas as telas autenticadas e páginas de login.
* **Justificativa:** Um cinza-azulado extremamente claro cria contraste suave com os cartões brancos, delimitando áreas de conteúdo sem precisar de bordas pesadas que poluem a interface no celular.

#### Superfície (Cards, Modais e Recibos)
* **HEX:** `#FFFFFF` (Branco Puro)
* **RGB:** `rgb(255, 255, 255)`
* **Onde usar:** Cards de resumo financeiro, itens de listagem, formulários, modais e fundo do documento PDF de recibo.
* **Justificativa:** Transmite limpeza visual, destaca as informações prioritárias sobre o fundo `#F8FAFC` e garante que o recibo PDF seja econômico caso o cliente decida imprimi-lo.

#### Texto Principal (Títulos e Valores)
* **HEX:** `#0F172A` (Tailwind: `slate-900`)
* **RGB:** `rgb(15, 23, 42)`
* **Onde usar:** Nomes de clientes, valores totais, títulos de páginas e dados principais do recibo.
* **Justificativa:** Substitui o preto puro (`#000000`) por um cinza chumbo profundo, reduzindo o cansaço ocular em telas luminosas enquanto mantém contraste máximo (taxa de contraste > 15:1 sobre branco).

#### Texto Secundário (Rótulos e Metadados)
* **HEX:** `#475569` (Tailwind: `slate-600`)
* **RGB:** `rgb(71, 85, 105)`
* **Onde usar:** Datas, horários, formas de pagamento, categorias, anotações técnicas, textos de apoio e aviso legal do recibo ("Este documento não substitui nota fiscal").
* **Justificativa:** Cria hierarquia visual clara. O olho do usuário lê primeiro o nome do cliente e o valor (`#0F172A`) e, em seguida, os detalhes complementares (`#475569`).

#### Bordas, Divisores e Campos Inativos
* **HEX:** `#E2E8F0` (Tailwind: `slate-200`)
* **Borda de Input Padrão:** `#CBD5E1` (Tailwind: `slate-300`)
* **Onde usar:** Linhas divisórias de listas de lançamentos, bordas de cartões, contornos de inputs de formulário e tabelas do recibo.
* **Justificativa:** Organiza os dados em listas densas mantendo o aspecto leve e moderno.

---

## 4. Implementação Técnica (CSS Variables & Tailwind)

### 4.1. Variáveis CSS (`resources/css/app.css`)

```css
:root {
  /* Cores da Marca */
  --color-primary: #1E40AF;
  --color-primary-hover: #1D4ED8;
  --color-primary-soft: #DBEAFE;
  
  --color-accent: #10B981;
  --color-accent-hover: #059669;

  /* Tons Neutros */
  --color-bg: #F8FAFC;
  --color-surface: #FFFFFF;
  --color-text-main: #0F172A;
  --color-text-muted: #475569;
  --color-border: #E2E8F0;
  --color-input-border: #CBD5E1;

  /* Cores Semânticas - Caixa e Atendimentos */
  --color-income: #16A34A;
  --color-income-bg: #DCFCE7;
  --color-income-border: #86EFAC;

  --color-expense: #DC2626;
  --color-expense-bg: #FEE2E2;
  --color-expense-border: #FCA5A5;

  --color-pending: #D97706;
  --color-pending-bg: #FEF3C7;
  --color-pending-border: #FDE68A;
}
```

### 4.2. Extensão do Tema no Tailwind (`tailwind.config.js`)

```javascript
export default {
  theme: {
    extend: {
      colors: {
        brand: {
          primary: '#1E40AF',
          'primary-hover': '#1D4ED8',
          'primary-soft': '#DBEAFE',
          accent: '#10B981',
          'accent-hover': '#059669',
        },
        semantic: {
          income: '#16A34A',
          'income-bg': '#DCFCE7',
          expense: '#DC2626',
          'expense-bg': '#FEE2E2',
          pending: '#D97706',
          'pending-bg': '#FEF3C7',
        }
      },
      fontFamily: {
        sans: ['Inter', 'system-ui', '-apple-system', 'sans-serif'],
      }
    }
  }
}
```

---

## 5. Regras de Aplicação nas Telas (Guia Rápido de UX)

1. **Proporção 60-30-10:**
   * **60% Neutros (`#F8FAFC` e `#FFFFFF`):** Fundos e cartões para manter a tela limpa.
   * **30% Estrutura e Tipografia (`#0F172A`, `#475569`, `#1E40AF`):** Navegação, textos e cabeçalhos.
   * **10% Ação e Semântica (`#10B981`, `#16A34A`, `#DC2626`, `#D97706`):** Botões principais, valores monetários e badges de status.
2. **Números Financeiros:** Nunca exibir valores de entrada e saída apenas com a cor; sempre combinar a cor semântica com o sinal matemático (`+ R$ 150,00` em `#16A34A` e `- R$ 45,00` em `#DC2626`) para garantir acessibilidade a usuários com daltonismo.
3. **Recibos em PDF:** Utilizar fundo `#FFFFFF`, cabeçalho com detalhe em `#1E40AF` e textos em `#0F172A` para máxima nitidez tanto na tela do WhatsApp quanto em impressoras comuns ou térmicas.