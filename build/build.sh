#!/usr/bin/env bash
#
# Build do Esquema Rico — gera os ZIPs instaláveis em dist/.
#
# Uso:  bash build/build.sh
#
# Produz:
#   dist/packages/plg_system_esquemaricocore.zip
#   dist/packages/com_esquemarico.zip
#   dist/packages/plg_system_esquemarico.zip
#   dist/packages/plg_esquemarico_content.zip
#   dist/pkg_esquemarico.zip   (pacote completo que instala tudo)

set -euo pipefail

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
SRC="$ROOT/src"
DIST="$ROOT/dist"
PKGDIR="$DIST/packages"

# Versão opcional (1º argumento). Quando informada, ajusta <version> em todos os
# manifestos (pacote + extensões) antes de empacotar. Aceita "v1.2.3" ou "1.2.3".
VERSION="${1:-}"
if [ -n "$VERSION" ]; then
  PLAIN="${VERSION#v}"
  echo ">> Ajustando <version> para $PLAIN nos manifestos"
  while IFS= read -r mf; do
    if grep -q '<extension' "$mf"; then
      sed -i "s|<version>[^<]*</version>|<version>$PLAIN</version>|" "$mf"
    fi
  done < <(find "$SRC" -maxdepth 2 -name '*.xml')
fi

rm -rf "$DIST"
mkdir -p "$PKGDIR"

# Extensões individuais: pasta de origem -> nome do zip
declare -A EXT=(
  ["plg_system_esquemaricocore"]="plg_system_esquemaricocore"
  ["com_esquemarico"]="com_esquemarico"
  ["plg_system_esquemarico"]="plg_system_esquemarico"
  ["plg_esquemarico_content"]="plg_esquemarico_content"
  ["plg_esquemarico_menus"]="plg_esquemarico_menus"
  ["plg_esquemarico_k2"]="plg_esquemarico_k2"
  ["plg_esquemarico_virtuemart"]="plg_esquemarico_virtuemart"
  ["plg_esquemarico_jevents"]="plg_esquemarico_jevents"
  ["plg_esquemarico_hikashop"]="plg_esquemarico_hikashop"
  ["plg_esquemarico_dpcalendar"]="plg_esquemarico_dpcalendar"
  ["plg_content_esquemaricokeywords"]="plg_content_esquemaricokeywords"
  ["plg_content_esquemaricoseo"]="plg_content_esquemaricoseo"
)

for dir in "${!EXT[@]}"; do
  zipname="${EXT[$dir]}"
  echo ">> Empacotando $dir -> $zipname.zip"
  (cd "$SRC/$dir" && zip -rq "$PKGDIR/$zipname.zip" . -x '*.DS_Store')
done

# Pacote: monta staging com manifesto + script + idioma + packages
echo ">> Montando o pacote pkg_esquemarico"
STAGE="$(mktemp -d)"
cp "$SRC/pkg_esquemarico/pkg_esquemarico.xml" "$STAGE/"
cp "$SRC/pkg_esquemarico/script.php" "$STAGE/"
cp -r "$SRC/pkg_esquemarico/language" "$STAGE/"
mkdir -p "$STAGE/packages"
cp "$PKGDIR"/*.zip "$STAGE/packages/"

(cd "$STAGE" && zip -rq "$DIST/pkg_esquemarico.zip" . -x '*.DS_Store')
rm -rf "$STAGE"

echo ""
echo "Build concluído. Artefatos em: $DIST"
ls -1 "$DIST" "$PKGDIR"
