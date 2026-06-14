# 07 — Fluxo de renderização

Este documento descreve o pipeline completo, do request HTTP até a injeção do JSON-LD na
página. É o "como tudo se conecta".

## Visão geral

```
Request → Joomla monta a página → plg_system_esquemarico
   │
   ├─ (onBeforeCompileHead)  Esquemas globais + evento de integrações → injeta no <head>
   │
   └─ (onAfterRender)        Remoção de duplicados + injeção tardia opcional no <body>
```

## Pré-condições (guarda)

O plugin de sistema só age se **todas** forem verdadeiras:

1. Estamos no **frontend** (cliente `site`).
2. O documento é **HTML** (não JSON, feed, RSS, impressão).
3. A **biblioteca** `Esquemarico\Core` carregou.
4. O **componente** `com_esquemarico` está instalado e habilitado.
5. Não é um feed nem uma página de impressão.

Se qualquer uma falhar, o plugin não faz nada.

## Etapa 1 — Esquemas globais (`onBeforeCompileHead`)

O plugin monta, na ordem, os blocos globais (ver [03](03-esquemas-globais.md)):

1. WebSite (nome do site)
2. Logo
3. Perfis sociais
4. Negócio local
5. Código personalizado global
6. Breadcrumbs

Cada bloco só é gerado se habilitado e se a regra de localização permitir (home vs. todas
as páginas). Os blocos vazios são descartados.

## Etapa 2 — Itens de marcação (evento `onEsquemaRicoBeforeRender`)

O plugin dispara o evento `onEsquemaRicoBeforeRender`, passando o array de blocos por
referência. **Cada plugin de integração** responde:

1. **`passContext()`** — descarte rápido se o componente/contexto não for o desta
   integração.
2. **`getPayload()`** — resolve a *view* atual e chama `view<Nome>()`, obtendo o payload
   (dados brutos do item da página). Sem payload válido → aborta.
3. **`getSnippets()`** — busca no banco os itens de marcação desta integração que casam
   com a *view*, o idioma e o estado publicado.
4. Para cada item, avalia as **condições de publicação**; descarta os que não passam.
5. Para cada item aprovado, **`preparePayload()`**:
   - resolve os modos de mapeamento (`fixed`, `_custom_`, seletor CSS, …);
   - mescla o item ao payload;
   - substitui as **SmartTags**;
   - (opcional) prepara o conteúdo via plugins do Joomla;
   - entrega à **classe de preparação** do tipo (`Schema\Tipos\<Tipo>`), que normaliza
     datas (ISO 8601), URLs (absolutas), limpa HTML e renomeia propriedades.
6. O resultado é passado ao **motor** (`GeradorJsonLd`), que monta o array do schema.

## Etapa 3 — Geração do JSON-LD (`GeradorJsonLd`)

Para cada item preparado:

1. Seleciona o construtor `contentType<Tipo>()` correspondente.
2. Dispara `onEsquemaRicoSchemaBeforeGenerate` (permite a plugins ajustarem o array).
3. **Limpeza recursiva**: remove propriedades `null`, `false` e string vazia (preservando
   o zero), e descarta objetos órfãos que só contêm `@type`.
4. Prepende `@context: https://schema.org`.
5. Serializa com `json_encode` usando `JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT |
   JSON_UNESCAPED_UNICODE`. Erros de codificação são reportados no log.
6. Envolve em `<script type="application/ld+json" data-type="esr">…</script>`.

> O atributo `data-type="esr"` identifica os blocos da própria extensão, para que a
> remoção de duplicados **não** apague os nossos.

## Etapa 4 — Injeção

Dois modos, conforme a opção **Aguardar renderização da página**:

- **Desligado (padrão)**: em `onBeforeCompileHead`, os blocos são adicionados ao documento
  via `addCustomTag` (entram no `<head>`).
- **Ligado**: em `onAfterRender`, os blocos são inseridos antes de `</body>` (ou ao final,
  se não houver `</body>`). Útil quando o conteúdo só fica disponível tardiamente.

Quando a opção **Minificar JSON** está ligada, o espaço em branco é colapsado antes da
injeção.

## Etapa 5 — Remoção de duplicados (`onAfterRender`)

Para evitar schemas conflitantes de templates/extensões:

- **JSON-LD de terceiros**: localiza todos os `<script type="application/ld+json">` que
  **não** sejam nossos (`data-type="esr"`) e, se corresponderem a um `@type` marcado para
  remoção, os apaga.
- **Microdados**: remove atributos `itemscope`/`itemtype`/`itemprop` de tipos
  selecionados, com regras extras para `Article`, `Product`, `Event`, `BreadcrumbList` e
  `AggregateRating`.

A lista de tipos a remover é configurável (por padrão remove `BreadcrumbList`). As
integrações de artigo também removem automaticamente os schemas `Article`/`BlogPosting`/
`NewsArticle` que o template gerar para a página atual.

## Etapa 6 — Controle de robôs

O plugin adiciona à meta `robots` os valores `max-snippet:-1, max-image-preview:large,
max-video-preview:-1` (a menos que já haja `noindex`/`nosnippet`/`max-`), liberando ao
Google o uso de trechos e imagens grandes nos resultados.

## Etapa 7 — Depuração

Com o modo **debug** ligado e o usuário sendo administrador, o plugin imprime ao final da
página um painel com todas as mensagens de log coletadas (itens encontrados, condições
avaliadas, markup gerado), facilitando entender o resultado.

## Diagrama de sequência (resumo)

```
Visitante
   │  GET /produto
   ▼
Joomla (frontend)
   │  onBeforeCompileHead
   ▼
plg_system_esquemarico
   │  monta esquemas globais
   │  dispara onEsquemaRicoBeforeRender ───────────────┐
   │                                                   ▼
   │                                       plg_esquemarico_content
   │                                          passContext / getPayload
   │                                          getSnippets / condições
   │                                          preparePayload (mapping+SmartTags)
   │                                          Schema\Tipos\<Tipo> (normaliza)
   │                                          GeradorJsonLd (monta+serializa)
   │  ◀───────────────────────────────────── devolve blocos JSON-LD
   │  injeta no <head>
   ▼
onAfterRender → remove duplicados, injeção tardia, robôs, debug
   ▼
HTML final com <script type="application/ld+json" data-type="esr">…</script>
```
