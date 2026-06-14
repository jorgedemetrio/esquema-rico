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

### Corrigido
- **Meta keywords** voltam a ser emitidas nas páginas de artigo (`plg_content_esquemaricokeywords`),
  a partir das palavras-chave e tags da matéria (sem sobrescrever uma já existente).
- Arquivos de idioma do plugin de sistema (`plg_system_esquemarico`), que estavam ausentes
  e fariam a extensão instalar com rótulos quebrados; adicionado também en-GB para a
  biblioteca compartilhada.

### Pendente
- Backend completo (CRUD de itens, campos de mapeamento, configurações globais).
- Demais integrações (e-commerce, eventos, etc.).
- Testes automatizados e validação no Teste de Resultados Avançados do Google.

Ver `TODO.md` para o detalhamento por fase.
