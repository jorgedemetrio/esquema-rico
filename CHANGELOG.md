# Histórico de mudanças — Esquema Rico

Todas as mudanças relevantes deste projeto são documentadas aqui.
O formato segue, de forma simplificada, o [Keep a Changelog](https://keepachangelog.com/pt-BR/).

## [Não lançado]

### Adicionado
- Documentação funcional completa em `docs/funcional/` (visão geral, arquitetura,
  tipos de schema, esquemas globais, integrações, mapeamento/SmartTags, condições,
  fluxo de renderização, modelo de dados, compatibilidade e glossário).
- Plano de implementação detalhado (`TODO.md`) e `README.md`.
- Biblioteca compartilhada `Esquemarico\Core` (`plg_system_esquemaricocore`):
  cache, detecção de extensões, condições de publicação (Menu, Idioma), SmartTags
  (usuário, página, site, data/hora) e utilitários.
- Componente `com_esquemarico`: manifesto, script de instalação, tabelas
  (`#__esquemarico`, `#__esquemarico_config`), motor de geração de JSON-LD com
  todos os construtores de schema, classes de preparação de tipo, helpers
  (mapeamento, limpeza de schema, breadcrumbs), classes-base de integração e
  painel administrativo inicial.
- Plugin de sistema `plg_system_esquemarico`: geração dos esquemas globais,
  orquestração das integrações, injeção do JSON-LD e remoção de duplicados
  (arquitetura moderna via `SubscriberInterface`).
- Integração com o conteúdo nativo do Joomla (`plg_esquemarico_content`).
- Script de build (`build/build.sh`) que gera os ZIPs instaláveis.
- Backend de Itens completo: lista (busca/filtros/paginação), edição com abas
  (Geral / Mapeamento / Condições), campos de mapeamento (`MapField` e variantes),
  campos de tipo e integração, e configurações globais (`ConfigModel`/`ConfigController`).
- Formulários dos 18 tipos de conteúdo (`forms/contenttypes/*.xml`), alinhados ao
  motor de geração, mais a especialização `Custom_Code` (preserva o JSON-LD bruto).
- Integrações adicionais: `menus` (marcação manual), `k2`, `virtuemart`, `hikashop`,
  `jevents`, `dpcalendar` — cobrindo os padrões artigo/produto/evento.
- Qualidade: scripts de lint (`build/lint.*`), config PHPStan (`phpstan.neon.dist`),
  PSR-12 (`phpcs.xml.dist`), `.editorconfig`, suíte de testes do motor JSON-LD (`tests/`),
  guia de validação/QA e guia do usuário (`docs/guia-do-usuario.md`).
- Condições de publicação adicionais (Componente, Dispositivo, Grupo de Usuário, Nível de
  Acesso); campos de subtipo (`BusinessTypesField`, `OrganizationTypesField`) e o campo de
  horário de funcionamento (`OpeningHoursField`).

- CI/CD e governança focados em Joomla 6: workflows `build.yml`, `validacao-pre-master.yml`
  e `deploy.yml` (FTPS + XML de atualização Joomla 6); `deploy.sh`; gate
  `.claude/skills/validacao-pre-producao/` (valida convenções J6, i18n e SQL MySQL 5);
  `phpmd.xml`; e os arquivos de instrução `CLAUDE.md`, `AGENTS.md`, `GEMINI.md` e
  `.github/copilot-instructions.md`.

- **Sitemaps XML** (frontend do `com_esquemarico`): índice + sitemaps de conteúdo,
  categorias, menu e tags, com `priority` ponderada pela **recência** da data de
  modificação (decaimento linear configurável) e `changefreq` derivado da idade. Núcleos
  puros (`SitemapPriority`, `SitemapBuilder`) com testes unitários. Aba de configuração e
  documentação (`docs/funcional/12-sitemap.md`, `docs/analise-seo.md`).

- **Análise de SEO estilo Yoast** (`plg_content_esquemaricoseo` + `SeoAnalyzer`): pontuação
  0–100 e verificações (título, meta descrição, keywords, volume, palavra-chave de foco no
  título/URL/descrição/texto/subtítulo, densidade, imagens com alt, links, subtítulos) no
  editor de artigos. Núcleo `SeoAnalyzer` com testes unitários.

#### Lote de SEO (2026-06)
- **Open Graph e Twitter Cards** (`plg_system_esquemarico` + config "opengraph"): gera as
  meta tags `og:*` e `twitter:*` (locale, type, title, description, url, site_name, image,
  card, site) a partir dos metadados da página e da identidade do site, sem sobrescrever
  tags já presentes.
- **Canonical e controle de indexação** (config "indexing"): canonical autorreferente quando
  ausente (opt-in) e `noindex, follow` em resultados de busca (com_search/com_finder) e,
  opcionalmente, em páginas paginadas.
- **Sitemap de imagens**: namespace `image` e `<image:image>` com as imagens intro/fulltext
  dos artigos no sitemap de conteúdo (teste unitário incluído).
- **Análise de legibilidade** (estilo Yoast, `SeoAnalyzer::readability()`): pontuação
  independente com índice Flesch adaptado ao PT-BR, proporção de frases longas, parágrafos
  longos e distribuição de subtítulos; segundo medidor no painel.
- **Template de `<title>`** (config "titles"): modelo configurável com `%title%`,
  `%sitename%` e `%sep%`, e título próprio para a página inicial (opt-in).
- **Consolidação em `@graph`** (config "jsonld", opt-in): junta os blocos JSON-LD da página
  num único `<script>` com `@graph`, preservando o `@id` de cada nó.
- **Novos tipos de schema**: `QAPage` (pergunta única com `acceptedAnswer`/`suggestedAnswer`)
  e `SoftwareApplication` (`offers` + `aggregateRating`), com formulários, idiomas e testes.
- **Preview e validação de Rich Results no editor** (`SchemaPreviewField`): gera o JSON-LD do
  item sobre os dados salvos, faz lint estrutural (`@context`/`@type`) e mostra o JSON, com
  atalho para o Teste de Resultados Avançados do Google.
- **Auto meta description** (`plg_content_esquemaricokeywords`): quando o artigo não tem
  descrição, gera uma a partir do início do conteúdo (~160 caracteres) — também alimenta o
  `og:description`. Opt-out por parâmetro.
- **Atalho de sitemap/robots** (`SitemapUrlField`): mostra a URL pública do índice de sitemap
  e a linha `Sitemap:` pronta para colar no `robots.txt`.

### Corrigido
- **Meta keywords** voltam a ser emitidas nas páginas de artigo (`plg_content_esquemaricokeywords`),
  a partir das palavras-chave e tags da matéria (sem sobrescrever uma já existente).
- Arquivos de idioma do plugin de sistema (`plg_system_esquemarico`), que estavam ausentes
  e fariam a extensão instalar com rótulos quebrados; adicionado também en-GB para a
  biblioteca compartilhada.

### Pendente
- Sub-itens de SEO de menor prioridade: `hreflang`, sitemaps de vídeo/notícias, `gzip` e
  ping aos buscadores no sitemap; preview de snippet ao vivo no editor de artigos.
- Redirecionamentos 404 → 301: usar o componente nativo do Joomla (`com_redirect`) em vez de
  reimplementar.
- Demais integrações e validação contínua no Teste de Resultados Avançados do Google.

Ver `TODO.md` para o detalhamento por fase.
