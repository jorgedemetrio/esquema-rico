#!/usr/bin/env bash
#
# Esquema Rico - Lint de sintaxe PHP (Linux/macOS/Git Bash)
#
# Roda `php -l` em todos os arquivos .php de src/. Requer PHP no PATH.
#
# Uso:  bash build/lint.sh

set -uo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC="$ROOT/src"

if ! command -v php >/dev/null 2>&1; then
    echo "PHP não encontrado no PATH. Instale o PHP 8.3."
    exit 1
fi

echo "Usando PHP: $(command -v php) ($(php -r 'echo PHP_VERSION;'))"

errors=0
count=0
while IFS= read -r -d '' f; do
    count=$((count + 1))
    if ! out="$(php -l "$f" 2>&1)"; then
        echo "ERRO: $f"
        echo "$out"
        errors=$((errors + 1))
    fi
done < <(find "$SRC" -name '*.php' -print0)

echo ""
if [ "$errors" -eq 0 ]; then
    echo "OK: $count arquivos sem erros de sintaxe."
else
    echo "$errors arquivo(s) com erro de sintaxe."
    exit 1
fi
