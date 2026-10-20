#!/bin/sh

cd "$(dirname "$0")"

OS=$(uname -s)
ARCH=$(uname -m)

if [ -f /system/build.prop ]; then
    OS="Android"
fi

case "$OS" in
    Linux|Android)
        case "$ARCH" in
            x86_64)
                exec bin/uc-web/dist/uc-web-linux-amd64 bin/uc-web/uc-web.json
                ;;
            i386|i486|i586|i686)
                exec bin/uc-web/dist/uc-web-linux-386 bin/uc-web/uc-web.json
                ;;
            armv8l|armv7l|arm)
                exec bin/uc-web/dist/uc-web-linux-arm bin/uc-web/uc-web.json
                ;;
            arm64|aarch64)
                exec bin/uc-web/dist/uc-web-linux-arm64 bin/uc-web/uc-web.json
                ;;
            *)
                echo "Unsupported architecture: $ARCH"
                exit 1
                ;;
        esac
        ;;
    Darwin)
        case "$ARCH" in
            x86_64)
                exec bin/uc-web/dist/uc-web-darwin-amd64 bin/uc-web/uc-web.json
                ;;
            arm64)
                exec bin/uc-web/dist/uc-web-darwin-arm64 bin/uc-web/uc-web.json
                ;;
            *)
                echo "Unsupported architecture: $ARCH"
                exit 1
                ;;
        esac
        ;;
    *)
        echo "Unsupported OS: $OS"
        exit 1
        ;;
esac
