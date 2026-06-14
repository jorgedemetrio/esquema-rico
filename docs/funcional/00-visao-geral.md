# 00 — Visão geral

## O que é

**Esquema Rico** é uma extensão para Joomla que adiciona **dados estruturados** às páginas
do site. Dados estruturados são um padrão de marcação que descreve o significado do
conteúdo de uma página de forma que os mecanismos de busca consigam interpretá-lo: "isto é
um produto", "isto é uma receita com 30 minutos de preparo", "isto é um evento que começa
no dia X".

A extensão produz essa descrição no formato **JSON-LD** (JavaScript Object Notation for
Linked Data), recomendado pelo Google, usando o vocabulário público **Schema.org**. O
resultado é um bloco `<script type="application/ld+json">` injetado no HTML da página.

## Por que isso importa

Quando o conteúdo está corretamente marcado, ele pode aparecer na Pesquisa Google com
**Resultados Avançados** (*Rich Results*): estrelas de avaliação, preço e disponibilidade
de produtos, perguntas frequentes expansíveis, miniaturas de receita, datas de eventos,
trilha de navegação (*breadcrumbs*), caixa de pesquisa do site, painel de conhecimento da
organização, entre outros. Isso aumenta a visibilidade e a taxa de cliques sem alterar a
aparência visual do site.

## Conceitos centrais

| Conceito | Definição |
|----------|-----------|
| **Item de marcação** | Uma configuração salva pelo usuário que diz "gere um schema do tipo X com estes mapeamentos de campos, nestas condições". É a unidade gerenciável no painel. |
| **Tipo de conteúdo** (*content type*) | O tipo de schema a ser gerado: Artigo, Produto, Evento, Receita, FAQ, etc. Cada um corresponde a um ou mais tipos do Schema.org. |
| **Integração** (*app*) | Um plugin que sabe ler dados de uma fonte (o conteúdo nativo do Joomla, ou um componente de terceiros) e entregá-los como *payload*. |
| **Payload** | Conjunto de dados brutos extraídos do item da página atual (título, descrição, imagem, datas, preço…) que serão usados para preencher o schema. |
| **Mapeamento** (*mapping*) | A ligação entre uma propriedade do schema (ex.: `headline`) e a origem do valor (ex.: o título do artigo, um campo fixo, ou uma SmartTag). |
| **SmartTag** | Variável dinâmica no formato `{nome}` substituída em tempo de renderização (ex.: `{gsd.item.headline}`, `{user.name}`). |
| **Condição de publicação** | Regra que determina em quais páginas/contextos um item de marcação deve ser renderizado (por menu, idioma, dispositivo, data, etc.). |
| **Esquema global** | Marcação não vinculada a um conteúdo específico, configurada uma vez e exibida em toda a home ou em todas as páginas: WebSite, Logo, Perfis Sociais, Negócio Local, Breadcrumbs. |

## Como o usuário interage com o produto

1. Instala o pacote (componente + plugin de sistema + biblioteca + integrações).
2. Habilita o plugin de sistema "Sistema – Esquema Rico".
3. No painel do componente, configura os **esquemas globais** (nome do site, logo,
   perfis sociais, negócio local, breadcrumbs).
4. Cria **itens de marcação**: escolhe um tipo de conteúdo (ex.: Produto), uma integração
   (ex.: conteúdo nativo do Joomla), faz o mapeamento dos campos e define as condições.
5. Visita o site: o plugin de sistema detecta a página, encontra os itens aplicáveis,
   monta o JSON-LD e o injeta no HTML.
6. Valida o resultado no **Teste de Resultados Avançados** do Google e no painel da
   ferramenta (que inclui um testador embutido).

## Princípios de projeto

- **Não invasivo**: a marcação é adicional; não altera o layout nem o conteúdo visível.
- **Sem duplicação**: a ferramenta pode remover microdados/JSON-LD conflitantes gerados
  por templates ou outras extensões, evitando schemas duplicados que o Google penaliza.
- **Orientado a dados**: a lógica de cada schema é declarada em formulários XML e classes
  PHP isoladas, facilitando manutenção e a adição de novos tipos.
- **Extensível**: novas fontes de dados entram como plugins de integração, sem tocar no
  núcleo.

## Fora de escopo

- A extensão não garante posições na Pesquisa; ela apenas habilita a elegibilidade a
  Resultados Avançados. A decisão final é do algoritmo do Google.
- Não edita o conteúdo dos artigos/produtos; apenas lê e descreve.
