#!/usr/bin/env bash
# Gera dist/jpx-eleicoes-2026.zip pronto para "Plugins → Enviar plugin".
set -euo pipefail
cd "$(dirname "$0")/.."
mkdir -p dist
rm -f dist/jpx-eleicoes-2026.zip
zip -rq dist/jpx-eleicoes-2026.zip jpx-eleicoes-2026 -x '*.DS_Store'
echo "dist/jpx-eleicoes-2026.zip"
