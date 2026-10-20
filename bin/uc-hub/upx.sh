#!/bin/sh

upx="$1"

for f in dist/*; do
    [ -f "$f" ] || continue
    "$upx" --best --lzma -- "$f"
done