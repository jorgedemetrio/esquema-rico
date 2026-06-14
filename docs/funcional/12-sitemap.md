# 12 — Sitemap XML

O Esquema Rico gera **sitemaps XML** (protocolo [sitemaps.org 0.9](https://www.sitemaps.org/protocol.html))
para o Google e demais buscadores, com **peso (priority) ponderado pela recência** da data
de modificação/criação: conteúdo alterado ou criado recentemente é mais importante que o
antigo.

## Endereços

| Sitemap | URL |
|---------|-----|
| Índice | `index.php?option=com_esquemarico&view=sitemap&format=xml` |
| Conteúdo (artigos) | `…&view=sitemap&type=content&format=xml` |
| Categorias | `…&view=sitemap&type=categories&format=xml` |
| Itens de menu | `…&view=sitemap&type=menu&format=xml` |
| Tags | `…&view=sitemap&type=tags&format=xml` |

O **índice** (`sitemapindex`) aponta para os quatro sub-sitemaps. Envie a URL do índice ao
Google Search Console. Cada sub-sitemap é um `urlset` independente.

> O cabeçalho `Content-Type: application/xml` é definido na resposta, e `X-Robots-Tag:
> noindex` evita que o próprio sitemap seja indexado como página.

## Cálculo do peso (priority)

A prioridade (0,1 a 1,0 do protocolo) vem de um **decaimento linear** sobre uma janela
(padrão 365 dias), usando a data mais recente entre **modificação** e **criação**:

```
idade = (hoje − data_mais_recente) em dias
fator = min(idade / janela, 1)
priority = max − (max − min) × fator            (arredondado a 1 casa)
```

- **idade 0** (hoje) → prioridade **máxima** (padrão 1,0);
- **idade ≥ janela** → prioridade **mínima** (padrão 0,1);
- entre os dois → linear.

Assim, itens recentes pesam mais que os antigos, exatamente como pedido. Os parâmetros
(janela, mínimo, máximo) são configuráveis em **Configurações → Sitemap XML**.

## Frequência sugerida (changefreq)

Derivada da idade: ≤ 1 dia → `daily`; ≤ 30 dias → `weekly`; ≤ 180 dias → `monthly`;
acima → `yearly`.

## Regras por tipo

| Tipo | Fonte | Data usada | Observações |
|------|-------|------------|-------------|
| Conteúdo | `#__content` (state=1) | `modified` / `created` | Filtra por nível de acesso público e idioma |
| Categorias | `#__categories` (com_content, published) | `modified_time` / `created_time` | Exclui a categoria raiz (id=1) |
| Menu | `#__menu` (site, published) | `publish_up` | Itens de menu não têm data de modificação; a **home** recebe a prioridade máxima |
| Tags | `#__tags` (published) | `modified_time` / `created_time` | Exclui a tag raiz |

Em todos os casos: apenas itens **publicados**, respeitando **nível de acesso** (visitante)
e **idioma** (`*` ou o idioma ativo). As URLs são roteadas (SEF quando ativo) e absolutas.
Tabelas ausentes (ex.: `com_tags` desabilitado) resultam em sitemap vazio, sem erro.

## Configuração

Em **Componentes → Esquema Rico → Configurações → Sitemap XML**:

- **Janela de prioridade (dias)** — período do decaimento (padrão 365).
- **Prioridade máxima** — peso dos mais recentes (padrão 1,0).
- **Prioridade mínima** — peso dos mais antigos (padrão 0,1).

## Arquitetura

- `site/src/Helper/SitemapPriority.php` — cálculo de peso/frequência por recência (**puro**,
  testável: ver `tests/SitemapTest.php`).
- `site/src/Helper/SitemapBuilder.php` — montagem do XML `urlset`/`sitemapindex` (**puro**).
- `site/src/Model/SitemapModel.php` — consultas (conteúdo, categorias, menu, tags) e
  montagem das entradas.
- `site/src/Controller/DisplayController.php` — devolve o XML com o `Content-Type` correto.

## Exemplo de saída

```xml
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
  <url>
    <loc>https://seusite.com/noticias/lancamento</loc>
    <lastmod>2026-06-14T09:30:00-03:00</lastmod>
    <changefreq>daily</changefreq>
    <priority>1.0</priority>
  </url>
  <url>
    <loc>https://seusite.com/institucional/historia</loc>
    <lastmod>2024-02-10T10:00:00-03:00</lastmod>
    <changefreq>yearly</changefreq>
    <priority>0.1</priority>
  </url>
</urlset>
```
