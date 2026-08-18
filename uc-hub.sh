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
                exec bin/uc-hub/dist/uc-hub-linux-amd64 bin/uc-hub/uc-hub.json
                ;;
            i386|i486|i586|i686)
                exec bin/uc-hub/dist/uc-hub-linux-386 bin/uc-hub/uc-hub.json
                ;;
            armv8l|armv7l|arm)
                exec bin/uc-hub/dist/uc-hub-linux-arm bin/uc-hub/uc-hub.json
                ;;
            arm64|aarch64)
                exec bin/uc-hub/dist/uc-hub-linux-arm64 bin/uc-hub/uc-hub.json
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
                exec bin/uc-hub/dist/uc-hub-darwin-amd64 bin/uc-hub/uc-hub.json
                ;;
            arm64)
                exec bin/uc-hub/dist/uc-hub-darwin-arm64 bin/uc-hub/uc-hub.json
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
