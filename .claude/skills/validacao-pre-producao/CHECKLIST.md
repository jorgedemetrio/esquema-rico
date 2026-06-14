# Checklist de pré-publicação — Esquema Rico (Joomla 6)

Itens automatizados pelo `validar.sh` marcados com 🤖; os demais são manuais.

## Código (Joomla 6 / PHP 8.3)

- [ ] 🤖 `php -l` sem erros em todo `src/` e `tests/`.
- [ ] 🤖 Sem API legada J3 (`JFactory`, `JText`, `JRoute`, `JHtml`, `*Legacy`, `jimport`).
- [ ] 🤖 Sem `CMSObject` (removido no J6).
- [ ] 🤖 Guarda `\defined('_JEXEC') or die;` no topo de cada PHP.
- [ ] Todo código novo é **namespaced** e usa `use Joomla\CMS\…` (sem classes globais `J*`).
- [ ] Plugins implementam `SubscriberInterface` (sistema) ou seguem a classe-base de
      integração; componentes usam `MVCFactory` e `services/provider.php`.
- [ ] Acesso ao usuário via `$app->getIdentity()` / `UserFactoryInterface` (não `getUser()`).
- [ ] Queries com query builder + parâmetros vinculados (`->bind`, `ParameterType`),
      **nunca** concatenação de string.
- [ ] PHP 8.3: propriedades/retornos tipados; null-safety (`?->`, `??`); sem passar
      `null` a parâmetro não-nullable.

## Idiomas

- [ ] 🤖 Toda chave `ESR_*`/`COM_ESQUEMARICO_*` usada existe em **pt-BR**.
- [ ] 🤖 Todo arquivo de idioma referenciado nos manifestos existe.
- [ ] Strings novas adicionadas em **pt-BR** (principal) e **en-GB** (fallback).
- [ ] Sem remoção de traduções existentes.

## SQL (MySQL 5)

- [ ] 🤖 Sem `ALTER TABLE` de coluna (coluna nova vai no `CREATE TABLE`).
- [ ] 🤖 Sem sintaxe MariaDB (`ADD/DROP/MODIFY COLUMN IF [NOT] EXISTS`).
- [ ] 🤖 `ENGINE=InnoDB` + `utf8mb4`.
- [ ] 🤖 Sem `DATETIME DEFAULT '0000-00-00'` (use `NULL`).
- [ ] 🤖 Índice sobre `VARCHAR` ≤ 191 (limite de 767 bytes do InnoDB em utf8mb4).
- [ ] `uninstall` usa `DROP TABLE IF EXISTS`.
- [ ] Migração de versão: criar `admin/sql/updates/mysql/<nova-versao>.sql` (= versão de
      produção + 1) e bumpar `<version>` nos manifestos.
- [ ] Seeds idempotentes (`INSERT IGNORE` / `WHERE NOT EXISTS`).

## Estrutura / empacotamento

- [ ] 🤖 Cada plugin/componente tem `services/provider.php` + `src/Extension/*.php`.
- [ ] `bash build/build.sh` gera `dist/pkg_esquemarico.zip` sem erro.
- [ ] Manifesto do pacote lista todas as extensões na ordem (biblioteca → componente →
      plugin de sistema → integrações).

## Validação funcional (manual)

- [ ] `cd tests && composer install && vendor/bin/phpunit` — testes do motor verdes.
- [ ] Instalar o pacote num **Joomla 6** limpo (PHP 8.3, MySQL 5.7+).
- [ ] Plugins habilitados após instalar (a biblioteca, o sistema e as integrações).
- [ ] Criar um item de marcação e confirmar o `<script type="application/ld+json">` no
      frontend.
- [ ] Validar a saída no [Teste de Resultados Avançados](https://search.google.com/test/rich-results)
      e no [validador Schema.org](https://validator.schema.org/).
- [ ] Ver `docs/funcional/11-validacao-e-qa.md` para o roteiro completo de QA.
