# 04 — Integrações

Uma **integração** é um plugin do grupo `esquemarico` que sabe ler dados de uma fonte e
entregá-los como *payload* para o motor. É o mecanismo de extensibilidade: novas fontes de
dados entram como plugins, sem alterar o núcleo.

## Hierarquia de classes

```
Joomla\CMS\Plugin\CMSPlugin
        │
        ▼
PluginBase                     (ciclo de vida padrão — fornecido pelo componente)
   ├── PluginBaseArtigo        (blogs/artigos — remove schemas Article duplicados)
   ├── PluginBaseProduto       (e-commerce — helpers de preço/disponibilidade/avaliação)
   └── PluginBaseEvento        (eventos — helpers de datas e local)
```

Um plugin de integração concreto (ex.: `plg_esquemarico_content`) estende uma dessas
classes e implementa, no mínimo, um método `view<Nome>()`.

## Métodos que uma integração implementa

| Método | Obrigatório? | Papel |
|--------|-------------|-------|
| `view<Nome>()` | Sim | Retorna o **payload** (array) do item da página atual. O sufixo `<Nome>` casa com a *view* ativa (ex.: `viewArticle`, `viewProduct`). |
| `passContext()` | Recomendado | Validação rápida: o plugin deve rodar nesta página? (ex.: o componente ativo é o esperado e há um `id`.) |
| `getThingID()` | Quando o ID não vem em `id` | Extrai o ID do item da query string (ex.: `virtuemart_product_id`). |
| `getView()` | Quando a *view* não vem em `view` | Resolve qual `view<Nome>()` chamar. |
| `onMapOptions($plugin, &$options)` | Opcional | Adiciona opções de mapeamento próprias (campos personalizados, categorias…). |
| `onContentPrepareForm($form, $data)` | Opcional | Injeta o formulário de edição rápida na tela de edição da fonte (ex.: aba no editor de artigos). |
| `advertiseSupportedViews()` | Herdado | Publica as *views* suportadas (por introspecção dos métodos `view*`), usado pelos *dropdowns* do backend. |

## O payload

O payload é um array associativo com **chaves canônicas** que as propriedades dos
formulários de schema esperam por padrão. Chaves comuns:

```
id, alias, headline, description, introtext, fulltext,
image, imagetext, image_intro, image_full,
created, modified, publish_up, publish_down, created_by, created_by_alias,
ratingValue, reviewCount, metakey, metadesc,
category.id, category.title, category.alias,
cf.<nome_do_campo>            (campos personalizados)
```

Tipos especializados acrescentam:

- **Produto**: `sku`, `mpn`, `gtin`, `brand`, `offerPrice` (número ou faixa `[low, high]`),
  `currency`, `offerAvailability`, `offerItemCondition`, `weight`, `weightUnit`, `reviews`.
- **Evento**: `startDate`, `endDate`, `locationName`, endereço, `performerName`,
  `organizerName`, `price`, `offerCurrency`.

O payload alimenta tanto a geração do JSON-LD quanto as SmartTags (ver
[05 — Mapeamento e SmartTags](05-mapeamento-e-smarttags.md)): cada chave vira a SmartTag
`{gsd.item.<chave>}` disponível no mapeamento.

## Exemplo: conteúdo nativo do Joomla (`plg_esquemarico_content`)

1. `passContext()` confirma que o componente ativo é `com_content` e que há um `id`.
2. `viewArticle()` carrega o artigo via *model* do com_content, extrai título, texto
   (intro/full), imagens (intro/full), datas, autor, avaliação, metadados, categoria e —
   se habilitado — os **campos personalizados** (prefixados com `cf.`).
3. `onMapOptions()` adiciona ao seletor de mapeamento as imagens intro/full, o alias do
   autor, as opções de categoria e cada campo personalizado.
4. `onContentPrepareForm()` adiciona uma aba "Esquema Rico" no editor de artigos para
   edição rápida do item de marcação (quando a edição rápida está habilitada e o usuário
   tem permissão).

## Plugins de integração planejados

Organizados por categoria (espelhando o ecossistema Joomla):

- **Conteúdo/Blogs**: `content` (nativo), `k2`, `easyblog`, `rsblog`.
- **E-commerce**: `virtuemart`, `hikashop`, `j2store`, `jshopping`, `eshop`.
- **Eventos/Calendários**: `jevents`, `dpcalendar`, `jcalpro`, `icagenda`, `djevents`,
  `eventbooking`, `rseventspro`.
- **Diretórios/Listagens**: `sobipro`, `jbusinessdirectory`, `djclassifieds`, `djcatalog2`.
- **Construtores de página**: `sppagebuilder`, `quix`, `gridbox`.
- **Avaliações**: `jreviews`, `zoo`.
- **Especial**: `menus` (marcação manual por item de menu).

> A ordem de implementação está priorizada no `TODO.md`. O núcleo já contempla as três
> classes-base, de modo que cada novo plugin é majoritariamente um `view<Nome>()` + um
> `assignments.xml` + arquivos de idioma.

## Edição manual por item de menu (`menus`)

O plugin `menus` é especial: não extrai dados ricos de uma fonte, mas permite ao usuário
marcar **um item de menu específico** manualmente. Ele:

- usa `Itemid` como ID e o item de menu ativo como contexto;
- expõe um payload simples (`headline`, `description`, `image`) a partir dos parâmetros do
  menu;
- injeta o formulário de edição rápida diretamente no Gerenciador de Menus.

## Arquivos de um plugin de integração

```
plg_esquemarico_<nome>/
├── <nome>.xml                      (manifesto)
├── services/provider.php           (registro DI — padrão Joomla 6)
├── src/Extension/<Nome>.php        (a classe do plugin)
├── form/
│   ├── assignments.xml             (condições de publicação por view)
│   └── form.xml                    (opcional — edição rápida embutida)
└── language/pt-BR, en-GB           (strings)
```
