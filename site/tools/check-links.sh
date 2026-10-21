#!/usr/bin/env bash
# Checks every absolute link in the homepage, header and footer sources.
# Run from a normal internet connection: homepage/tools/check-links.sh
# Exit code is non-zero if any link does not return HTTP 2xx/3xx.
set -uo pipefail
cd "$(dirname "$0")/.."
fail=0
for url in $(grep -ohE 'href="https?://[^"]+"' src/*.html | sed 's/^href="//; s/"$//; s/&amp;/\&/g' | sort -u); do
  code=$(curl -sS -o /dev/null -L -A "Mozilla/5.0 (link check)" -w '%{http_code}' --max-time 20 "$url" || echo 000)
  case "$code" in 2*|3*) printf 'OK   %s  %s\n' "$code" "$url" ;;
    *) printf 'FAIL %s  %s\n' "$code" "$url"; fail=1 ;; esac
done
exit $fail
