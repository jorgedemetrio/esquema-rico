# Esquema Rico

> Dados estruturados (JSON-LD / Schema.org) para Joomla — melhore a aparência do seu site
> nos **Resultados Avançados** da Pesquisa Google.

**Esquema Rico** adiciona marcação `application/ld+json` às páginas do seu site Joomla,
descrevendo o conteúdo (artigos, produtos, eventos, receitas, FAQs, negócios locais,
organizações e muito mais) de forma que o Google possa exibir resultados enriquecidos:
estrelas de avaliação, preço e disponibilidade, perguntas frequentes, datas de eventos,
trilha de navegação, caixa de pesquisa do site e painel de conhecimento.

- **Alvo**: Joomla 6 · PHP 8.3 · MySQL 5
- **Idioma**: português (pt-BR), com inglês (en-GB) como *fallback*
- **Licença**: GPL-3.0-or-later

---

## Recursos

- **18+ tipos de schema**: Artigo, Produto, Evento, Receita, FAQ, Como Fazer, Negócio
  Local, Organização, Pessoa, Curso, Livro, Filme, Avaliação, Vaga de Emprego, Serviço,
  Vídeo, Checagem de Fatos e Código Personalizado.
- **Esquemas globais** prontos: Nome do Site, Caixa de Pesquisa de Sitelinks, Logo,
  Perfis Sociais, Negócio Local e Breadcrumbs.
- **Mapeamento dinâmico** de campos com **SmartTags** (`{gsd.item.headline}`,
  `{user.name}`, …) e modos fixo / personalizado / seletor CSS.
- **Condições de publicação**: controle por menu, idioma, grupo de usuário, data,
  dispositivo e mais.
- **Integrações** com o conteúdo nativo do Joomla e (planejado) com VirtueMart, HikaShop,
  J2Store, JEvents, DPCalendar, K2, EasyBlog, SP Page Builder, entre outros.
- **Remoção de duplicados**: elimina microdados/JSON-LD conflitantes de templates e
  extensões, evitando schemas repetidos.
- **Edição rápida** embutida no editor de artigos e no gerenciador de menus.
- **Sitemaps XML** (conteúdo, categorias, menu e tags) com **peso por recência** da data
  de modificação — recém-alterados pesam mais. Ver [`docs/funcional/12-sitemap.md`](docs/funcional/12-sitemap.md).
- **Correção da meta keywords** nas páginas de artigo e **análise de SEO estilo Yoast** (com
  pontuação) no editor. Ver [`docs/funcional/13-keywords-e-analise-seo.md`](docs/funcional/13-keywords-e-analise-seo.md).
- **Modo de depuração** com painel de diagnóstico.

## Arquitetura

A extensão é um **pacote** com quatro artefatos:

| Artefato | Papel |
|----------|-------|
| `com_esquemarico` | Componente: backend, motor JSON-LD e tipos de schema |
| `plg_system_esquemarico` | Plugin de sistema: injeta a marcação e gera esquemas globais |
| `plg_system_esquemaricocore` | Biblioteca compartilhada (`Esquemarico\Core`): cache, condições, SmartTags, utilitários |
| `plg_esquemarico_*` | Plugins de integração: leem dados de cada fonte |

Detalhes completos em [`docs/funcional/01-arquitetura.md`](docs/funcional/01-arquitetura.md).

> Para usar a extensão no dia a dia, veja o [Guia do usuário](docs/guia-do-usuario.md).

## Estrutura do repositório

```
.
├── README.md
├── TODO.md                      ← plano de implementação detalhado
├── docs/
│   └── funcional/               ← documentação funcional (00–10)
└── src/
    ├── pkg_esquemarico/         ← manifesto do pacote
    ├── com_esquemarico/         ← componente (admin + site + media)
    ├── plg_system_esquemarico/  ← plugin de sistema (renderização)
    ├── plg_system_esquemaricocore/  ← biblioteca compartilhada
    └── plg_esquemarico_content/ ← integração com o conteúdo nativo
```

## Como funciona (resumo)

1. O usuário configura os **esquemas globais** e cria **itens de marcação** no backend.
2. No frontend, o **plugin de sistema** detecta a página, gera os esquemas globais e
   dispara o evento que as **integrações** respondem com o payload do conteúdo atual.
3. O **motor** mescla mapeamentos + SmartTags, normaliza (datas ISO 8601, URLs absolutas),
   limpa propriedades vazias e serializa o JSON-LD.
4. A marcação é injetada na página e os schemas duplicados de terceiros são removidos.

Veja o pipeline completo em
[`docs/funcional/07-fluxo-de-renderizacao.md`](docs/funcional/07-fluxo-de-renderizacao.md).

## Instalação (desenvolvimento)

> O empacotamento automatizado (`dist/`) está descrito na Fase 10 do `TODO.md`.

1. Requisitos: Joomla 6, PHP 8.3, MySQL 5.7+ (ou MariaDB 10.4+).
2. Instale o pacote `pkg_esquemarico` pelo Gerenciador de Extensões.
3. Habilite o plugin **Sistema – Esquema Rico** (a biblioteca e as integrações são
   habilitadas pelo instalador do pacote).
4. Acesse **Componentes → Esquema Rico** para configurar.

## Integração contínua e deploy

| Workflow | Gatilho | O que faz |
|----------|---------|-----------|
| `build.yml` | push/PR | `php -l`, PHPStan/PHPMD/PHPCS (advisory) e testes do motor (PHPUnit) |
| `validacao-pre-master.yml` | PR/push para `master` | Gate `validar.sh` (FAIL bloqueia) |
| `deploy.yml` | tag `v*` | Valida, empacota e publica via FTPS o `pkg_esquemarico` + XML de atualização (Joomla 6) |

**Gate de pré-publicação** (rode antes de PR/merge na `master` ou de empacotar):

```bash
.claude/skills/validacao-pre-producao/validar.sh
```

Verifica sintaxe PHP, **convenções Joomla 6** (reprova APIs legadas J3/`CMSObject`),
i18n, **SQL MySQL 5** (índice em `VARCHAR` ≤ 191, sem sintaxe MariaDB, datas anuláveis) e a
estrutura dos artefatos. Detalhes no Skill `.claude/skills/validacao-pre-producao/`.

> Instruções para assistentes de IA: [`CLAUDE.md`](CLAUDE.md), [`AGENTS.md`](AGENTS.md),
> [`GEMINI.md`](GEMINI.md) e [`.github/copilot-instructions.md`](.github/copilot-instructions.md).

## Compatibilidade

Projetado para a arquitetura moderna do Joomla (namespaced MVC, *service providers*,
eventos via `SubscriberInterface`), PHP 8.3 e MySQL 5 (`InnoDB` + `utf8mb4`, sem recursos
exclusivos de MySQL 8). Detalhes em
[`docs/funcional/09-compatibilidade.md`](docs/funcional/09-compatibilidade.md).

## Status

Em desenvolvimento ativo. Consulte o [`TODO.md`](TODO.md) para o estado de cada fase.

## Licença

Distribuído sob a [GNU General Public License v3.0 ou posterior](https://www.gnu.org/licenses/gpl-3.0.html),
como é padrão para extensões Joomla.
