# Esquema Rico — Documentação Funcional

**Esquema Rico** é uma extensão para Joomla que gera **dados estruturados** no formato
[JSON-LD](https://json-ld.org/) seguindo o vocabulário [Schema.org](https://schema.org),
ajudando o seu site a se qualificar para os **Resultados Avançados** (*Rich Results*) da
Pesquisa Google.

Esta pasta contém a documentação **funcional** do produto: o que ele faz, como cada parte
funciona e quais regras de negócio regem a geração da marcação. Ela é independente de
detalhes de implementação (esses ficam no código e no `TODO.md`).

## Índice

| # | Documento | Conteúdo |
|---|-----------|----------|
| 00 | [Visão geral](00-visao-geral.md) | Objetivo, público-alvo, conceitos centrais e benefícios |
| 01 | [Arquitetura](01-arquitetura.md) | Componentes do produto (componente, plugin de sistema, biblioteca, integrações) |
| 02 | [Tipos de schema](02-tipos-de-schema.md) | Os ~19 tipos de conteúdo suportados e suas propriedades Schema.org |
| 03 | [Esquemas globais](03-esquemas-globais.md) | WebSite, Logo, Perfis Sociais, Negócio Local e Breadcrumbs |
| 04 | [Integrações](04-integracoes.md) | Como os plugins de integração mapeiam conteúdo de terceiros |
| 05 | [Mapeamento e SmartTags](05-mapeamento-e-smarttags.md) | Como os campos do schema são preenchidos dinamicamente |
| 06 | [Condições de publicação](06-condicoes-publicacao.md) | Regras que decidem em quais páginas a marcação aparece |
| 07 | [Fluxo de renderização](07-fluxo-de-renderizacao.md) | O pipeline completo, do request à injeção do JSON-LD |
| 08 | [Modelo de dados](08-modelo-de-dados.md) | Tabelas, colunas e formato dos parâmetros |
| 09 | [Compatibilidade](09-compatibilidade.md) | Requisitos de Joomla 6, PHP 8.3 e MySQL 5 |
| 10 | [Glossário](10-glossario.md) | Termos usados ao longo da documentação |
| 11 | [Validação e QA](11-validacao-e-qa.md) | Lint, testes, build, instalação no Joomla 6 e validação no Google |
| 12 | [Sitemap XML](12-sitemap.md) | Sitemaps de conteúdo, categorias, menu e tags, com peso por recência |
| 13 | [Meta keywords e Análise de SEO](13-keywords-e-analise-seo.md) | Correção da meta keywords e pontuação de SEO estilo Yoast no editor |

> Veja também a [Análise de SEO](../analise-seo.md) do componente.

## Convenções

- **ESR** é o prefixo de código da extensão (constantes de idioma `ESR_*`, namespace
  `Joomla\Component\Esquemarico`, biblioteca compartilhada `Esquemarico\Core`).
- Todo o produto é em **português (pt-BR)**, com fallback para **inglês (en-GB)**.
- Referências a "a Pesquisa" significam o mecanismo de busca do Google, principal
  consumidor desses dados, embora o JSON-LD seja entendido por qualquer rastreador.
