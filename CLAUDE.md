# CLAUDE.md — Guia de desenvolvimento do Esquema Rico (Joomla 6)

Guia detalhado para assistentes de IA e desenvolvedores. Fale **Português Brasil**.
Para a visão funcional, leia `docs/funcional/`. Para usar a extensão, `docs/guia-do-usuario.md`.

---

## 1. O que é

**Esquema Rico** é uma extensão **Joomla 6** que gera **dados estruturados** (JSON-LD /
Schema.org) e os injeta nas páginas, qualificando o site a Resultados Avançados do Google.
Stack: **PHP 8.3, Joomla 6 (namespaced), MySQL 5 (utf8mb4/InnoDB), Bootstrap 5**.

### Identificadores (não confundir)

| Item | Valor |
|------|-------|
| Componente | `com_esquemarico` |
| Namespace | `Joomla\Component\Esquemarico\{Administrator,Site}` |
| Biblioteca compartilhada | `Esquemarico\Core` (`plg_system_esquemaricocore`) |
| Plugin de sistema | `plg_system_esquemarico` |
| Grupo de plugins de integração | `esquemarico` |
| Tabelas | `#__esquemarico`, `#__esquemarico_config` |
| Constantes de idioma | `ESR_*`, `COM_ESQUEMARICO_*` |
| Idiomas | **pt-BR** (principal), **en-GB** (fallback) |

---

## 2. Arquitetura (4 artefatos)

```
pkg_esquemarico
├── plg_system_esquemaricocore   Biblioteca Esquemarico\Core (cache, condições, SmartTags, utils)
├── com_esquemarico              Componente: backend + MOTOR JSON-LD + tipos de schema
├── plg_system_esquemarico       Plugin de sistema: injeta o JSON-LD + esquemas globais
└── plg_esquemarico_*            Integrações (content, menus, k2, virtuemart, hikashop, jevents, dpcalendar)
```

Detalhe em `docs/funcional/01-arquitetura.md`. Ordem de dependência (e instalação):
biblioteca → componente → plugin de sistema → integrações.

### Mapa de diretórios (`src/`)

```
src/
├── pkg_esquemarico/                      manifesto do pacote + script.php
├── com_esquemarico/
│   ├── esquemarico.xml  script.php  admin/  media/
│   └── admin/
│       ├── services/provider.php
│       ├── src/
│       │   ├── Extension/EsquemaricoComponent.php
│       │   ├── Controller/  Model/  View/  Table/  Field/
│       │   ├── Engine/GeradorJsonLd.php          ← MOTOR (coração)
│       │   ├── Schema/{Base,SchemaHelper}.php  Schema/Tipos/*.php
│       │   ├── Helper/{EsquemaRicoHelper,MappingOptions,SchemaCleaner,Apps}.php
│       │   └── Plugin/PluginBase{,Artigo,Produto,Evento}.php
│       ├── forms/{item,filter_items,config}.xml  forms/contenttypes/*.xml
│       ├── sql/{install,uninstall}.mysql.utf8.sql  sql/updates/mysql/*.sql
│       ├── tmpl/  language/{pt-BR,en-GB}/  access.xml  config.xml
│   └── site/                                       ← frontend: SITEMAPS XML
│       └── src/{Controller,Model,Helper}/  language/    (Sitemap{Priority,Builder}, SitemapModel)
├── plg_system_esquemarico/               provider + src/Extension/Esquemarico.php
├── plg_system_esquemaricocore/           autoload.php + Core/  + src/Extension/
└── plg_esquemarico_<nome>/               provider + src/Extension/<Nome>.php + form/ + language/
```

---

## 3. Convenções Joomla 6 (OBRIGATÓRIAS)

> **NÃO é Joomla 3.** Proibido: `JFactory`, `JText`, `JRoute`, `JHtml`, `JModelLegacy`,
> `JControllerLegacy`, `jimport`, `CMSObject`. O gate `validar.sh` reprova esses usos.

### 3.1 Service provider (todo artefato tem)

```php
// services/provider.php
return new class () implements ServiceProviderInterface {
    public function register(Container $container): void {
        @include_once JPATH_PLUGINS . '/system/esquemaricocore/autoload.php'; // biblioteca
        $container->set(PluginInterface::class, function (Container $c) {
            $plugin = new MinhaClasse($c->get(DispatcherInterface::class),
                (array) PluginHelper::getPlugin('esquemarico', 'alias'));
            $plugin->setApplication(Factory::getApplication());
            return $plugin;
        });
    }
};
```

### 3.2 Plugin de sistema = `SubscriberInterface`

```php
final class Esquemarico extends CMSPlugin implements SubscriberInterface {
    public static function getSubscribedEvents(): array {
        return ['onBeforeCompileHead' => 'aoCompilarCabecalho', 'onAfterRender' => 'aposRenderizar'];
    }
}
```

### 3.3 Aplicação, usuário, config, banco

```php
$app  = Factory::getApplication();
$user = $app->getIdentity();                 // NUNCA Factory::getUser()
$cfg  = $app->get('sitename');               // NUNCA Factory::getConfig()
$db   = Factory::getContainer()->get(\Joomla\Database\DatabaseInterface::class);
$query = $db->getQuery(true)->select('*')->from($db->quoteName('#__esquemarico'))
    ->where($db->quoteName('plugin') . ' = :p')->bind(':p', $alias);  // SEM concatenar
```

### 3.4 Guarda e tipagem

- Todo PHP começa com `\defined('_JEXEC') or die;`.
- PHP 8.3: propriedades/parâmetros/retornos tipados, `?->`, `??`, `match`, enums.

---

## 4. O motor JSON-LD (`Engine/GeradorJsonLd.php`)

Recebe os dados já preparados e produz `<script type="application/ld+json" data-type="esr">`.
Um método `contentType<Tipo>()` por tipo. Pontos importantes:

- `clean()` remove `null`/`false`/`''` (preserva `0`) e objetos órfãos só com `@type`.
- `@context: https://schema.org` é prependido; serializa com
  `JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR`.
- O atributo `data-type="esr"` protege nossos blocos da remoção de duplicados.
- **Código personalizado** retorna a string como está (sem reembrulhar nem limpar — ver
  `Schema\Tipos\Custom_Code`).

### Adicionar um novo tipo de schema

1. `forms/contenttypes/<tipo>.xml` — campos (use `map`/`mapimage`/`mapdate`/`mapuser` para
   valores de conteúdo; `list`/`text` para enums fixos).
2. Método `contentType<Tipo>()` em `GeradorJsonLd`.
3. (Opcional) `Schema/Tipos/<Tipo>.php` para normalização específica.
4. Strings `ESR_CONTENT_TYPE_<TIPO>` em pt-BR/en-GB.

> **Regra de nomes**: o `Schema\Base` deriva `ratingValue`/`reviewCount`/`title`/datas de
> `rating_value`/`review_count`/`headline`/`publish_up`. Nos formulários, use esses nomes
> (ex.: campo `rating_value`, não `ratingValue`) para o `Base` não sobrescrever com nulo.
> A chave `mode` (não `option`) no campo de horário evita colisão com o `MappingOptions`.

---

## 5. Integrações (`plg_esquemarico_*`)

Herdam de `…\Administrator\Plugin\PluginBase{,Artigo,Produto,Evento}`. Implementam, no
mínimo, `view<Nome>()` que retorna o **payload** (array de dados brutos da fonte). Opcional:
`passContext()`, `getThingID()`, `getView()`, `onMapOptions()`, `fonteInstalada()`.

```php
final class Content extends PluginBaseArtigo {
    public function viewArticle(): ?array {
        $model = Factory::getApplication()->bootComponent('com_content')->getMVCFactory()
            ->createModel('Article', 'Site', ['ignore_request' => true]);
        // ... retorna ['headline'=>…, 'description'=>…, 'image'=>…, 'cf.<campo>'=>…]
    }
}
```

As chaves do payload viram SmartTags `{gsd.item.<chave>}`. Condições de publicação em
`form/assignments.xml`. Fonte de terceiros: só atue se `fonteInstalada()` (via
`Esquemarico\Core\Extension::componentIsEnabled('alias')`).

### Adicionar uma integração

`manifest.xml` + `services/provider.php` + `src/Extension/<Nome>.php` (herdando a base
certa) + `form/assignments.xml` + `language/{pt-BR,en-GB}`; registrar no
`pkg_esquemarico.xml`, no `script.php` (auto-habilitar) e no `build/build.sh`.

---

## 6. Mapeamento, SmartTags e Condições

- **Mapeamento**: cada propriedade do schema é ligada a uma origem via `MapField` (estrutura
  `{option, fixed, custom}`). `MappingOptions::prepare` resolve o modo; `::replace`
  substitui as SmartTags.
- **SmartTags**: `{gsd.item.*}` (payload), `{gsd.*}` (site), `{user.*}`, `{page.*}`,
  `{site.*}`, `{date}`/`{year}`… (biblioteca `Esquemarico\Core\SmartTags`).
- **Condições**: `Esquemarico\Core\Conditions` — `ConditionsHelper::passSet` avalia regras
  (operador `includes`/`not_includes`). Condições: Menu, Idioma, Componente, Dispositivo,
  GrupoUsuario, NivelAcesso. UI por `form/assignments.xml`.

Detalhe em `docs/funcional/{05,06}-*.md`.

---

## 7. Banco de dados (MySQL 5)

- `ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci`.
- **Índice sobre `VARCHAR` ≤ 191** (limite de 767 bytes do InnoDB com utf8mb4 no MySQL 5).
- Sem sintaxe MariaDB (`IF [NOT] EXISTS` em coluna); sem recursos exclusivos de MySQL 8.
- `DATETIME` anulável (`NULL`), nunca `'0000-00-00 00:00:00'`.
- Coluna nova no `CREATE TABLE`; sem `ALTER` de coluna no `install`. `uninstall` com
  `DROP TABLE IF EXISTS`. Seeds idempotentes.
- Migração: `admin/sql/updates/mysql/<versao>.sql` (= versão de produção + 1) + bump nos
  manifestos. Persistência de config = linha `config` em `#__esquemarico_config`.

---

## 8. Internacionalização

- pt-BR (principal) + en-GB (fallback). Toda chave usada deve existir. Não remova traduções.
- `Text::_` em JavaScript = SEMPRE JS-safe (`Text::_('CHAVE', true)` ou `json_encode`).

---

## 9. Comandos

```bash
bash build/lint.sh                 # php -l em todo src/
bash build/build.sh [versao]       # gera dist/pkg_esquemarico.zip (bumpa manifestos se versao)
cd tests && composer install && vendor/bin/phpunit    # testes do motor JSON-LD
phpstan analyse -c phpstan.neon.dist src              # análise estática
phpcs --standard=phpcs.xml.dist src                   # PSR-12

# GATE de pré-publicação (rode antes de PR/merge na master ou de empacotar)
.claude/skills/validacao-pre-producao/validar.sh
```

### Deploy

`git tag vX.Y.Z && git push --tags` dispara `.github/workflows/deploy.yml` → roda o gate,
`build/build.sh vX.Y.Z`, e publica `pkg_esquemarico-X.Y.Z.zip` + `atualizacao.xml` via FTPS
(secrets `FTP_URL`/`FTP_USUARIO`/`FTP_SENHA`). `targetplatform` = Joomla 6; `php_minimum` 8.1.

---

## 10. Git e mínima intervenção

- Faça `pull` antes de começar; commits descritivos em português (o quê / por quê /
  arquivos / impacto). Não comite código quebrado, `dist/` nem `vendor/`.
- Trabalhe pontualmente; não refaça arquivos inteiros. Ao corrigir um erro, verifique o
  mesmo padrão em arquivos similares.
- Antes de entregar na `master`, o gate `validar.sh` precisa passar (sem FAIL).

---

## 11. Estado atual

Ver `TODO.md` (fases) e `CHANGELOG.md`. Resumo: motor JSON-LD com 18 tipos, backend
completo (CRUD + campos de mapeamento + config global), 7 integrações, 6 condições, testes
do motor, lint/PHPStan/PSR-12, gate e deploy. Pendentes: fast-edit inline com AJAX (v2),
mais integrações, construtor de condições AND/OU, paridade total en-GB.

> ⚠️ Não há PHP no ambiente de desenvolvimento atual — rode lint/PHPUnit/gate antes de publicar.
