# TODO — Plano de implementação do **Esquema Rico**

Extensão Joomla de dados estruturados (JSON-LD / Schema.org), alvo **Joomla 6 + PHP 8.3 +
MySQL 5**. Marca **Esquema Rico** (ESR). Veja a documentação funcional em
[`docs/funcional/`](docs/funcional/).

Legenda: `[ ]` pendente · `[~]` em andamento · `[x]` concluído · 🔑 caminho crítico.

---

## Visão de entrega (fases)

| Fase | Objetivo | Estado |
|------|----------|--------|
| 0 | Estrutura do repositório e documentação | `[x]` |
| 1 | Biblioteca compartilhada `Esquemarico\Core` | `[~]` |
| 2 | Componente `com_esquemarico` — esqueleto + dados | `[~]` |
| 3 | Motor JSON-LD + tipos de schema | `[~]` |
| 4 | Plugin de sistema `plg_system_esquemarico` | `[~]` |
| 5 | Integração de conteúdo `plg_esquemarico_content` | `[~]` |
| 6 | Backend completo (telas, campos, formulários) | `[~]` |
| 7 | Condições de publicação (UI + motor) | `[~]` |
| 8 | Esquemas globais (config completa) | `[~]` |
| 9 | Demais integrações (e-commerce, eventos, …) | `[~]` |
| 10 | Empacotamento, instalação, atualização | `[~]` |
| 11 | Qualidade: testes, validação Google, i18n | `[~]` |

---

## Fase 0 — Estrutura e documentação `[x]`

- [x] Definir marca/nomenclatura (Esquema Rico / ESR).
- [x] Criar árvore de diretórios `src/` (pacote, componente, plugins).
- [x] Documentação funcional completa em `docs/funcional/` (00–10 + índice).
- [x] `README.md` do projeto.
- [x] `TODO.md` (este arquivo).
- [ ] `CHANGELOG.md` inicial.
- [ ] `.gitignore` (artefatos de build, `dist/`).
- [ ] `LICENSE` (GPL-3.0, como toda extensão Joomla).

## Fase 1 — Biblioteca `Esquemarico\Core` (`plg_system_esquemaricocore`) `[~]` 🔑

- [x] `Core/autoload.php` — registro PSR-4 do namespace `Esquemarico\Core`.
- [x] `esquemaricocore.xml` — manifesto do plugin de sistema.
- [x] `services/provider.php` — DI (plugin sem comportamento, só biblioteca).
- [x] `src/Extension/EsquemaRicoCore.php` — classe mínima do plugin.
- [x] `Core/Cache.php` — `has/get/set` (memo em memória por request).
- [x] `Core/Extension.php` — `isInstalled`, `isEnabled`, `componentIsEnabled`,
      `getVersion` (consulta `#__extensions` + cache estático).
- [x] `Core/Functions.php` — `strpos_arr`, `isFeed`, `dateToUTC`,
      `array_splice_assoc`, `makeArray`, helpers de string/data.
- [x] `Core/Conditions/Condition.php` — classe-base de condição (`pass`, `passByOperator`).
- [x] `Core/Conditions/ConditionsHelper.php` — `passSets/passSet` (E/OU).
- [x] `Core/Conditions/Conditions/Menu.php` — condição de menu (com filhos).
- [x] `Core/Conditions/Conditions/Idioma.php` — condição de idioma.
- [ ] `Core/Conditions/Conditions/{GrupoUsuario,IdUsuario,NivelAcesso}.php`.
- [ ] `Core/Conditions/Conditions/{Data,DiaSemana,Mes,Hora}.php`.
- [ ] `Core/Conditions/Conditions/{Dispositivo,Componente}.php`.
- [x] `Core/SmartTags/SmartTags.php` — coletor + substituidor `{ns.chave}`.
- [x] `Core/SmartTags/Tags/Usuario.php`, `Pagina.php`, `Site.php`, `DataHora.php`.
- [ ] Testes unitários da biblioteca (datas, condições, smarttags).

## Fase 2 — Componente: esqueleto + dados (`com_esquemarico`) `[~]` 🔑

- [x] `esquemarico.xml` — manifesto (namespace, admin/site, media, SQL, menu admin).
- [x] `script.php` — script de instalação (preflight de versões, mensagens).
- [x] `admin/services/provider.php` — registro do componente no DI.
- [x] `admin/src/Extension/EsquemaricoComponent.php` — `BootableExtensionInterface`.
- [x] `admin/sql/install.mysql.utf8.sql` — tabelas `#__esquemarico(_config)`.
- [x] `admin/sql/uninstall.mysql.utf8.sql`.
- [ ] `admin/sql/updates/mysql/1.0.0.sql` — baseline de updates.
- [x] `admin/src/Table/ItemTable.php` — tabela do item de marcação.
- [x] `admin/src/Table/ConfigTable.php` — tabela de configuração.
- [ ] Regras de acesso `access.xml`.

## Fase 3 — Motor JSON-LD + tipos de schema `[~]` 🔑

- [x] `admin/src/Engine/GeradorJsonLd.php` — construtores por tipo + limpeza + serialização.
  - [x] Artigo, Produto, Evento, Receita, FAQ, HowTo
  - [x] Negócio Local, Organização, Pessoa, Curso, Livro, Filme
  - [x] Avaliação, Vaga, Serviço, Vídeo, Checagem de Fatos, Código Personalizado
  - [x] Globais: WebSite, Logo, Perfis Sociais, Breadcrumbs
- [x] `admin/src/Schema/Base.php` — preparação comum (datas, URLs, limpeza, rename).
- [x] `admin/src/Schema/SchemaHelper.php` — *factory* de tipo.
- [x] `admin/src/Schema/Tipos/Article.php` e `Custom_Code.php` (especializações).
- [ ] Demais `admin/src/Schema/Tipos/*.php` — opcionais (os tipos funcionam via `Base`;
      especializar só quando precisar de normalização específica).
- [x] `admin/src/Helper/EsquemaRicoHelper.php` — breadcrumbs, params, site, datas, URLs.
- [x] `admin/src/Helper/MappingOptions.php` — opções e resolução de modos.
- [x] `admin/src/Helper/SchemaCleaner.php` — remoção de JSON-LD/microdados.
- [x] `admin/src/Helper/Apps.php` — *boot* de plugins de integração.
- [x] `admin/src/Plugin/{PluginBase,PluginBaseArtigo,PluginBaseProduto,PluginBaseEvento}.php`.

## Fase 4 — Plugin de sistema `plg_system_esquemarico` `[~]` 🔑

- [x] `esquemarico.xml` — manifesto (grupo system, config, updateserver).
- [x] `services/provider.php`.
- [x] `src/Extension/Esquemarico.php` — `SubscriberInterface`:
  - [x] `onBeforeCompileHead` — esquemas globais + injeção no `<head>`.
  - [x] `onAfterRender` — remoção de duplicados + injeção tardia + robôs + debug.
  - [x] `onContentPrepareForm` — roteia para edição rápida.
  - [x] `onEsquemaRicoBeforeRender` — orquestra integrações.
- [ ] Layout do painel de debug.

## Fase 5 — Integração de conteúdo `plg_esquemarico_content` `[~]`

- [x] `content.xml` — manifesto.
- [x] `services/provider.php`.
- [x] `src/Extension/Content.php` — `viewArticle`, `passContext`, `onMapOptions`,
      `onContentPrepareForm`, campos personalizados.
- [x] `form/assignments.xml` — condições para artigos/categorias.
- [x] `form/form.xml` — edição rápida no editor de artigos.
- [x] `language/pt-BR`, `language/en-GB`.

## Fase 6 — Backend completo `[~]`

- [x] `admin/src/Controller/{DisplayController,ItemController,ItemsController,ConfigController}.php`.
- [x] `admin/src/Model/{ItemsModel,ItemModel,ConfigModel}.php`.
- [x] `admin/src/View/{Items,Item,Config,Painel}/HtmlView.php` + `tmpl/`.
- [x] Campos personalizados em `admin/src/Field/`:
  - [x] `ContentTypesField` (lista de tipos de schema).
  - [x] `IntegrationsField` (lista de integrações instaladas).
  - [x] `MapField`, `MapImageField`, `MapDateField`, `MapUserField` (seletor de mapeamento).
  - [x] `OpeningHoursField` (horário de funcionamento — completa o Negócio Local).
  - [x] `BusinessTypesField` (subtipos de Negócio Local) e `OrganizationTypesField`.
  - [x] `FastEditField` v1 — painel na aba "Esquema Rico" do editor de artigos com acesso
        de um clique a criar/gerenciar itens.
  - [ ] `FastEditField` v2 — editor totalmente inline (AJAX para carregar os campos do tipo
        e salvar o item junto com o artigo).
  - [ ] Aprimorar `MapImage/MapDate/MapUser` (seletor de mídia/calendário/usuário no modo fixo).
- [x] `forms/item.xml`, `forms/filter_items.xml`, `forms/config.xml`.
- [x] `forms/contenttypes/*.xml` para os 18 tipos (todos criados, alinhados ao gerador).
- [x] Painel (dashboard) com atalhos e status do plugin.
- [x] Toolbar, paginação, busca, filtros, ordenação na lista de itens.
- [ ] Testador embutido no painel (validação JSON-LD).
- [ ] Ativos JS/CSS em `media/` via `joomla.asset.json` (hoje o JS do MapField é inline).
- [ ] Verificar filtragem de valores de array do `MapField` na validação do formulário.

## Fase 7 — Condições de publicação (UI + motor) `[~]`

- [x] Integrar `ConditionsHelper` no `getSnippets` do `PluginBase` (avaliação por item).
- [x] UI inicial de condições (menu, idioma) via `form/assignments.xml` da integração.
- [x] Condições no motor: Menu, Idioma, Componente, Dispositivo, GrupoUsuario, NivelAcesso
      (registradas no `ConditionsHelper`; dispositivo/grupo na UI do `content`).
- [ ] Construtor de condições mais rico (grupos AND/OR, repetível) + geo/data/hora.
- [ ] Migração/normalização do formato de `assignments` nos `params`.

## Fase 8 — Esquemas globais (config completa) `[~]`

- [x] `forms/config.xml` com as seções: Nome do Site, Breadcrumbs, Sitelinks, Logo,
      Perfis Sociais, Negócio Local e Avançado (remover microdados, código personalizado).
- [x] `ConfigModel` + `ConfigController` persistindo a linha `config`.
- [x] Renderizar cada esquema global no plugin de sistema (Fase 4).
- [ ] Adicionar abas de Tipos de Conteúdo e Integrações (ativar/desativar) na config.

## Fase 9 — Demais integrações `[~]`

Prioridade (alto → baixo valor de mercado):

- [x] `plg_esquemarico_menus` (marcação manual — baixa complexidade, alto valor).
- [x] `plg_esquemarico_k2` (artigos K2).
- [x] `plg_esquemarico_virtuemart` (produto — exercita `PluginBaseProduto`).
- [x] `plg_esquemarico_hikashop` (produto).
- [ ] `plg_esquemarico_j2store` (produto).
- [x] `plg_esquemarico_jevents` (evento — exercita `PluginBaseEvento`).
- [x] `plg_esquemarico_dpcalendar` (evento).
- [ ] `plg_esquemarico_easyblog` (artigo).
- [ ] `plg_esquemarico_sppagebuilder` (artigo/página).
- [ ] Demais (`jshopping`, `eshop`, `jcalpro`, `icagenda`, `djevents`, `eventbooking`,
      `rseventspro`, `sobipro`, `jbusinessdirectory`, `djclassifieds`, `djcatalog2`,
      `quix`, `gridbox`, `jreviews`, `zoo`, `rsblog`).

> Cada integração reusa uma classe-base e precisa apenas de `view<Nome>()`,
> `assignments.xml` e idiomas — esforço marginal por plugin é baixo.

## Fase 10 — Empacotamento e instalação `[~]`

- [x] `pkg_esquemarico/pkg_esquemarico.xml` — manifesto do pacote.
- [ ] `pkg_esquemarico/script.php` — instala biblioteca → componente → plugins, na ordem;
      habilita plugins automaticamente; checa versões mínimas.
- [x] Script de build (`build/build.sh`) que monta os ZIPs em `dist/` (aceita versão).
- [x] Pipeline CI/CD (`.github/workflows/`): `build.yml` (lint+estática+testes),
      `validacao-pre-master.yml` (gate) e `deploy.yml` (FTPS + XML de atualização Joomla 6).
- [x] Script de deploy (`.github/scripts/deploy.sh`) + `updateserver` XML auto-hospedado.
- [x] Gate de validação focado em Joomla 6 (`.claude/skills/validacao-pre-producao/`).
- [ ] Verificar instalação limpa em Joomla 6 + atualização a partir de versão anterior (precisa de ambiente).

## Fase 11 — Qualidade `[~]`

- [x] Scripts de lint (`build/lint.sh` / `build/lint.ps1`) para `php -l`.
- [x] Config base de análise estática (`phpstan.neon.dist`).
- [x] Testes unitários do motor JSON-LD (`tests/`, cobrindo os tipos principais).
- [x] Guia de validação/QA (`docs/funcional/11-validacao-e-qa.md`) com roteiro manual e
      passos de validação no Google.
- [ ] Executar de fato lint/PHPStan/PHPUnit (requer PHP no ambiente).
- [x] Padronização de código (PSR-12): `phpcs.xml.dist` + `.editorconfig`.
- [x] Auditoria de idiomas: pt-BR do componente completo (sem chaves faltantes);
      arquivos de idioma de todos os manifestos verificados como existentes; en-GB do
      plugin de sistema e da biblioteca criados.
- [x] Documentação de usuário final ([`docs/guia-do-usuario.md`](docs/guia-do-usuario.md)).
- [ ] Paridade completa pt-BR ↔ en-GB do componente (en-GB tem só o essencial).
- [ ] Acessibilidade/desempenho: medir impacto do `onAfterRender` em páginas grandes.

---

## Fase 12 — SEO: Sitemaps XML `[~]`

- [x] Núcleos puros `SitemapPriority` (peso por recência) e `SitemapBuilder` (XML), testados.
- [x] `SitemapModel` (conteúdo, categorias, menu, tags) + `DisplayController` de site.
- [x] Índice + 4 sub-sitemaps; `priority` por recência, `changefreq` por idade, `lastmod`.
- [x] Aba de configuração (janela/min/max) + documentação (`12-sitemap.md`, `analise-seo.md`).
- [ ] (Opcional) `robots.txt`/IndexNow, canonical/hreflang, `width`/`height` em imagens.

## Fase 13 — SEO de conteúdo `[~]`

- [x] `plg_content_esquemaricokeywords` — corrige a emissão da meta keywords (metakey + tags).
- [x] `plg_content_esquemaricoseo` + `SeoAnalyzer` — análise/score de SEO estilo Yoast no
      editor (regras de SEO conhecidas), com testes unitários.
- [x] Documentação (`13-keywords-e-analise-seo.md`) e atualização da análise de SEO.
- [ ] (Opcional) Análise live por JS (recalcular a nota a cada tecla, sem salvar).

## Decisões técnicas registradas

1. **Marca/identificadores**: Esquema Rico, `com_esquemarico`, grupo de plugins
   `esquemarico`, namespace `Joomla\Component\Esquemarico`, biblioteca `Esquemarico\Core`,
   tabelas `#__esquemarico(_config)`, constantes `ESR_*`.
2. **Sem dependência do produto original**: nenhum nome, namespace, marca ou URL de
   terceiros aparece no código. SmartTags do payload usam o prefixo neutro `gsd.item.`
   apenas como *namespace interno de dados* (não é marca); pode ser renomeado para
   `item.`/`esr.item.` numa rodada de polimento se desejado.
3. **Alvo único Joomla 5/6**: sem código de compatibilidade com Joomla 3/4.
4. **Persistência chave→params** para configurações globais (flexível, sem migrações de
   schema a cada nova opção).
5. **Motor JSON-LD isolado** e testável, sem dependências do ciclo de request.

## Riscos / pontos de atenção

- Volume de integrações (28) é grande; o núcleo foi desenhado para que cada uma seja
  barata, mas é trabalho recorrente.
- Campos personalizados de UI (seletor de mapeamento, horário de funcionamento) exigem
  JS próprio — atenção ao *build* de assets do Joomla 6.
- Remoção de duplicados via regex sobre o buffer pode ser sensível a HTML malformado;
  cobrir com casos de teste.
