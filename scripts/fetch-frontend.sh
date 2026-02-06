#!/usr/bin/env bash
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "$0")/.." && pwd)"
URL="${FRONTEND_URL:-https://github.com/sergunik/bioreport-canvas/releases/latest/download/frontend.tar.gz}"

rm -rf "${REPO_ROOT}/frontend/public/"*
curl -L "$URL" -o /tmp/frontend.tar.gz
tar -xzf /tmp/frontend.tar.gz -C "${REPO_ROOT}/frontend/public/"
rm /tmp/frontend.tar.gz
