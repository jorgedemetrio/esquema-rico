---
name: validacao-pre-producao
description: Gate de pré-publicação do Esquema Rico (Joomla 6 / PHP 8.3 / MySQL 5). Rode antes de mergear/abrir PR para master ou gerar o pacote. Orquestra php -l, convenções J6, PHPStan/PHPCS, idiomas e SQL MySQL 5.
---

# Validação de pré-produção — Esquema Rico

Gate determinístico que roda as checagens automatizáveis do `CHECKLIST.md` antes de
entregar na `master` ou gerar o pacote instalável.

## Quando usar

- Antes de abrir/mergear PR para `master`.
- Antes de `bash build/build.sh` (gerar o pacote).
- Roda também na CI: `.github/workflows/validacao-pre-master.yml`.

## Como rodar

```bash
.claude/skills/validacao-pre-producao/validar.sh            # gate completo (sai !=0 em FAIL)
.claude/skills/validacao-pre-producao/validar.sh --changed  # só PHP do git diff
.claude/skills/validacao-pre-producao/validar.sh --quick    # pula phpstan/phpcs
```

## O que o gate cobre (FAIL bloqueia; WARN/INFO são heurística)

1. **Sintaxe PHP** (`php -l`) em `src/` e `tests/`.
2. **Convenções Joomla 6** — FAIL em API legada J3 (`JFactory`, `JText`, `JRoute`,
   `jimport`, `*Legacy`, `CMSObject`); WARN em `Factory::getUser()` (depreciado);
   guarda `_JEXEC` presente.
3. **Análise estática** — PHPStan (`phpstan.neon.dist`) e PHPCS PSR-12
   (`phpcs.xml.dist`), ambos advisory.
4. **Idiomas** — chaves `ESR_*`/`COM_ESQUEMARICO_*` usadas mas inexistentes em pt-BR
   (FAIL); todo arquivo de idioma referenciado em manifesto deve existir (FAIL).
5. **SQL (MySQL 5)** — sem ALTER de coluna; sem sintaxe MariaDB (`IF [NOT] EXISTS` em
   coluna); InnoDB + utf8mb4; sem `DATETIME DEFAULT '0000-00-00'`; **índice em VARCHAR
   ≤ 191** (limite de 767 bytes do InnoDB com utf8mb4).
6. **Estrutura** — cada plugin/componente tem `services/provider.php` + classe
   `src/Extension/*.php`.

Os testes do motor JSON-LD (`tests/`) e o Playwright (se houver) ficam **fora** deste
gate — são passos próprios.

Detalhes e itens manuais: ver `CHECKLIST.md`.
