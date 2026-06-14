#!/bin/bash
#
# deploy.sh — Empacota e publica o Esquema Rico (pacote Joomla 6) via FTPS,
# gerando/atualizando o XML do servidor de atualização do Joomla.
#
# Uso: deploy.sh <versao>          (ex.: deploy.sh v1.0.0)
#
# Alvo: Joomla 6 (targetplatform). Código namespaced compatível com J5.1+/J6.
# Banco MySQL 5 (utf8mb4/InnoDB) e PHP 8.1+ (alvo 8.3).

set -e

if [ -z "${1:-}" ]; then
  echo "Erro: versão não fornecida. Uso: $0 <versao>"; exit 1
fi

VERSION="$1"
PLAIN_VERSION="${VERSION#v}"
APP_NAME="pkg_esquemarico"
BASE_URL="https://apps.sobieskiproducoes.com.br/${APP_NAME}"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
ZIP_FILE="${APP_NAME}-${PLAIN_VERSION}.zip"

echo "Iniciando deploy de ${APP_NAME} versão ${PLAIN_VERSION}"

# --- Build do pacote (bumpa <version> em todos os manifestos) ---
echo "Gerando o pacote com build/build.sh ${VERSION}..."
bash "${ROOT}/build/build.sh" "${VERSION}"
cp "${ROOT}/dist/pkg_esquemarico.zip" "${ROOT}/${ZIP_FILE}"
cd "${ROOT}"
echo "Pacote pronto: ${ZIP_FILE}"

# --- Entrada de atualização do Joomla (foco em Joomla 6) ---
echo "Gerando a entrada de atualização..."
cat > nova_entrada.xml << EOL
    <update>
        <name>Esquema Rico</name>
        <element>${APP_NAME}</element>
        <type>package</type>
        <version>${PLAIN_VERSION}</version>
        <infourl title="Esquema Rico">${BASE_URL}/atualizacao.xml</infourl>
        <downloads>
            <downloadurl type="full" format="zip">${BASE_URL}/${ZIP_FILE}</downloadurl>
        </downloads>
        <tags>
            <tag>stable</tag>
        </tags>
        <targetplatform name="joomla" version="(6)\.*"/>
        <php_minimum>8.1</php_minimum>
        <supported_databases mysql="5.7" mariadb="10.4"/>
    </update>
EOL

# --- Combina com o atualizacao.xml remoto (se existir) ---
FILE_EXISTS=true
wget -O atualizacao_remota.xml "${BASE_URL}/atualizacao.xml" > /dev/null 2>&1 || FILE_EXISTS=false
if [ "$FILE_EXISTS" = false ]; then
  echo "atualizacao.xml inexistente no servidor — criando novo."
  (
    echo '<?xml version="1.0" encoding="utf-8"?>'
    echo '<updates>'
    cat nova_entrada.xml
    echo '</updates>'
  ) > atualizacao.xml
else
  echo "atualizacao.xml encontrado — adicionando nova entrada."
  sed '2r nova_entrada.xml' atualizacao_remota.xml > atualizacao.xml
fi

# --- Deploy via FTPS ---
if [ -z "${FTP_URL:-}" ] || [ -z "${FTP_USER:-}" ] || [ -z "${FTP_PASSWORD:-}" ]; then
  echo "Erro: configure FTP_URL, FTP_USER e FTP_PASSWORD."; exit 1
fi
echo "Enviando via FTPS para ${APP_NAME}/ ..."
lftp -c "set sftp:auto-confirm yes; set ftp:ssl-allow yes; set ssl:verify-certificate no;
open -u ${FTP_USER},${FTP_PASSWORD} ${FTP_URL};
mkdir -p /${APP_NAME};
cd /${APP_NAME};
put -O . ${ZIP_FILE};
put -O . atualizacao.xml;
bye"

echo "Deploy concluído."

# --- Limpeza ---
rm -f "${ZIP_FILE}" nova_entrada.xml atualizacao.xml atualizacao_remota.xml
echo "Finalizado."
