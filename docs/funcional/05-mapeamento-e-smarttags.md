# 05 — Mapeamento e SmartTags

O **mapeamento** é o que liga uma propriedade do schema (ex.: `headline`) à origem do seu
valor. Em vez de o usuário digitar o título de cada produto, ele mapeia `headline` →
"título do item" uma única vez, e o valor é resolvido dinamicamente em cada página.

## Modos de mapeamento de um campo

Cada campo mapeável no editor (`map`, `mapimage`, `mapdate`, `mapuser`) oferece um seletor
com os seguintes modos:

| Modo | Significado | Valor resultante |
|------|-------------|------------------|
| **Opção de origem** | Escolher uma origem da lista (ex.: "Título do item", "Imagem intro", um campo personalizado) | A SmartTag correspondente, ex.: `{gsd.item.headline}` |
| **Fixo** (`fixed`) | Digitar um valor fixo (texto, data, ou selecionar um usuário) | O valor literal |
| **Personalizado** (`_custom_`) | Digitar uma expressão livre que pode conter SmartTags | A string com SmartTags a resolver |
| **Seletor CSS** (`_css_selector_`) | Extrair o valor de um elemento da própria página via seletor CSS | O texto/atributo capturado do HTML |
| **Desabilitado** (`_disabled_`) | Não emitir a propriedade | `false` (removido na limpeza) |

A resolução desses modos acontece na preparação do item, **antes** da substituição das
SmartTags.

## Catálogo de opções de mapeamento

As opções disponíveis no seletor são agrupadas. As **fixas** (sempre presentes):

- **Integração** (`gsd.item.*`): `id`, `alias`, `headline`, `description`, `introtext`,
  `fulltext`, `image`, `imagetext`, `weight`, `weightUnit`, datas (`created`,
  `publish_up`, `publish_down`, `modified`), `ratingValue`, `reviewCount`, `metakey`,
  `metadesc`, e `url`.
- **Autor** (`user.*`): `id`, `name`, `firstname`, `lastname`, `login`, `email`.
- **Página** (`page.*`): `title`, `browsertitle`, `desc`, `keywords`, `lang`, `generator`.
- **Informações do site** (`gsd.*` / `site.*`): `sitename`, `siteurl`, `sitelogo`,
  `site.email`.

Cada integração pode **acrescentar** opções via `onMapOptions()` — por exemplo, os campos
personalizados de um artigo entram como `gsd.item.cf.<nome>`, e as categorias como
`gsd.item.category.*`.

## SmartTags

Uma **SmartTag** é uma variável no formato `{namespace.chave}` substituída em tempo de
renderização. O motor de SmartTags recebe duas coleções:

1. O **payload** do item da página atual, exposto sob o prefixo `gsd.item.` (ex.:
   `{gsd.item.headline}`, `{gsd.item.cf.marca}`).
2. As **configurações globais** sob o prefixo `gsd.` (`{gsd.sitename}`, `{gsd.siteurl}`,
   `{gsd.sitelogo}`).

Além dessas, a biblioteca `Esquemarico\Core` fornece SmartTags de contexto:

| Família | Exemplos | Descrição |
|---------|----------|-----------|
| Usuário | `{user.name}`, `{user.email}`, `{user.id}` | Dados do autor/usuário associado |
| Página | `{page.title}`, `{page.desc}`, `{page.keywords}` | Metadados do documento atual |
| Site | `{site.name}`, `{site.email}` | Configuração global do Joomla |
| Data/Hora | `{date}`, `{year}`, `{month}`, `{time}` | Momento da renderização |
| URL | `{url}`, `{querystring:param}` | Contexto da requisição |

### Como a substituição funciona

1. Após resolver os modos de mapeamento, o item vira um conjunto de strings que podem
   conter SmartTags.
2. O motor percorre cada string e troca cada `{…}` pelo valor correspondente da coleção.
   Valores nulos viram string vazia (a tag é removida).
3. O item resolvido é mesclado ao payload e enviado à classe de preparação do tipo de
   schema.

### Exemplo completo

Item de marcação do tipo **Produto**, integração **conteúdo nativo**:

```
name        → {gsd.item.headline}      → "Tênis de corrida X"
description → {gsd.item.description}   → "Leve e respirável…"
image       → {gsd.item.image}         → "https://site/imagens/tenis.jpg"
sku         → {gsd.item.cf.sku}        → "TN-X-42"   (campo personalizado)
brand       → (fixo) "Acme"            → "Acme"
offerPrice  → {gsd.item.cf.preco}      → "299.90"
```

Resultado (após limpeza e serialização):

```json
{
  "@context": "https://schema.org",
  "@type": "Product",
  "name": "Tênis de corrida X",
  "description": "Leve e respirável…",
  "image": "https://site/imagens/tenis.jpg",
  "sku": "TN-X-42",
  "brand": { "@type": "Brand", "name": "Acme" },
  "offers": { "@type": "Offer", "price": "299.90", "priceCurrency": "BRL" }
}
```

## Preparação de conteúdo (opcional)

Quando a opção **Preparar conteúdo** está ligada, o título e a descrição passam pelos
plugins de conteúdo do Joomla (`content.prepare`) antes de irem ao schema, resolvendo
*shortcodes* de outras extensões. Isso tem custo de desempenho e é desligado por padrão.
