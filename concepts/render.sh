#!/usr/bin/env bash
# Rebuilds every mockup and presentation board PNG in renders/.
set -euo pipefail
cd "$(dirname "$0")"
R="node render.mjs"; S="$PWD/src"; O="$PWD/renders"
export PLAYWRIGHT_MODULE="${PLAYWRIGHT_MODULE:-/opt/node22/lib/node_modules/playwright}"
python3 build_boards.py
$R "$S/a-desktop.html" "$O/a-desktop-hero.png" 1440 900
$R "$S/a-desktop.html" "$O/a-desktop-full.png" 1440 full
$R "$S/a-mobile.html"  "$O/a-mobile-first-screen.png" 390 844 2
HIDE_FIXED=1 $R "$S/a-mobile.html" "$O/a-mobile.png" 390 full 2
for d in b c; do
  $R "$S/$d-desktop.html" "$O/$d-desktop-hero.png" 1440 900
  $R "$S/$d-mobile.html"  "$O/$d-mobile.png" 390 full 2
done
for b in 0-overview a b c; do $R "$S/board-$b.html" "$O/board-$b.png" 1600 full 1; done
echo "renders updated"
