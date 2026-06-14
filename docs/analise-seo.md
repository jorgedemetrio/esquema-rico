# Análise de SEO do Esquema Rico

Avaliação do trabalho de SEO entregue pelo componente, com pontos fortes, lacunas e o que
foi adicionado nesta rodada.

## Veredito resumido

O componente faz **bem** o que se propõe — **dados estruturados (JSON-LD)** — que é um
pilar de SEO técnico moderno (elegibilidade a Resultados Avançados). É uma base sólida e
bem arquitetada, mas o SEO de um site não se resume a structured data. Faltava
**descoberta/rastreabilidade** (sitemap), agora adicionada.

**Nota geral: bom (com a lacuna de sitemap fechada).**

## Pontos fortes (o que está bem feito)

1. **Cobertura ampla de Schema.org** — 18 tipos (Article, Product, Event, FAQ, HowTo,
   LocalBusiness, Organization, Person, Recipe, Review, Video, JobPosting, etc.) + esquemas
   globais (WebSite, Logo, Perfis Sociais, Sitelinks Searchbox, Breadcrumbs). Isso cobre os
   principais formatos de Rich Results do Google.
2. **JSON-LD (formato recomendado)** — em vez de microdados, alinhado à recomendação atual
   do Google.
3. **Higiene técnica correta**:
   - datas em **ISO 8601** com fuso do site;
   - **URLs absolutas** (inclusive limpeza do `#joomlaImage://` do campo de mídia);
   - remoção de propriedades vazias (preservando o zero);
   - limpeza de HTML/`<script>` nas propriedades.
4. **Anti-duplicação** — remove microdados/JSON-LD de templates/extensões que conflitem
   (BreadcrumbList, Article, Product…), evitando o problema comum de schemas duplicados.
   Marca os próprios blocos com `data-type="esr"` para não removê-los.
5. **Controle de snippet de robôs** — injeta `max-snippet:-1, max-image-preview:large,
   max-video-preview:-1` (respeitando `noindex`/`nosnippet` já existentes), liberando ao
   Google trechos e imagens grandes nos resultados.
6. **Condições de publicação** — controla onde cada schema aparece (menu, idioma,
   dispositivo, grupo, nível de acesso), evitando marcação em páginas erradas.
7. **Multilíngue** — itens por idioma; o frontend filtra pelo idioma ativo.
8. **Desempenho/cache** — memoização por requisição (campos personalizados, leitura de XML).

## Lacunas (o que faltava / a melhorar)

| Lacuna | Status |
|--------|--------|
| **Sitemap XML** (descoberta de URLs pelo Google) | ✅ Adicionado (ver [12-sitemap.md](funcional/12-sitemap.md)) |
| **Meta keywords** ausente nas páginas de artigo | ✅ Adicionado: plugin `plg_content_esquemaricokeywords` (ver [13](funcional/13-keywords-e-analise-seo.md)) |
| **Análise/score de SEO no editor** (estilo Yoast) | ✅ Adicionado: plugin `plg_content_esquemaricoseo` + `SeoAnalyzer` (ver [13](funcional/13-keywords-e-analise-seo.md)) |
| **Canonical / hreflang** automáticos | ⬜ Pendente (hoje fica a cargo do template/SEF do Joomla) |
| **Meta tags** (title/description/OG/Twitter Cards) | ⬜ Fora do escopo (o foco é structured data); o Joomla já cobre o básico |
| **Validação automática** do JSON-LD contra o Google na CI | ⬜ Há testes do motor (`tests/`) e o guia de QA, mas a validação no Rich Results Test é manual |
| **robots.txt / IndexNow** | ⬜ Não tratado |
| **Imagens**: `width`/`height`/licença em `ImageObject` | 🔸 Parcial (usa `url`); poderia enriquecer |
| **Paginação/`prev`/`next`** e breadcrumbs em coleções | 🔸 Breadcrumbs ok; coleções não |

> Observação: várias dessas lacunas (canonical, meta, robots) são **responsabilidade do
> core do Joomla / template**, não necessariamente do componente de dados estruturados.
> Mantê-las fora do escopo é uma decisão de produto defensável; ficam registradas como
> oportunidades.

## O que foi adicionado nesta rodada (sitemaps)

Quatro sitemaps + índice, com **peso por recência** da data de modificação (recentes pesam
mais que antigos), exatamente como solicitado:

- **Conteúdo** (artigos), **Categorias**, **Menu** e **Tags** — mesma regra de priorização.
- `priority` por decaimento linear sobre uma janela configurável; `changefreq` derivado da
  idade; `lastmod` em W3C/ISO 8601.
- Núcleos puros (`SitemapPriority`, `SitemapBuilder`) cobertos por **testes unitários**.

Detalhes e endereços em [funcional/12-sitemap.md](funcional/12-sitemap.md).

## Recomendações de próximos passos (SEO)

1. **Enviar o índice do sitemap** ao Google Search Console e ao `robots.txt`
   (`Sitemap: https://seusite/index.php?option=com_esquemarico&view=sitemap&format=xml`).
2. Avaliar **canonical/hreflang** automáticos por integração (quando o template não cobrir).
3. Enriquecer `ImageObject` com `width`/`height` quando disponíveis.
4. Adicionar **IndexNow** (ping a Bing/Yandex) ao salvar conteúdo (opcional).
5. Manter a validação periódica dos exemplos no Teste de Resultados Avançados (ver
   [funcional/11-validacao-e-qa.md](funcional/11-validacao-e-qa.md)).
