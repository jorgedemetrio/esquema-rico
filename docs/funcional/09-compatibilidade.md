# 09 — Compatibilidade

A extensão tem como alvo **Joomla 6**, **PHP 8.3** e **MySQL 5**. Este documento resume os
requisitos e as escolhas técnicas que garantem essa compatibilidade.

## Requisitos mínimos

| Plataforma | Versão alvo | Mínimo suportado |
|------------|-------------|------------------|
| Joomla | 6.x | 5.0 (com a estrutura namespaced) |
| PHP | 8.3 | 8.1 |
| MySQL | 5.7 | 5.6 |
| MariaDB | 10.4+ | 10.3 |

## Joomla 6

Joomla 6 consolida a arquitetura moderna iniciada na 4 e remove definitivamente APIs
legadas. A extensão adota desde o início o modelo atual:

- **Estrutura namespaced**: todo o código fica em `src/` com PSR-4. O manifesto declara
  `<namespace path="src">Joomla\Component\Esquemarico</namespace>`.
- **Service Providers (DI)**: cada artefato (componente e plugins) tem
  `services/provider.php` registrando-se no contêiner de injeção de dependências. Não há
  mais `import` de arquivos soltos.
- **MVC moderno**: `Controller/`, `Model/`, `View/`, `Table/` namespaced; *factories* via
  `MVCFactory`.
- **Eventos por `SubscriberInterface`**: os plugins implementam
  `Joomla\Event\SubscriberInterface` e declaram `getSubscribedEvents()` em vez de métodos
  mágicos. Cada *handler* recebe um objeto de evento tipado.
- **Plugins com classe em `src/Extension/`** e *boot* via provider.

### APIs legadas evitadas (removidas no Joomla 5/6)

| Legado (não usar) | Substituto moderno |
|-------------------|--------------------|
| `JFactory`, `JText`, `JRoute`, … (prefixo `J`) | Classes namespaced `Joomla\CMS\…` |
| `Factory::getUser()` | `$app->getIdentity()` / `getApplication()->getIdentity()` |
| `Factory::getConfig()` | `$app->get('config_key')` |
| `BaseDatabaseModel::getInstance()` | `MVCFactory`/`getMVCFactory()->createModel()` |
| `JEventDispatcher`, métodos mágicos de plugin | `SubscriberInterface` + eventos tipados |
| `CMSObject` (genérico) | DTOs/`\stdClass`/tipos próprios |
| `\JLoader::register` ad hoc | Autoload PSR-4 via namespace do manifesto |

> A detecção de "qual versão do Joomla" do produto original (constante `nrJ4`) deixa de
> ser necessária: o alvo é uma única linha moderna (5/6).

## PHP 8.3

- **Tipagem**: assinaturas tipadas, `declare(strict_types=1)` onde apropriado.
- **Sem comportamentos depreciados**: nada de `${var}` em strings, nada de chamadas
  dinâmicas inseguras; *null-safety* ao acessar propriedades opcionais (operador `?->` e
  *null coalescing* `??`).
- **`json_encode`** com `JSON_THROW_ON_ERROR` opcional; tratamento explícito de
  `JsonException`.
- **`array_filter`/`array_map`** com cuidado para preservar `0` (zero é valor válido em
  preços, avaliações, posições).
- Atenção a *passing null to non-nullable* (depreciado a partir do 8.1): todas as
  funções de string recebem `(string)` *casts* defensivos.

## MySQL 5

Ver [08 — Modelo de dados](08-modelo-de-dados.md) para o detalhamento. Resumo das
decisões:

- `InnoDB` + `utf8mb4` + `utf8mb4_unicode_ci`.
- Colunas indexadas com VARCHAR ≤ 190 (limite de 767 bytes do índice).
- `ROW_FORMAT=DYNAMIC` para linhas largas.
- Sem dependência de funções JSON do MySQL nem de `CHECK` constraints.
- Datas anuláveis em vez de *default* `'0000-00-00'`.

## Multilíngue

- Idioma principal **pt-BR**, com **en-GB** como *fallback* (padrão do Joomla).
- Suporte a sites multilíngues: itens de marcação têm coluna `language` e o frontend
  filtra por idioma ativo (ou `*`).
- Strings de instalação em `*.sys.ini`; strings de runtime em `*.ini`.

## Acessibilidade e desempenho

- A marcação não altera o DOM visível; é apenas `<script>` — sem impacto de
  acessibilidade.
- Cache em memória por requisição evita recomputar campos personalizados e leitura de XML.
- A remoção de duplicados opera sobre o *buffer* já montado, em uma única passagem.
