#!/bin/sh

cd "$(dirname "$0")"

OS=$(uname -s)
ARCH=$(uname -m)

if [ -f /system/build.prop ]; then
    OS="Android"
fi

case "$OS" in
    Linux)
        case "$ARCH" in
            x86_64)
                exec bin/uc-web/dist/uc-web-linux-amd64 uc-web.json
                ;;
            aarch64)
                exec bin/uc-web/dist/uc-web-linux-arm64 uc-web.json
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
                exec bin/uc-web/dist/uc-web-darwin-amd64 uc-web.json
                ;;
            arm64)
                exec bin/uc-web/dist/uc-web-darwin-arm64 uc-web.json
                ;;
            *)
                echo "Unsupported architecture: $ARCH"
                exit 1
                ;;
        esac
        ;;
    Android)
        case "$ARCH" in
            armv8l|armv7l|arm)
                exec bin/uc-web/dist/uc-web-android-arm uc-web.json
                ;;
            aarch64)
                exec bin/uc-web/dist/uc-web-android-arm64 uc-web.json
                ;;
            *)
                echo "Unsupported Android architecture: $ARCH"
                exit 1
                ;;
        esac
        ;;
    *)
        echo "Unsupported OS: $OS"
        exit 1
        ;;
esac
