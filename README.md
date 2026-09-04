# 🛡️ Amura Suporte - Sistema de Gestão de Plantão & Sobreaviso

> **Mobile Offline-First (PWA) + Sincronização Inteligente + Retaguarda Laravel 12**  
> Módulo completo para registro e apuração de plantões de suporte técnico com cálculo trabalhista automatizado (CLT/Convenção), escala horária fixa da empresa, suporte a atendimentos retroativos e simultâneos, ranking de clientes e painel administrativo com auditoria e manutenção de horas.

---

## 📑 Sumário

- [Visão Geral](#-visão-geral)
- [Regras de Negócio e Cálculos Trabalhistas](#-regras-de-negócio-e-cálculos-trabalhistas)
- [Escala de Plantão da Empresa](#-escala-de-plantão-da-empresa)
- [Aplicativo Mobile Offline-First (PWA)](#-aplicativo-mobile-offline-first-pwa)
- [Painel Administrativo & Relatórios Locais](#-painel-administrativo--relatórios-locais)
- [Manutenção, Auditoria e Glosa de Horas](#-manutenção-auditoria-e-glosa-de-horas)
- [Arquitetura Técnica & Banco de Dados](#-arquitetura-técnica--banco-de-dados)
- [Endpoints da API](#-endpoints-da-api)
- [Como Executar o Projeto Localmente](#-como-executar-o-projeto-localmente)
- [Como Acessar pelo Celular na Intranet](#-como-acessar-pelo-celular-na-intranet)

---

## 💡 Visão Geral

O **Sistema de Gestão de Plantão & Sobreaviso** foi concebido para solucionar o desafio de técnicos que realizam atendimentos emergenciais fora do horário comercial, muitas vezes em trânsito, em casa ou sem conectividade com a rede da empresa.

### Principais Pilares:
1. **Zero Perda de Dados:** Aplicativo móvel que opera **100% offline** com banco de dados local `IndexedDB` nativo no aparelho do técnico.
2. **Conformidade Trabalhista:** Apuração exata de sobreaviso e horas extras aplicando os fatores legais e contratuais estabelecidos.
3. **Escala Automática:** Escala horária corporativa computada automaticamente sem risco do colaborador esquecer de ativar ou desativar o plantão.
4. **Governança & Gestão:** Painel administrativo com extrato detalhado, simulação em R$ da folha de pagamento, ranking de clientes demandantes e ferramentas para o gestor ajustar, auditar ou glosar horas indevidas.
5. **Integração com Helpdesk:** Os chamados sincronizados criam automaticamente os tickets oficiais na tabela `ticketit` do sistema local.

---

## ⚖️ Regras de Negócio e Cálculos Trabalhistas

A apuração de horas para folha de pagamento obedece à seguinte matriz de cálculo:

| Modalidade | Fator Multiplicador | Regra / Justificativa | Exemplo Prático |
| :--- | :---: | :--- | :--- |
| **Hora de Sobreaviso** | $\mathbf{0,333\times}$ | **1/3 da hora normal** pelo tempo em que o técnico permaneceu em sobreaviso aguardando chamados. | 3h de sobreaviso líquido = **1,00h** equivalente na folha |
| **Hora Extra Semana (Seg a Sex)** | $\mathbf{1,50\times}$ | **50% de adicional** sobre atendimentos realizados em dias úteis (18:00 às 21:00). | 1h de atendimento na semana = **1,50h** equivalente |
| **Hora Extra Sábado** | $\mathbf{1,75\times}$ | **75% de adicional** sobre atendimentos realizados no sábado (09:00 às 21:00). | 1h de atendimento no sábado = **1,75h** equivalente |
| **Hora Extra Domingo e Feriados** | $\mathbf{2,00\times}$ | **100% de adicional** sobre atendimentos realizados em domingos e feriados. | 1h de atendimento no domingo = **2,00h** equivalente |

### 🧮 Fórmula de Dedutibilidade do Sobreaviso:
Durante o período de plantão, o tempo em que o técnico está atendendo deixa de ser sobreaviso e passa a ser remunerado como **Hora Extra Efetiva**. A apuração do sobreaviso líquido é:

$$\text{Sobreaviso Líquido} = \max\Big(0,\ \text{Sobreaviso Bruto da Escala} - \sum \text{Minutos Trabalhados}\Big)$$

### 🕒 Atendimentos que Ultrapassam as 21:00:
* O sistema **não impõe travas nem limites rígidos** de horário.
* Atendimentos que se estendem além das 21:00 (ex: *20:45 às 21:45*) ou chamados emergenciais que começam tarde da noite (ex: *21:30 às 22:15*) são computados integralmente como **Hora Extra**.
* O colaborador recebe a remuneração integral de todo o tempo trabalhado, e o sobreaviso bruto nunca fica negativo ($\max(0, \dots)$).

---

## 📅 Escala de Plantão da Empresa

Para mitigar o esquecimento do colaborador de acionar o botão de início/fim do plantão, implementamos o regime de escala automática:

```
[ Segunda a Sexta ] ─── 18:00 às 21:00 (3h brutas)   ───> 🤖 100% Automático
[ Sábado ]          ─── 09:00 às 21:00 (12h brutas)  ───> 🤖 100% Automático
[ Domingo / Feriado ] ── Horário Variável / Manual   ───> 👤 Acionamento Manual / Modo Feriado
```

1. **Segunda a Sexta (18h às 21h):** Computa automaticamente **3 horas (180 minutos)** de sobreaviso bruto e desconta os atendimentos realizados, sem exigir que o técnico toque em nada.
2. **Sábado (09h às 21h):** Computa automaticamente **12 horas (720 minutos)** de sobreaviso bruto.
3. **Domingos e Feriados:** Exibe o botão `[ ▶ Iniciar Plantão ]` e o cronômetro para medição exata do período, além do botão de atalho `[ ⚡ Ativar Modo Feriado ]` para forçar o modo manual em feriados ocorridos durante a semana.

---

## 📱 Aplicativo Mobile Offline-First (PWA)

Localizado em `/public/mobile/`, desenvolvido em **Vanilla JS modular + CSS moderno + IndexedDB nativo** sem dependência de internet ou bibliotecas externas pesadas:

* **Identidade Visual Amura:** Paleta de cores oficial (`#0d6efd` primário e tema Navy Ocean `#0b1329` / `#131f37`) com logo oficial da empresa.
* **Aba 1 (Plantão & Escala):** Exibição do status atual da escala, cronômetro em tempo real para turnos variáveis, cartões métricos e resumo trabalhista apurado com os multiplicadores em tempo real.
* **Aba 2 (Novo Atendimento):**
  * Busca offline instantânea de clientes por Código Empresarial ou Nome Fantasia / Razão Social;
  * Checkbox para cadastrar **Cliente Avulso / Novo** não presente na base;
  * Seletores de data e hora com atalhos de clique rápido `[ Agora ]` e `[ Ontem ]` (-24h);
  * **Atendimentos Simultâneos:** Permite lançar dois clientes atendidos no mesmo intervalo de tempo sem bloqueio;
  * **Lançamento Retroativo:** Suporte a datas de dias anteriores.
* **Aba 3 (Meus Chamados):** Listagem com badges visuais (`🟢 Ticket #1234` / `🟡 Pendente Sync`), duração formatada e botão `[ 🗑️ ]` para o técnico excluir lançamentos errados antes de sincronizar.
* **Aba 4 (Sync & Configurações):**
  * Configuração da URL da intranet;
  * Seletor de agente ativo memorizado no celular;
  * **Sincronização Silenciosa em Background:** Baixa clientes e categorias automaticamente ao conectar na rede sem exigir clique manual do técnico;
  * **Fallbacks Offline:** Se o técnico abrir o app em casa sem ter baixado a base da empresa, o app disponibiliza categorias padrão e permite digitar o cliente manualmente.

---

## 📊 Painel Administrativo & Relatórios Locais

Acessível no menu lateral do sistema em **Relatórios > Plantão & Sobreaviso** (`/admin/oncall/reports`):

### 1. Filtro por Período e Agente
* Intervalo de **Data Início** e **Data Fim** com atalhos rápidos (`Mês Atual`, `Mês Anterior`, `Últimos 15 Dias`, `Últimos 7 Dias`).
* Dropdown para filtrar por **Plantonista Específico** ou consolidar **Todos os Agentes**.
* Botão **Exportar CSV** com codificação UTF-8 BOM (abre com acentuação perfeita no Microsoft Excel).
* Botão **Imprimir Relatório** com formatação `@media print` para PDF ou folha de pagamento A4.

### 2. Cards Superiores de Indicadores (KPIs)
* **Total de Chamados no Período**
* **Clientes Únicos Atendidos**
* **Sobreaviso Líquido a Pagar** ($0,333\times$)
* **Horas Extras Fatoradas a Pagar** (1.5x / 1.75x / 2.0x)
* **Total Geral de Horas Equivalentes a Pagar**

### 3. As Quatro Abas de Relatório:
* **Aba 1 (Cálculo Trabalhista Mensal por Agente):** Tabela detalhada discriminando horas da escala, atendimentos semana, sábado, domingo, sobreaviso líquido, horas equivalentes totais e **Simulador Interativo de Custo em R$** com input de valor da hora normal.
* **Aba 2 (Clientes Atendidos por Agente):** Painéis agrupados por técnico contendo cada empresa atendida, código, número de chamados, tempo dedicado e data do último chamado.
* **Aba 3 (Ranking dos Clientes que Mais Utilizam o Plantão):** Classificação das empresas mais demandantes (#1, #2, #3 com medalhas), percentual do volume com barra visual de participação e taxa de chamados resolvidos no plantão.
* **Aba 4 (Extrato Analítico com Auditoria):** Tabela completa de cada atendimento realizado com número do Ticket, horários, duração e ferramentas de gestão.

---

## 🛠️ Manutenção, Auditoria e Glosa de Horas

Para evitar pagamentos indevidos por erros do técnico ou desacordo da diretoria, o gestor dispõe na **Aba 4** de ferramentas completas:

```
Extrato Analítico ───> Linha do Atendimento ───> [ ✏️ Ajustar ] / [ 🗑️ Excluir ]
```

1. **Glosa Total de Horas (Patrão não aceitou o chamado):**
   * No modal, basta desmarcar a opção *"Aprovar Horas Deste Atendimento para Pagamento"*.
   * O chamado permanece no extrato para histórico do cliente, mas computa **0 minutos de hora extra** na folha e mantém o sobreaviso original intacto (badge `🚫 Glosado (0m)`).
2. **Glosa Parcial / Duração Ajustada:**
   * Se o técnico lançou 120 minutos mas o combinado foi 30 minutos, o gestor informa 30 minutos no campo *"Duração Ajustada/Autorizada"*. A folha passa a computar estritamente o tempo autorizado (badge `⚠️ 30 min (Ajustado)`).
3. **Correção de Horários:**
   * Permite retificar Data e Hora de início e fim caso o técnico tenha digitado errado.
4. **Justificativa do Gestor (`admin_notes`):**
   * Registro formal da motivação do ajuste para transparência entre empresa e colaborador.
5. **Recálculo Automático:**
   * Ao salvar ou excluir um lançamento, todo o turno e os relatórios do período são recalculados imediatamente.

---

## 🏗️ Arquitetura Técnica & Banco de Dados

### 1. Migrations Criadas
* `database/migrations/2026_09_03_000001_create_oncall_tables.php`:
  * `oncall_shifts`: gerencia turnos de sobreaviso (`uuid`, `user_id`, `started_at`, `ended_at`, `total_standby_minutes`, `total_worked_minutes`, `status`).
  * `oncall_attendances`: atendimentos (`uuid`, `oncall_shift_id`, `user_id`, `customer_id`, `customer_name_fallback`, `contact_name`, `started_at`, `ended_at`, `duration_minutes`, `trouble`, `solution`, `is_resolved`, `ticket_id`).
* `database/migrations/2026_09_03_000002_add_is_oncall_to_users_table.php`:
  * Adiciona a coluna booleana `is_oncall` na tabela `users` para filtrar apenas quem de fato é plantonista.
* `database/migrations/2026_09_03_000003_add_management_fields_to_oncall_attendances.php`:
  * Adiciona `is_approved`, `adjusted_duration_minutes` e `admin_notes` para manutenção e glosa de horas.

### 2. Modelos Eloquent
* `App\Models\Oncall\OncallShift`: relacionamento com `agent` e `attendances`, método `recalculateHours()`.
* `App\Models\Oncall\OncallAttendance`: relacionamento com `shift`, `agent`, `customer`, `category` e `ticket`. Possui o assessor `effective_minutes` para cálculo inteligente respeitando glosas e ajustes da diretoria.
* `App\Models\User`: escopo `scopeOncall()`, flag `is_oncall` adicionada nos formulários e modais administrativos de usuário.

---

## 🔌 Endpoints da API

| Método | Endpoint | Descrição |
| :--- | :--- | :--- |
| `GET` | `/api/v1/oncall/master-data` | Retorna lista leve de clientes ativos, categorias e agentes com `is_oncall = true` para cache offline no celular. |
| `POST` | `/api/v1/oncall/sync` | Recebe lote de atendimentos e turnos com UUIDs, grava com idempotência, cria os tickets oficiais na tabela `ticketit` e recalcula horas. |
| `GET` | `/api/v1/oncall/reports` | Retorna dados consolidados da apuração trabalhista em JSON. |
| `PUT` | `/admin/oncall/attendances/{id}` | Salva ajustes de horários, glosa ou duração autorizada pelo gestor. |
| `DELETE` | `/admin/oncall/attendances/{id}` | Exclui lançamento indevido ou duplicado e recalcula o plantão. |

---

## 🚀 Como Executar o Projeto Localmente

O ambiente roda isolado via Docker com as seguintes portas mapeadas:
* **Aplicação Laravel:** `http://localhost:8095`
* **Adminer (Banco de Dados):** `http://localhost:8087`
* **Vite:** `5178`

### Comandos para Iniciar:
```bash
# Entrar na pasta do projeto
cd d:/Amura/Projetos/suporte_Plantao

# Subir os containers do ambiente de plantão
docker compose -f docker-compose.yml -f docker-compose.dev.yml up -d

# Executar as migrations
docker exec -it plantao12_app php artisan migrate
```

---

## 📲 Como Acessar pelo Celular na Intranet

1. Conecte o smartphone na mesma rede Wi-Fi da empresa.
2. Abra o navegador móvel (Chrome no Android ou Safari no iOS).
3. Digite o endereço do servidor na rede:
   ```text
   http://192.168.0.198:8095/mobile
   ```
   *(Substitua pelo IP local da sua máquina caso altere).*
4. Toque no menu do navegador e selecione **"Adicionar à Tela Inicial"** para instalar o ícone do aplicativo.
5. O aplicativo baixará a base automaticamente e funcionará mesmo fora da empresa durante a noite. Ao retornar ou conectar à rede, as pendências são sincronizadas com um toque.
