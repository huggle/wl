#!/bin/bash
#Author Addshore

set -euo pipefail
cd "$(dirname "$0")/.."
while IFS= read -r -d '' file; do
    php -l "$file"
done < <(find src tests -type f -name '*.php' -print0)
