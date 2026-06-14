# 11 — Validação e QA

Este documento reúne como verificar a extensão: lint, testes automatizados, build,
instalação no Joomla 6, validação do JSON-LD no Google e um roteiro de testes manuais.

## 1. Lint de sintaxe (PHP)

Verifica erros de sintaxe em todos os `.php` de `src/`.

```bash
# Linux/macOS/Git Bash
bash build/lint.sh
```
```powershell
# Windows
pwsh build/lint.ps1
```

Requer PHP 8.3 no PATH. Os scripts tentam localizar XAMPP/Laragon/WAMP automaticamente.

## 2. Análise estática (PHPStan)

```bash
composer require --dev phpstan/phpstan
vendor/bin/phpstan analyse -c phpstan.neon.dist
```

Como as classes do CMS não estão no repositório, aponte `scanDirectories` para uma
instalação do Joomla 6 (`libraries/`) em `phpstan.neon.dist` para evitar falsos positivos.

## 3. Testes automatizados (motor JSON-LD)

```bash
cd tests
composer install
vendor/bin/phpunit
```

Cobrem os principais tipos de schema. Ver [`tests/README.md`](../../tests/README.md).

## 4. Build do pacote instalável

```bash
bash build/build.sh
```

Gera `dist/pkg_esquemarico.zip` (pacote completo) e os ZIPs individuais em
`dist/packages/`. Requer `zip` no PATH.

## 5. Instalação e teste no Joomla 6

1. **Ambiente**: Joomla 6, PHP 8.3, MySQL 5.7+/MariaDB 10.4+.
2. **Instalar**: Painel → Sistema → Instalar → Enviar Pacote → `dist/pkg_esquemarico.zip`.
   O instalador habilita automaticamente a biblioteca, o plugin de sistema e as integrações.
3. **Verificar plugins**: Sistema → Plugins → confirme habilitados:
   - `Sistema - Esquema Rico (Biblioteca)`
   - `Sistema - Esquema Rico`
   - `Esquema Rico - Conteúdo do Joomla` (e as demais integrações)
4. **Configurar globais**: Componentes → Esquema Rico → Configurações → preencha Nome do
   Site, Logo, Perfis Sociais, etc. → Salvar.
5. **Criar um item**: Esquema Rico → Itens → Novo → Integração "Conteúdo do Joomla",
   Tipo "Artigo", Título → Salvar. Reabra e ajuste o Mapeamento e as Condições (ex.: menu).
6. **Ver no frontend**: abra um artigo no site e inspecione o HTML — deve haver
   `<script type="application/ld+json" data-type="esr">` com o schema.
7. **Depurar**: ative `Debug` no plugin de sistema; um painel ao fim da página (para
   administradores) mostra os itens encontrados e por que cada um foi/não foi renderizado.

## 6. Validar o JSON-LD no Google

Copie o conteúdo do `<script type="application/ld+json">` (ou a URL da página) e valide em:

- **Teste de Resultados Avançados**: https://search.google.com/test/rich-results
- **Validador Schema.org**: https://validator.schema.org/

Pontos a conferir por tipo:
- **Artigo**: `headline` ≤ 110 caracteres, `image` acessível, datas em ISO 8601.
- **Produto**: `offers.price` numérico com ponto decimal, `priceCurrency` ISO 4217
  (ex.: BRL), `availability` como URL `https://schema.org/...`.
- **Evento**: `startDate`/`endDate` em ISO 8601 com fuso, `location` (Place ou
  VirtualLocation).
- **FAQ**: ao menos uma `Question` com `acceptedAnswer`.
- **Breadcrumbs**: `position` sequencial começando em 1; `item` com URL absoluta.

## 7. Roteiro de testes manuais (QA)

| # | Cenário | Resultado esperado |
|---|---------|--------------------|
| 1 | Página inicial com Nome do Site e Logo configurados | `WebSite` e `Organization`(logo) no `<head>` |
| 2 | Página interna (não home) | `BreadcrumbList` presente; `WebSite` ausente |
| 3 | Item Artigo publicado + condição de menu casada | schema `Article` na página do artigo |
| 4 | Mesmo item, condição de menu NÃO casada | schema ausente |
| 5 | Item despublicado (state=0) | schema ausente |
| 6 | Site multilíngue, item em pt-BR, navegando em en-GB | schema ausente em en-GB |
| 7 | Template que emite `BreadcrumbList` próprio + remoção ativa | apenas o nosso `BreadcrumbList` permanece |
| 8 | Tipo Produto com preço mapeado a um campo personalizado | `Offer.price` com o valor do campo |
| 9 | Código Personalizado com `{gsd.item.headline}` | a SmartTag é substituída na saída |
| 10 | Minificar JSON ligado | saída sem quebras de linha desnecessárias |

## 8. Limitações conhecidas (a tratar)

- **Integrações de terceiros** (VirtueMart, HikaShop, JEvents, DPCalendar) leem o banco da
  extensão de origem; os nomes de tabela/coluna foram baseados nas versões usuais e devem
  ser conferidos contra a versão instalada (há `try/catch` para degradar com segurança).
- **Edição rápida** embutida no editor de artigos/menus ainda é um atalho (Fase 6 pendente).
- **Campo de horário de funcionamento** e **construtor de condições avançado** pendentes.
- Sem PHP no ambiente de desenvolvimento atual, o lint/PHPStan/testes ainda não foram
  executados aqui — rode-os antes de publicar.
