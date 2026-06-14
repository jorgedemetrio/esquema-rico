# 13 — Meta keywords e Análise de SEO

Dois recursos de SEO de conteúdo entregues como **plugins de conteúdo** (grupo `content`)
do pacote Esquema Rico.

## 13.1 Correção da meta keywords (`plg_content_esquemaricokeywords`)

Em vários templates/versões o Joomla deixou de emitir `<meta name="keywords">` na página
do artigo. Este plugin **garante a emissão**, a partir das palavras-chave da matéria (e,
opcionalmente, das suas tags). O Google não usa mais essa tag, mas outros buscadores (ex.:
Bing/Yandex e buscadores internos) ainda a consideram.

**Como funciona:**
- Escuta `onContentPrepare` (evento de conteúdo). Atua apenas no **frontend** e na página
  de **um único artigo** (`com_content.article`, `view=article`).
- Coleta `metakey` do artigo + (se a opção estiver ligada) os títulos das **tags** do
  artigo (`#__contentitem_tag_map` → `#__tags`). Remove duplicados (case-insensitive).
- Define `keywords` no documento **apenas se ainda não houver** uma — não sobrescreve o
  que o template/Joomla já tenha emitido.

**Opção:** *Incluir as tags do artigo* (padrão: sim).

## 13.2 Análise de SEO estilo Yoast (`plg_content_esquemaricoseo`)

Adiciona ao **editor de artigos** uma **pontuação de SEO (0–100)** e uma lista de
verificações com semáforo (bom / regular / ruim), inspirada no Yoast SEO do WordPress.

**Onde aparece:** uma seção **"Análise SEO"** na aba **Opções** do artigo, com:
- um campo **Palavra-chave de foco** (gravado em `#__content.attribs`);
- o **painel** com o medidor de pontuação e a lista de verificações.

A análise autoritativa roda **no servidor** (classe `SeoAnalyzer`, testada) sobre os dados
salvos; é **recalculada ao salvar**. Um JS leve mostra contadores ao vivo (tamanho do
título e da meta descrição).

### Regras avaliadas (SEO conhecido)

Sempre:
| Verificação | Bom | Regular | Ruim |
|-------------|-----|---------|------|
| Tamanho do título | 40–60 | 30–70 | fora |
| Meta descrição | 120–160 | demais | ausente |
| Meta keywords | preenchida | — | vazia |
| Volume de conteúdo | ≥ 600 palavras | 300–599 | < 300 |
| Imagens com `alt` | todas | sem imagens | faltando |
| Links no conteúdo | ≥ 1 | — | nenhum |
| Subtítulos (H2–H6) | ≥ 1 | — | nenhum |

Com **palavra-chave de foco** definida:
| Verificação | Bom | Ruim/Regular |
|-------------|-----|--------------|
| FK no título | aparece | não aparece |
| FK na meta descrição | aparece | não aparece |
| FK na URL (alias) | aparece | não aparece |
| FK no início do texto | aparece | não aparece |
| Densidade da FK | 0,5–2,5% | fora (ruim) / 0–0,5 e 2,5–3,5% (regular) |
| FK em subtítulo | aparece | ausente (regular) |

### Pontuação

Cada verificação vale `bom = 1`, `regular = 0,5`, `ruim = 0`, com **pesos** (título, meta
descrição, volume, FK no título e densidade pesam o dobro). A nota final é a média
ponderada × 100. Classificação: **≥ 70 bom**, **≥ 45 regular**, **< 45 ruim**.

### Arquitetura

- `com_esquemarico/admin/src/Seo/SeoAnalyzer.php` — regras + pontuação (**puro**, testado em
  `tests/SeoAnalyzerTest.php`).
- `com_esquemarico/admin/src/Field/SeoAnalysisField.php` — renderiza o painel no editor.
- `com_esquemarico/media/{css/seo.css,js/seo.js}` — visual e contadores ao vivo.
- `plg_content_esquemaricoseo` — injeta a seção via `onContentPrepareForm`.

> Limitação conhecida: a análise live (recalcular a nota a cada tecla, como no Yoast) é uma
> evolução futura; hoje a nota completa é recalculada ao **salvar**, com contadores ao vivo
> nos campos de título/descrição.
