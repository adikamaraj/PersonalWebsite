#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")/.."
if [ ! -f .env ]; then cp .env.example .env; fi
chmod 600 .env
find wp-content/themes/adi-personal wp-content/plugins/adi-site-content -type d -exec chmod 755 {} +
find wp-content/themes/adi-personal wp-content/plugins/adi-site-content -type f -exec chmod 644 {} +
docker compose up -d
