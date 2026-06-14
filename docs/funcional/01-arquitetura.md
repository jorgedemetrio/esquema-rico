# 01 — Arquitetura

A extensão é distribuída como um **pacote** (`pkg_esquemarico`) que agrega quatro tipos de
artefatos do Joomla. Cada um tem uma responsabilidade clara.

```
┌──────────────────────────────────────────────────────────────────────┐
│                         pkg_esquemarico (pacote)                       │
│                                                                        │
│  ┌────────────────────────┐      ┌────────────────────────────────┐   │
│  │ com_esquemarico         │      │ plg_system_esquemaricocore     │   │
│  │ (Componente)            │      │ (Biblioteca compartilhada)      │   │
│  │                         │      │ Namespace: Esquemarico\Core     │   │
│  │ • Painel / Itens / Conf │      │ • Cache                         │   │
│  │ • Tabelas + SQL         │◀────▶│ • Detecção de extensões         │   │
│  │ • Motor JSON-LD         │      │ • Condições de publicação       │   │
│  │ • Tipos de schema       │      │ • SmartTags                     │   │
│  │ • Helper / Mapeamento   │      │ • Utilitários (datas, strings)  │   │
│  └────────────────────────┘      └────────────────────────────────┘   │
│             ▲                                    ▲                      │
│             │                                    │                      │
│  ┌──────────┴──────────────┐      ┌──────────────┴─────────────────┐   │
│  │ plg_system_esquemarico  │      │ plg_esquemarico_content (+ ...) │   │
│  │ (Plugin de sistema)     │      │ (Plugins de integração)         │   │
│  │ • Injeta o JSON-LD      │      │ • Lê dados da fonte              │   │
│  │ • Esquemas globais      │      │ • Entrega o payload              │   │
│  │ • Remove duplicados     │      │ • Define mapeamentos/condições   │   │
│  └─────────────────────────┘      └────────────────────────────────┘   │
└──────────────────────────────────────────────────────────────────────┘
```

## 1. Componente `com_esquemarico`

O centro administrativo e o **motor** da extensão.

- **Backend (administrator)**: telas de Painel, Itens (CRUD da marcação) e Configurações
  (esquemas globais e opções avançadas). Estrutura MVC com namespace
  `Joomla\Component\Esquemarico\Administrator`.
- **Motor JSON-LD** (`src/Engine`): a classe que recebe os dados preparados e produz a
  string `<script type="application/ld+json">`. Contém um método construtor por tipo de
  schema. É o coração do produto.
- **Tipos de schema** (`src/Schema/Tipos`): classes que preparam/normalizam as
  propriedades de cada tipo antes de irem ao motor (datas em ISO 8601, URLs absolutas,
  limpeza de HTML, renomeações). Herdam de uma classe `Base`.
- **Helper / Mapeamento / Limpeza** (`src/Helper`): utilitários de domínio — montagem de
  breadcrumbs, leitura de configurações, opções de mapeamento, SmartTags e a remoção de
  schemas duplicados.
- **Tabelas + SQL** (`src/Table`, `sql/`): persistência dos itens e das configurações
  globais.

> O motor e os tipos de schema vivem no `src` do componente porque são compartilhados:
> tanto o backend (para validar/pré-visualizar) quanto o plugin de sistema (para
> renderizar no frontend) os utilizam. O plugin de sistema "inicializa" (boota) o
> componente para acessar essas classes.

## 2. Plugin de sistema `plg_system_esquemarico`

O responsável por **colocar a marcação na página** no frontend.

- Escuta os eventos `onBeforeCompileHead` (injeção no `<head>`) e `onAfterRender`
  (injeção no corpo / remoção de microdados), além de `onContentPrepareForm` (para a
  edição rápida no editor de artigos).
- Gera os **esquemas globais** diretamente (WebSite, Logo, Perfis Sociais, Negócio Local,
  Breadcrumbs) — ver [03 — Esquemas globais](03-esquemas-globais.md).
- Dispara o evento `onEsquemaRicoBeforeRender`, ao qual os plugins de integração
  respondem com seus blocos JSON-LD.
- Remove microdados e JSON-LD duplicados de templates/extensões de terceiros.
- Adiciona o controle de *snippet* de robôs (`max-snippet`, `max-image-preview`).

## 3. Biblioteca compartilhada `plg_system_esquemaricocore`

Um plugin de sistema que **não tem comportamento próprio**; existe para fornecer um
conjunto de classes utilitárias sob o namespace `Esquemarico\Core`, carregadas via PSR-4.
É a fundação reutilizada por todos os demais artefatos. Contém:

- **Cache**: memoização em memória por requisição (e opcionalmente em arquivo) para evitar
  recomputar campos personalizados, leitura de XML, etc.
- **Extension**: consulta a tabela `#__extensions` para saber se uma extensão de terceiros
  está instalada/habilitada e qual a sua versão.
- **Conditions**: o motor de avaliação de condições de publicação (ver
  [06 — Condições de publicação](06-condicoes-publicacao.md)).
- **SmartTags**: o substituidor de variáveis dinâmicas (ver
  [05 — Mapeamento e SmartTags](05-mapeamento-e-smarttags.md)).
- **Functions**: utilitários gerais (datas em UTC, manipulação de arrays e strings,
  detecção de feed, etc.).

> Separar a biblioteca em um plugin próprio permite atualizá-la independentemente e
> compartilhá-la entre futuras extensões da mesma família.

## 4. Plugins de integração `plg_esquemarico_*`

Cada integração é um plugin do grupo `esquemarico` que sabe extrair dados de **uma fonte**:

- `plg_esquemarico_content` — conteúdo nativo do Joomla (com_content).
- (planejados) `plg_esquemarico_menus`, `plg_esquemarico_k2`, `plg_esquemarico_virtuemart`,
  `plg_esquemarico_hikashop`, `plg_esquemarico_jevents`, etc.

Todos herdam de uma das três classes-base fornecidas pelo componente:

| Classe-base | Uso típico | Comportamento extra |
|-------------|-----------|---------------------|
| `PluginBase` | Fontes genéricas (ex.: itens de menu) | Apenas o ciclo padrão |
| `PluginBaseArtigo` | Blogs/artigos | Remove schemas `Article`/`BlogPosting` duplicados no `onAfterRender` |
| `PluginBaseProduto` | E-commerce | Helpers de preço, disponibilidade e avaliações |
| `PluginBaseEvento` | Calendários/eventos | Helpers de datas de início/fim e local |

Ver detalhes em [04 — Integrações](04-integracoes.md).

## Mapa de namespaces

| Artefato | Namespace |
|----------|-----------|
| Componente (admin) | `Joomla\Component\Esquemarico\Administrator\…` |
| Componente (site) | `Joomla\Component\Esquemarico\Site\…` |
| Biblioteca compartilhada | `Esquemarico\Core\…` |
| Plugin de sistema | `Joomla\Plugin\System\Esquemarico\…` |
| Plugin de integração (conteúdo) | `Joomla\Plugin\Esquemarico\Content\…` |

## Dependências entre artefatos

- O **plugin de sistema** depende da **biblioteca** e do **componente** (boota ambos).
- Os **plugins de integração** dependem da **biblioteca** e das **classes-base do
  componente**.
- O **componente** depende da **biblioteca** para cache, condições e SmartTags.
- A **biblioteca** não depende de nada da família (é a base).

A ordem de instalação reflete isso: biblioteca → componente → plugin de sistema →
integrações.
