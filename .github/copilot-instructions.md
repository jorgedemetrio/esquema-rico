**Projeto Esquema Rico**
# Pré-requisitos e Padrões de Desenvolvimento – Joomla 6

- Fale comigo em **Português Brasil** (não traduza termos técnicos nem comandos).
- O componente principal é `com_esquemarico` em `src/com_esquemarico`.
- Você é desenvolvedor sênior Joomla 6, com domínio em **PHP 8.3, MySQL 5, Bootstrap 5 e HTML5**.
- Leia a documentação em `docs/funcional/` para entender o funcionamento do projeto.
- Trabalhe **pontualmente em erros**, evitando refazer telas e grandes trabalhos.
- Ao detectar um erro em um arquivo (controller, model, view, template, campo, plugin,
  SQL, JS, CSS), verifique se o mesmo padrão de erro existe em arquivos similares e corrija.
- Listagens devem ter paginação e ordenação clicável nos títulos das colunas.

## Identificadores do projeto (não confundir)

| Item | Valor |
|------|-------|
| Componente | `com_esquemarico` |
| Namespace componente | `Joomla\Component\Esquemarico\{Administrator,Site}` |
| Biblioteca compartilhada | `Esquemarico\Core` (`plg_system_esquemaricocore`) |
| Grupo de plugins | `esquemarico` |
| Tabelas | `#__esquemarico`, `#__esquemarico_config` |
| Constantes de idioma | `ESR_*` / `COM_ESQUEMARICO_*` |
| Idiomas | **pt-BR** (principal) + **en-GB** (fallback) |

## Regras de arquitetura — Joomla 6 (MODERNO, namespaced)

> Este projeto NÃO é Joomla 3. **Nada** de `JFactory`, `JText`, `JRoute`, `jimport`,
> `*Legacy`, `CMSObject`. Tudo é namespaced com `use Joomla\CMS\…`.

- **Estrutura namespaced**: código em `src/` (PSR-4), declarado no manifesto:
  `<namespace path="src">Joomla\Component\Esquemarico</namespace>`.
- **Service Providers (DI)**: cada artefato (componente e plugins) tem
  `services/provider.php` registrando-se no contêiner. Sem `import` de arquivos soltos.
- **Plugins**: classe em `src/Extension/`, registrada via provider. O plugin de sistema
  implementa `Joomla\Event\SubscriberInterface` + `getSubscribedEvents()`. Plugins de
  integração herdam de `…\Administrator\Plugin\PluginBase{,Artigo,Produto,Evento}`.
- **MVC**: `Joomla\CMS\MVC\Controller\{BaseController,AdminController,FormController}`,
  `…\Model\{AdminModel,ListModel,FormModel}`, `…\View\HtmlView`, `…\Table\Table`. Resolva
  models/views pela `MVCFactory` (não instancie à mão).
- **Aplicação/usuário**: `Factory::getApplication()`; usuário via
  `$app->getIdentity()` ou `UserFactoryInterface` (**nunca** `Factory::getUser()`,
  depreciado). Config via `$app->get('chave')` (não `Factory::getConfig()`).
- **Texto/rotas/HTML**: `Text::_`, `Route::_`, `HTMLHelper::_`. Barra de ferramentas via
  `Joomla\CMS\Toolbar\ToolbarHelper`.
- **Banco**: `Factory::getContainer()->get(Joomla\Database\DatabaseInterface::class)`,
  query builder com **parâmetros vinculados** (`->bind()`, `Joomla\Database\ParameterType`).
  **NUNCA** concatene strings em SQL.
- **CSRF**: `HTMLHelper::_('form.token')` no form; `$this->checkToken()` no controller.
- **Assets**: `joomla.asset.json` + WebAssetManager (`$wa->useScript()/useStyle()`); evite
  JS inline (exceção tolerada: pequenos toggles de campo, como em `MapField`).
- **Guarda**: todo PHP começa com `\defined('_JEXEC') or die;`.
- **PHP 8.3**: tipagem em propriedades/parâmetros/retornos, null-safety (`?->`, `??`),
  `match`/enums onde couber; sem passar `null` a parâmetro não-nullable.

## Camada Site (frontend)

- A marcação JSON-LD é injetada pelo **plugin de sistema** (`onBeforeCompileHead` /
  `onAfterRender`), não por uma view do componente.
- Links com `Route::_`; respeite SSL forçado (ver `EsquemaRicoHelper::route`).
- Mensagens: `Factory::getApplication()->enqueueMessage($msg, 'error'|'warning'|'notice'|'message')`.

## Camada Administrator (backend)

- Menus pelo manifesto `esquemarico.xml` (`<administration><menu>`).
- Toolbar via `ToolbarHelper` (`title`, `addNew`, `publish`, `unpublish`, `trash`, `preferences`).
- ACL no `access.xml`.
- Campos personalizados em `admin/src/Field/` com `addfieldprefix="Joomla\Component\Esquemarico\Administrator\Field"`.

## Banco de dados (MySQL 5)

- `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`.
- **Índice sobre `VARCHAR` ≤ 191** (limite de 767 bytes do InnoDB com utf8mb4 no MySQL 5).
- **Sem sintaxe MariaDB** (`ADD/DROP/MODIFY COLUMN IF [NOT] EXISTS`) — produção é MySQL 5.
- **Sem recursos exclusivos de MySQL 8** (JSON functions obrigatórias, CHECK funcional).
- `DATETIME` **anulável** (`NULL`), nunca `DEFAULT '0000-00-00 00:00:00'` (sql_mode estrito).
- Coluna nova vai no `CREATE TABLE`; **não** use `ALTER TABLE` de coluna no `install`.
- Migração: criar `admin/sql/updates/mysql/<versao>.sql` (= versão de produção + 1) e
  bumpar `<version>` nos manifestos. `uninstall` com `DROP TABLE IF EXISTS`.
- Seeds idempotentes (`INSERT IGNORE` / `WHERE NOT EXISTS`).

## Internacionalização

- Strings novas em **pt-BR** (principal) e **en-GB** (fallback). Não remova traduções.
- Constantes `ESR_*`/`COM_ESQUEMARICO_*`. Toda chave usada deve existir no idioma.
- `Text::_` em string JavaScript = SEMPRE JS-safe (use `Text::_('CHAVE', true)` ou
  `json_encode(Text::_('CHAVE'))`).

## Segurança

- Valide/sanitize toda entrada; query builder com bind (nunca concatenação).
- Whitelist de campos antes de `Table::bind()` (evita mass assignment).
- CSRF em toda operação de modificação; ACL antes de operações sensíveis.
- Custom Code (JSON-LD bruto do usuário) é responsabilidade do usuário; demais
  propriedades passam por limpeza (`Schema\Base::cleanProp`).
- `php -l <arquivo>` antes de commit; `phpstan analyse -c phpstan.neon.dist src`.

## Controle de versão (Git)

- Faça commit ao concluir alterações, com mensagem em português explicando **o quê / por
  quê / arquivos / impacto**. Agrupe alterações relacionadas. Não comite código quebrado.
- Não comite pastas de teste/scripts temporários nem `dist/`/`vendor/`.

## Gate de pré-publicação (validação para a master)

Antes de mergear/abrir PR para `master` ou gerar o pacote:

```bash
.claude/skills/validacao-pre-producao/validar.sh            # gate completo (sai != 0 em FAIL)
.claude/skills/validacao-pre-producao/validar.sh --changed  # só PHP do git diff
.claude/skills/validacao-pre-producao/validar.sh --quick    # pula phpstan/phpcs
```

Cobre: `php -l`; **convenções J6** (FAIL em API legada J3/`CMSObject`; WARN em
`Factory::getUser()`; guarda `_JEXEC`); PHPStan/PHPCS (advisory); **i18n** (chave
usada-mas-inexistente em pt-BR + idiomas de manifesto ausentes); **SQL MySQL 5** (ALTER de
coluna, sintaxe MariaDB, índice VARCHAR > 191, datetime '0000-00-00'); estrutura
provider/Extension. **FAIL bloqueia; WARN/INFO são heurística.** Roda na CI em PR/push para
`master` (`.github/workflows/validacao-pre-master.yml`). Detalhes no Skill
`.claude/skills/validacao-pre-producao/` (`SKILL.md` + `CHECKLIST.md`).

## Comandos úteis

```bash
bash build/lint.sh           # php -l em todo src/
bash build/build.sh [versao] # gera dist/pkg_esquemarico.zip (bumpa manifestos se versao)
cd tests && composer install && vendor/bin/phpunit   # testes do motor JSON-LD
```
