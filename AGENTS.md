# Instruções para Desenvolvedores e Assistentes de IA

## Sobre Este Projeto

**Esquema Rico** é uma extensão **Joomla 6** que gera **dados estruturados** (JSON-LD /
Schema.org) para qualificar o site a Resultados Avançados da Pesquisa Google. É um pacote
com um componente, um plugin de sistema, uma biblioteca compartilhada e plugins de
integração. Tudo em **português (pt-BR)**, com **en-GB** como fallback.

## Stack

- **Backend**: PHP 8.3, Joomla 6 (arquitetura moderna namespaced), MySQL 5 (utf8mb4/InnoDB).
- **Frontend**: Bootstrap 5, HTML5 (a saída funcional é `<script type="application/ld+json">`).
- **Build/CI**: Bash (`build/`), GitHub Actions (`.github/workflows/`).

## Assistentes de IA integrados

Todos devem seguir os **mesmos padrões J6** (ver abaixo) e manter consistência.

- 🤖 **GitHub Copilot** — `.github/copilot-instructions.md` (regras J6, foco em código).
- 🤖 **Claude** — `CLAUDE.md` (guia detalhado: arquitetura, regras, comandos, gate).
- 🤖 **Gemini** — `GEMINI.md` (regras J6, espelho do Copilot).
- 📋 **Coordenação**: seguir os mesmos padrões de código/arquitetura; documentar decisões
  no `TODO.md`/`CHANGELOG.md`; comunicar mudanças significativas.

## Diretrizes gerais

### Comunicação
- Responda sempre em **Português Brasil** (não traduza termos técnicos nem comandos).

### Joomla 6 (NÃO é Joomla 3)
- Código **namespaced**; `use Joomla\CMS\…`. **Proibido**: `JFactory`, `JText`, `JRoute`,
  `jimport`, `*Legacy`, `CMSObject`.
- `services/provider.php` (DI), plugins com `SubscriberInterface`, MVC via `MVCFactory`.
- Usuário por `$app->getIdentity()` (não `Factory::getUser()`); config por `$app->get()`.
- Banco por `DatabaseInterface` + query builder com bind (nunca concatenar SQL).
- Guarda `\defined('_JEXEC') or die;` no topo de cada PHP.

### Banco (MySQL 5)
- InnoDB + utf8mb4; **índice em VARCHAR ≤ 191**; sem sintaxe MariaDB; sem recursos de
  MySQL 8; `DATETIME` anulável.
- Migração = versão de produção + 1 (`admin/sql/updates/mysql/<versao>.sql` + bump nos
  manifestos).

### Qualidade de código
- SOLID e KISS; comente quando necessário.
- Liste o que fazer primeiro no `TODO.md` e vá checando.
- Valide antes do commit (Sonar/PMD/PHPStan): rode o gate `validar.sh`.

### Controle de versão
- Faça `pull` antes de começar. Commits descritivos em português (o quê / por quê /
  arquivos / impacto). Não comite código quebrado, `dist/` nem `vendor/`.

## Validações obrigatórias

```bash
php -l <ARQUIVO>                                     # sintaxe PHP
bash build/lint.sh                                   # php -l em todo src/
phpstan analyse -c phpstan.neon.dist src             # análise estática
cd tests && composer install && vendor/bin/phpunit   # testes do motor JSON-LD
.claude/skills/validacao-pre-producao/validar.sh     # GATE de pré-publicação
```

## Estrutura de arquivos de IA e docs

```
.github/copilot-instructions.md      # Copilot
.github/workflows/                    # build / validacao-pre-master / deploy
.github/scripts/deploy.sh             # empacota + publica (FTPS) o pacote
.claude/skills/validacao-pre-producao/ # gate (validar.sh + SKILL.md + CHECKLIST.md)
CLAUDE.md / GEMINI.md / AGENTS.md     # instruções para os assistentes
docs/funcional/                       # documentação funcional (00–11)
docs/guia-do-usuario.md               # guia do usuário final
TODO.md / CHANGELOG.md / README.md
```

## Como testar (instalação real no Joomla 6)

1. Baixe um Joomla 6 (pacote full) e descompacte num servidor com PHP 8.3 e MySQL 5.7+.
2. Gere o pacote: `bash build/build.sh` → `dist/pkg_esquemarico.zip`.
3. Instale pelo Gerenciador de Extensões (Enviar Pacote). Os plugins são auto-habilitados.
4. Configure em **Componentes → Esquema Rico**, crie um item e confira o JSON-LD no
   frontend; valide no Teste de Resultados Avançados do Google.
5. Roteiro completo em `docs/funcional/11-validacao-e-qa.md`.

## Princípio de mínima intervenção

- Trabalhe pontualmente; não refaça arquivos/telas inteiras.
- Ao detectar um erro, verifique o mesmo padrão em arquivos similares.

---

**Bem-vindo ao time de desenvolvimento! 🚀**
