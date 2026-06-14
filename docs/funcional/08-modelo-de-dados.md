# 08 — Modelo de dados

A extensão persiste seus dados em duas tabelas. O motor de mapeamento e os esquemas
globais não criam outras estruturas: tudo que é configurável vive nestas duas tabelas (e
nos parâmetros dos plugins).

## Tabela `#__esquemarico` — itens de marcação

Cada linha é um **item de marcação** criado pelo usuário.

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `id` | INT UNSIGNED, PK, auto | Identificador |
| `title` | VARCHAR(190) | Título administrativo do item |
| `contenttype` | VARCHAR(50) | Tipo de schema (`article`, `product`, `event`, …) |
| `params` | MEDIUMTEXT (JSON) | Mapeamentos do tipo, condições e demais opções |
| `plugin` | VARCHAR(50) | Alias da integração (`content`, `k2`, `virtuemart`, …) |
| `appview` | VARCHAR(50) | *View* suportada da integração (`*` = todas) |
| `created` | DATETIME | Data de criação |
| `created_by` | INT UNSIGNED | Autor |
| `modified` | DATETIME | Última alteração |
| `modified_by` | INT UNSIGNED | Quem alterou |
| `ordering` | INT | Ordenação |
| `language` | VARCHAR(7) | Idioma (`*` = todos) |
| `note` | VARCHAR(255) | Nota interna |
| `state` | TINYINT | Estado: 1 publicado, 0 despublicado, -2 lixeira |
| `checked_out` | INT UNSIGNED | Bloqueio de edição (usuário) |
| `checked_out_time` | DATETIME | Quando foi bloqueado |

Índices: PK em `id`; índices em `state`, `plugin` e `language` (consultas do frontend
filtram por essas colunas).

### Formato de `params`

`params` é um JSON (Registry do Joomla) que agrega:

- **`<contenttype>`**: subobjeto com os mapeamentos do tipo. Cada propriedade mapeável é
  um objeto `{ "option": "<modo|origem>", "fixed": "…", "custom": "…", "css_selector":
  "…" }` conforme o modo escolhido (ver [05](05-mapeamento-e-smarttags.md)).
- **`assignments`**: as condições de publicação, organizadas por alias de condição, cada
  uma com `assignment_state`, `selection` e `params` (ver [06](06-condicoes-publicacao.md)).

Exemplo simplificado para um item Produto via conteúdo nativo:

```json
{
  "product": {
    "name":        { "option": "gsd.item.headline" },
    "description": { "option": "gsd.item.description" },
    "image":       { "option": "gsd.item.image" },
    "sku":         { "option": "gsd.item.cf.sku" },
    "brand":       { "option": "fixed", "fixed": "Acme" },
    "offerPrice":  { "option": "gsd.item.cf.preco" },
    "currency":    { "option": "fixed", "fixed": "BRL" }
  },
  "assignments": {
    "menu": { "assignment_state": "1", "selection": ["15"], "params": { "include_children": "1" } }
  }
}
```

## Tabela `#__esquemarico_config` — configurações

Armazena pares chave→parâmetros para configurações que não pertencem a um item específico.

| Coluna | Tipo | Descrição |
|--------|------|-----------|
| `name` | VARCHAR(190), PK | Chave de configuração (ex.: `config`) |
| `params` | MEDIUMTEXT (JSON) | Os parâmetros serializados |

Na prática, há uma linha `config` que guarda **todos os esquemas globais e opções
avançadas** (nome do site, breadcrumbs, logo, perfis sociais, negócio local, remoção de
microdados, código personalizado, minificação, debug, etc.). Essa abordagem chave→params
permite adicionar novas configurações sem alterar o esquema da tabela.

## Convenções de compatibilidade (MySQL 5)

- **Engine**: `InnoDB` (suporte a transações e chaves estrangeiras).
- **Charset/Collation**: `utf8mb4` / `utf8mb4_unicode_ci` (suporte completo a emojis e
  acentuação).
- **Tamanho de colunas indexadas**: VARCHAR de colunas indexadas ≤ 190 caracteres, para
  caber no limite de 767 bytes de índice de versões antigas do MySQL 5.x sem depender de
  `innodb_large_prefix`.
- **`ROW_FORMAT=DYNAMIC`** na tabela de config (linhas com `MEDIUMTEXT`).
- **Sem recursos exclusivos de MySQL 8** (sem `CHECK` constraints funcionais, sem funções
  JSON nativas obrigatórias). O JSON é tratado na camada PHP, não no SQL.
- **Datas**: colunas `DATETIME` aceitam `NULL` em vez do antigo *default*
  `'0000-00-00 00:00:00'`, compatível com o `sql_mode` estrito padrão das versões
  recentes.

## Scripts SQL

- `sql/install.mysql.utf8.sql` — criação das tabelas na instalação.
- `sql/uninstall.mysql.utf8.sql` — remoção na desinstalação.
- `sql/updates/mysql/*.sql` — migrações incrementais por versão (mecanismo de *schema
  updates* do Joomla).
