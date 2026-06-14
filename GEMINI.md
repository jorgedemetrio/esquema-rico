**Projeto Esquema Rico**
# Pré-requisitos e Padrões de Desenvolvimento – Joomla 6

- Fale comigo em **Português Brasil** (não traduza termos técnicos nem comandos).
- O componente principal é `com_esquemarico` em `src/com_esquemarico`. É uma extensão de
  **dados estruturados** (JSON-LD / Schema.org) para o Joomla.
- Você é desenvolvedor sênior Joomla 6, com domínio em **PHP 8.3, MySQL 5, Bootstrap 5 e HTML5**.
- Leia `docs/funcional/` para entender o projeto. Trabalhe **pontualmente em erros**; ao
  achar um erro, verifique o mesmo padrão em arquivos similares.

## Identificadores

- Componente `com_esquemarico` · namespace `Joomla\Component\Esquemarico\{Administrator,Site}`.
- Biblioteca `Esquemarico\Core` (`plg_system_esquemaricocore`) · grupo de plugins `esquemarico`.
- Tabelas `#__esquemarico`, `#__esquemarico_config` · constantes `ESR_*`/`COM_ESQUEMARICO_*`.
- Idiomas: **pt-BR** (principal) + **en-GB** (fallback).

## Arquitetura — Joomla 6 (namespaced, moderno)

> NÃO é Joomla 3. Proibido: `JFactory`, `JText`, `JRoute`, `JHtml`, `jimport`, `*Legacy`,
> `CMSObject`. Tudo com `use Joomla\CMS\…`.

- Código em `src/` (PSR-4) declarado no manifesto (`<namespace path="src">…`).
- `services/provider.php` (DI) em todo artefato; plugin de sistema com `SubscriberInterface`;
  integrações herdam de `…\Administrator\Plugin\PluginBase{,Artigo,Produto,Evento}`.
- MVC moderno via `MVCFactory`; `Factory::getApplication()`; usuário por `$app->getIdentity()`
  (não `Factory::getUser()`); config por `$app->get()`.
- `Text::_`, `Route::_`, `HTMLHelper::_`, `ToolbarHelper`.
- Banco: `DatabaseInterface` + query builder com `->bind()`/`ParameterType` (nunca
  concatenar SQL).
- CSRF (`HTMLHelper::_('form.token')` / `$this->checkToken()`); guarda `\defined('_JEXEC') or die;`.
- PHP 8.3: tipagem, null-safety (`?->`, `??`), `match`/enums.

## Banco de dados (MySQL 5)

- `InnoDB` + `utf8mb4`/`utf8mb4_unicode_ci`; **índice sobre `VARCHAR` ≤ 191** (limite de
  767 bytes do InnoDB).
- Sem sintaxe MariaDB (`IF [NOT] EXISTS` em coluna); sem recursos exclusivos de MySQL 8.
- `DATETIME` anulável (nunca `'0000-00-00'`); coluna nova no `CREATE TABLE` (sem `ALTER`
  de coluna no install); `uninstall` com `DROP TABLE IF EXISTS`.
- Migração = versão de produção + 1 em `admin/sql/updates/mysql/<versao>.sql` + bump nos
  manifestos. Seeds idempotentes.

## Internacionalização

- Strings novas em pt-BR e en-GB; não remova traduções. `Text::_` em JS sempre JS-safe.

## Qualidade e Git

- SOLID/KISS; comente quando necessário; liste o que fazer no `TODO.md` e vá checando.
- `php -l <arquivo>` antes de commit; `phpstan analyse -c phpstan.neon.dist src`.
- Commits descritivos em português (o quê / por quê / arquivos / impacto). Não comite
  código quebrado, `dist/` nem `vendor/`.

## Validações obrigatórias

```bash
php -l <ARQUIVO>                                  # sintaxe
bash build/lint.sh                                # php -l em todo src/
phpstan analyse -c phpstan.neon.dist src          # análise estática
cd tests && composer install && vendor/bin/phpunit # testes do motor JSON-LD
```

## Gate de pré-publicação (master)

Antes de PR/merge na `master` ou gerar o pacote, rode:

```bash
.claude/skills/validacao-pre-producao/validar.sh
```

Cobre `php -l`, convenções J6 (FAIL em API legada/`CMSObject`), PHPStan/PHPCS (advisory),
i18n (chave usada-mas-inexistente em pt-BR + idiomas de manifesto), SQL MySQL 5 (ALTER de
coluna, MariaDB, índice VARCHAR > 191, datetime '0000-00-00') e estrutura provider/Extension.
FAIL bloqueia; WARN/INFO são heurística. Roda na CI (`.github/workflows/validacao-pre-master.yml`).

## Estrutura de arquivos de IA

```
.github/copilot-instructions.md   # GitHub Copilot (regras J6)
CLAUDE.md                         # Guia detalhado (arquitetura + regras + comandos)
GEMINI.md                         # Este arquivo
AGENTS.md                         # Visão geral / coordenação entre assistentes
docs/funcional/                   # Documentação funcional (00–11)
docs/guia-do-usuario.md           # Guia do usuário final
TODO.md                           # Plano de implementação por fases
```
