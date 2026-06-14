#!/usr/bin/env bash
#
# validar.sh — Gate de pré-publicação do Esquema Rico (Joomla 6 / PHP 8.3 / MySQL 5).
#
# Roda as checagens automatizáveis antes de entregar na master / gerar o pacote.
# Diferente de uma extensão Joomla 3, AQUI o código é MODERNO E NAMESPACED:
#   - proíbe APIs legadas (JFactory, JText, JRoute, jimport, *Legacy);
#   - exige guarda _JEXEC;
#   - SQL compatível com MySQL 5 (índice em VARCHAR <= 191; sem sintaxe MariaDB);
#   - paridade de idiomas pt-BR <-> en-GB.
#
# Uso:
#   .claude/skills/validacao-pre-producao/validar.sh            # repo inteiro
#   .claude/skills/validacao-pre-producao/validar.sh --changed  # só PHP do git diff
#   .claude/skills/validacao-pre-producao/validar.sh --quick    # pula phpstan/phpcs
#
# Sai != 0 se houver qualquer FAIL. AVISO/INFO são heurística (revise à mão).

set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../../.." && pwd)"
cd "$ROOT" || exit 2

SRC="src"
INSTALL_SQL="src/com_esquemarico/admin/sql/install.mysql.utf8.sql"
UNINSTALL_SQL="src/com_esquemarico/admin/sql/uninstall.mysql.utf8.sql"

CHANGED=0; QUICK=0
for a in "$@"; do
  case "$a" in
    --changed) CHANGED=1 ;;
    --quick)   QUICK=1 ;;
  esac
done

FAILS=0; WARNS=0; PASSES=0
c_red(){ printf '\033[31m%s\033[0m' "$1"; }
c_grn(){ printf '\033[32m%s\033[0m' "$1"; }
c_ylw(){ printf '\033[33m%s\033[0m' "$1"; }
section(){ printf '\n=== %s ===\n' "$1"; }
pass(){ PASSES=$((PASSES+1)); printf '  [%s] %s\n' "$(c_grn PASS)" "$1"; }
warn(){ WARNS=$((WARNS+1));   printf '  [%s] %s\n' "$(c_ylw WARN)" "$1"; }
fail(){ FAILS=$((FAILS+1));   printf '  [%s] %s\n' "$(c_red FAIL)" "$1"; }

# ---------------------------------------------------------------------------
section "1. Sintaxe PHP (php -l)"
if command -v php >/dev/null 2>&1; then
  if [[ "$CHANGED" -eq 1 ]]; then
    mapfile -t PHP_FILES < <(git diff --name-only --diff-filter=d HEAD -- '*.php' 2>/dev/null)
  else
    mapfile -t PHP_FILES < <(find "$SRC" tests -name '*.php' 2>/dev/null)
  fi
  errs=0
  for f in "${PHP_FILES[@]}"; do
    [[ -f "$f" ]] || continue
    if ! out="$(php -l "$f" 2>&1)"; then fail "php -l: $f"; printf '       %s\n' "$out"; errs=1; fi
  done
  [[ "$errs" -eq 0 ]] && pass "${#PHP_FILES[@]} arquivo(s) PHP sem erro de sintaxe"
else
  warn "php não encontrado no PATH — pulei php -l"
fi

# ---------------------------------------------------------------------------
section "2. Convenções Joomla 6 (APIs legadas são FAIL)"
# Código moderno: namespaced + `use`. Nada de J3.
LEGACY_RE='\b(JFactory|JText|JRoute|JHtml|JController(Legacy|Form|Admin)?|JModel(Legacy|List|Admin|Form)?|JViewLegacy|JTable|JToolbarHelper|JPluginHelper|JComponentHelper|JUri|JLog)\b|jimport\s*\('
legacy_hits="$(grep -rEnl "$LEGACY_RE" "$SRC" --include='*.php' 2>/dev/null || true)"
if [[ -n "$legacy_hits" ]]; then
  fail "API legada (J3) encontrada — use as classes namespaced Joomla\\CMS\\*:"
  echo "$legacy_hits" | sed 's/^/       /'
else
  pass "Sem APIs legadas J3 (JFactory/JText/jimport/...)"
fi

# Factory::getUser() é depreciado no J6 → use \$app->getIdentity()/UserFactory.
if grep -rEn 'Factory::getUser\s*\(' "$SRC" --include='*.php' >/dev/null 2>&1; then
  warn "Factory::getUser() (depreciado no J6) — prefira \$app->getIdentity() ou UserFactoryInterface"
else
  pass "Sem Factory::getUser() depreciado"
fi
# CMSObject foi removido no J6.
if grep -rEn '\bCMSObject\b' "$SRC" --include='*.php' >/dev/null 2>&1; then
  fail "CMSObject foi removido no Joomla 6 — use \\stdClass / DTOs próprios"
else
  pass "Sem CMSObject (removido no J6)"
fi
# Guarda _JEXEC em todo PHP de src (exceto provider/autoload que também a têm).
missing_jexec="$(grep -rLE "defined\(['\"]_JEXEC['\"]\)|_JEXEC['\"]\s*\)\s*or" "$SRC" --include='*.php' 2>/dev/null || true)"
if [[ -n "$missing_jexec" ]]; then
  warn "Arquivos PHP sem guarda _JEXEC:"; echo "$missing_jexec" | sed 's/^/       /'
else
  pass "Todos os PHP de src têm guarda _JEXEC"
fi

# ---------------------------------------------------------------------------
section "3. Análise estática (PHPStan / PHPCS PSR-12)"
if [[ "$QUICK" -eq 1 ]]; then
  warn "--quick: PHPStan/PHPCS pulados"
else
  PHPSTAN=""
  for c in vendor/bin/phpstan ~/.composer/vendor/bin/phpstan phpstan; do
    command -v "$c" >/dev/null 2>&1 && { PHPSTAN="$c"; break; }
  done
  if [[ -n "$PHPSTAN" ]]; then
    cfg=""; [[ -f phpstan.neon.dist ]] && cfg="-c phpstan.neon.dist"
    if "$PHPSTAN" analyse $cfg --no-progress --error-format=raw "$SRC" >/tmp/phpstan_out.txt 2>/dev/null; then
      pass "PHPStan sem erros"
    else
      n="$(grep -cE ':[0-9]+:' /tmp/phpstan_out.txt 2>/dev/null || echo 0)"
      warn "PHPStan apontou ~$n itens (advisory) — veja /tmp/phpstan_out.txt"
    fi
  else
    warn "PHPStan não instalado — roda na CI (build.yml)"
  fi
  PHPCS=""
  for c in vendor/bin/phpcs ~/.composer/vendor/bin/phpcs phpcs; do
    command -v "$c" >/dev/null 2>&1 && { PHPCS="$c"; break; }
  done
  if [[ -n "$PHPCS" ]]; then
    std=""; [[ -f phpcs.xml.dist ]] && std="--standard=phpcs.xml.dist"
    if "$PHPCS" $std -q "$SRC" >/tmp/phpcs_out.txt 2>&1; then
      pass "PHPCS (PSR-12) sem violações"
    else
      warn "PHPCS apontou itens de estilo (advisory) — veja /tmp/phpcs_out.txt"
    fi
  else
    warn "PHPCS não instalado"
  fi
fi

# ---------------------------------------------------------------------------
section "4. Idiomas (pt-BR principal; paridade com en-GB)"
# 4a. Chaves ESR_*/COM_ESQUEMARICO_* usadas no admin mas não definidas em pt-BR.
admin="src/com_esquemarico/admin"
if [[ -d "$admin/language/pt-BR" ]]; then
  # Ignora prefixos construídos dinamicamente no PHP (terminam em "_"); nenhuma
  # chave de idioma real termina em underscore (ex.: ESR_CONTENT_TYPE_, ESR_SEO_).
  ref="$(grep -rhoE '\b(ESR_[A-Z0-9_]+|COM_ESQUEMARICO_[A-Z0-9_]+)\b' "$admin/forms" "$admin/tmpl" "$admin/src" 2>/dev/null | grep -vE '_$' | sort -u)"
  defined="$(grep -hoE '^[A-Z0-9_]+' "$admin"/language/pt-BR/com_esquemarico.ini "$admin"/language/pt-BR/com_esquemarico.sys.ini 2>/dev/null | sort -u)"
  miss=0
  while IFS= read -r k; do
    [[ -z "$k" ]] && continue
    echo "$defined" | grep -qx "$k" || { fail "i18n: chave usada mas inexistente em pt-BR: $k"; miss=1; }
  done <<< "$ref"
  [[ "$miss" -eq 0 ]] && pass "i18n pt-BR: todas as chaves usadas estão definidas"
else
  warn "pt-BR do componente não encontrado"
fi
# 4b. Todo arquivo de idioma referenciado nos manifestos existe.
#     Um manifesto pode ter VÁRIOS blocos <languages folder="...">; cada
#     <language> é resolvido contra o folder do bloco que o contém.
miss_lang=0
while IFS= read -r mf; do
  grep -q '<extension' "$mf" || continue
  dir="$(dirname "$mf")"
  folder=""
  while IFS= read -r line; do
    if [[ "$line" =~ \<languages[^\>]*folder=\"([^\"]*)\" ]]; then
      folder="${BASH_REMATCH[1]}"
    fi
    if [[ "$line" =~ \<language[^\>]*\>([^\<]+)\</language\> ]]; then
      rel="${BASH_REMATCH[1]}"
      [[ -f "$dir/$folder/$rel" ]] || { fail "i18n: arquivo de idioma ausente: $dir/$folder/$rel (em $mf)"; miss_lang=1; }
    fi
  done < "$mf"
done < <(find "$SRC" -maxdepth 2 -name '*.xml')
[[ "$miss_lang" -eq 0 ]] && pass "Todos os arquivos de idioma referenciados nos manifestos existem"

# ---------------------------------------------------------------------------
section "5. SQL (MySQL 5 / utf8mb4 / InnoDB)"
if [[ -f "$INSTALL_SQL" ]]; then
  # 5a. ALTER de coluna é proibido (coluna nova vai no CREATE TABLE).
  if grep -Pzoi 'ALTER\s+TABLE[^;]*?\b(ADD\s+COLUMN|MODIFY\b|CHANGE\s+COLUMN|DROP\s+COLUMN)' "$INSTALL_SQL" >/dev/null 2>&1; then
    fail "install: ALTER TABLE alterando COLUNA — consolide no CREATE TABLE"
  else
    pass "install.sql sem ALTER de coluna"
  fi
  # 5b. Sintaxe MariaDB (IF [NOT] EXISTS em coluna) — produção pode ser MySQL 5.x.
  if grep -qiE 'ADD\s+COLUMN\s+IF\s+NOT\s+EXISTS|DROP\s+COLUMN\s+IF\s+EXISTS|MODIFY\s+COLUMN\s+IF' "$INSTALL_SQL"; then
    fail "install: sintaxe MariaDB (IF [NOT] EXISTS em coluna) — incompatível com MySQL 5"
  else
    pass "install.sql sem sintaxe MariaDB"
  fi
  # 5c. InnoDB + utf8mb4.
  grep -qiE 'ENGINE\s*=\s*InnoDB' "$INSTALL_SQL" && pass "install.sql usa InnoDB" || warn "install.sql sem ENGINE=InnoDB explícito"
  grep -qiE 'CHARSET\s*=\s*utf8mb4' "$INSTALL_SQL" && pass "install.sql usa utf8mb4" || warn "install.sql sem utf8mb4"
  # 5d. Default datetime '0000-00-00' (inválido no sql_mode estrito do MySQL recente).
  if grep -qiE "DATETIME[^,]*DEFAULT\s*'0000-00-00" "$INSTALL_SQL"; then
    fail "install: DATETIME DEFAULT '0000-00-00' — use NULL (sql_mode estrito)"
  else
    pass "install.sql sem datetime '0000-00-00'"
  fi
  # 5e. Limite de índice utf8mb4 (767 bytes): VARCHAR indexado deve ser <= 191.
  #     Heurística: colunas KEY/UNIQUE/PRIMARY sobre VARCHAR(>191).
  big=0
  while IFS= read -r col; do
    name="$(echo "$col" | grep -oE '`[a-zA-Z0-9_]+`' | head -1 | tr -d '`')"
    size="$(echo "$col" | grep -oiE 'VARCHAR\(([0-9]+)\)' | grep -oE '[0-9]+' | head -1)"
    [[ -z "$name" || -z "$size" ]] && continue
    if (( size > 191 )); then
      # A coluna está em algum índice?
      if grep -qiE "(KEY|INDEX|UNIQUE)[^(]*\(\`?$name\`?" "$INSTALL_SQL"; then
        fail "índice utf8mb4 em VARCHAR($size) > 191 na coluna \`$name\` (estoura 767 bytes no MySQL 5)"; big=1
      fi
    fi
  done < <(grep -iE '`[a-zA-Z0-9_]+`\s+VARCHAR\([0-9]+\)' "$INSTALL_SQL")
  [[ "$big" -eq 0 ]] && pass "Sem índice em VARCHAR > 191 (compatível com MySQL 5)"
else
  fail "install.sql não encontrado em $INSTALL_SQL"
fi
if [[ -f "$UNINSTALL_SQL" ]]; then
  grep -qiE 'DROP\s+TABLE\s+IF\s+EXISTS' "$UNINSTALL_SQL" && pass "uninstall.sql usa DROP TABLE IF EXISTS" || warn "uninstall.sql sem DROP TABLE IF EXISTS"
else
  warn "uninstall.sql não encontrado em $UNINSTALL_SQL"
fi

# ---------------------------------------------------------------------------
section "6. Estrutura dos artefatos (provider + Extension)"
struct=0
for d in "$SRC"/plg_* "$SRC"/com_esquemarico; do
  [[ -d "$d" ]] || continue
  [[ -n "$(find "$d" -name 'provider.php' 2>/dev/null | head -1)" ]] || { fail "Sem services/provider.php em $d"; struct=1; }
  [[ -n "$(find "$d" -path '*/src/Extension/*.php' 2>/dev/null | head -1)" ]] || { fail "Sem classe src/Extension/*.php em $d"; struct=1; }
done
[[ "$struct" -eq 0 ]] && pass "Todos os plugins/componente têm provider + classe Extension"

# ---------------------------------------------------------------------------
section "RESUMO"
printf '  %s passes / %s warns / %s fails\n' "$(c_grn "$PASSES")" "$(c_ylw "$WARNS")" "$(c_red "$FAILS")"
if [[ "$FAILS" -gt 0 ]]; then
  printf '\n%s — corrija os FAIL antes de entregar na master / gerar o pacote.\n' "$(c_red 'BLOQUEADO')"
  exit 1
fi
printf '\n%s — sem FAIL. Revise os WARN à mão e percorra o CHECKLIST.md.\n' "$(c_grn 'OK')"
exit 0
