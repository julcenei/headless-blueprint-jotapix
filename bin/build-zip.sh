#!/usr/bin/env bash
# Gera dist/novafm-eleicoes-tse.zip pronto para "Plugins → Enviar plugin".
set -euo pipefail
cd "$(dirname "$0")/.."
mkdir -p dist
rm -f dist/novafm-eleicoes-tse.zip
zip -rq dist/novafm-eleicoes-tse.zip novafm-eleicoes-tse -x '*.DS_Store'
echo "dist/novafm-eleicoes-tse.zip"
