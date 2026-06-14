# Testes — Esquema Rico

Testes unitários do **motor de geração de JSON-LD** (`GeradorJsonLd`), a parte mais
crítica e autocontida da extensão. O motor depende apenas de `Joomla\Registry\Registry`
e de um *stub* do helper, então roda **fora** de uma instalação completa do Joomla.

## Pré-requisitos

- PHP 8.1+ (alvo 8.3)
- [Composer](https://getcomposer.org/)

## Como rodar

```bash
cd tests
composer install
vendor/bin/phpunit
```

Saída esperada: todos os testes verdes (cada tipo de schema principal é coberto).

## O que é coberto

`GeradorJsonLdTest.php` valida, para entradas representativas:

- **Artigo** — `@type`, headline, `ImageObject`, autor, data.
- **Produto** — `Offer` (preço único) e `AggregateOffer` (faixa `lowPrice`/`highPrice`).
- **Avaliação agregada** — presente com nota + contagem; **ausente** sem contagem.
- **Evento** — `Place`, datas, oferta.
- **FAQ** — `FAQPage` → `Question`/`Answer`.
- **Breadcrumbs** — `BreadcrumbList` com `position`.
- **Limpeza** — remove propriedades vazias, preserva o zero.
- **Código personalizado** — passa direto (sem reembrulhar).
- **Tipo inválido** — retorna `null`.
- **Lista de tipos** — ordem e "código personalizado" por último.

## Estratégia

O *bootstrap* (`bootstrap.php`) define `_JEXEC`, carrega `joomla/registry` via Composer,
declara um *stub* mínimo de `EsquemaRicoHelper` (event/log como no-ops) e inclui o arquivo
do motor. Assim, o motor é exercitado sem o CMS, comparando o JSON-LD decodificado com o
esperado.

> Para validar o JSON gerado contra o Google, copie a saída de um item real para o
> [Teste de Resultados Avançados](https://search.google.com/test/rich-results) ou para o
> [validador Schema.org](https://validator.schema.org/). Ver
> [`docs/funcional/11-validacao-e-qa.md`](../docs/funcional/11-validacao-e-qa.md).
