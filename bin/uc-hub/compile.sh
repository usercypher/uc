#!/bin/sh

BINARY_NAME=$(basename "$(pwd)")

platforms="windows/amd64 windows/386 darwin/amd64 darwin/arm64 linux/amd64 linux/arm64 android/arm android/arm64"

mkdir -p dist

for platform in $platforms; do
    GOOS="${platform%/*}"
    GOARCH="${platform#*/}"

    OUTPUT_NAME="${BINARY_NAME}-${GOOS}-${GOARCH}"

    if [ "$GOOS" = "windows" ]; then
        OUTPUT_NAME="${OUTPUT_NAME}.exe"
    fi

    echo "Building for ${GOOS}/${GOARCH}..."

    CGO_ENABLED=0 GOOS="$GOOS" GOARCH="$GOARCH" go build -ldflags="-s -w" -o "dist/$OUTPUT_NAME" ./
done
