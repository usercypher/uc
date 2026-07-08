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
                exec bin/uc-hub/dist/uc-hub-linux-amd64 uc-hub.json
                ;;
            aarch64)
                exec bin/uc-hub/dist/uc-hub-linux-arm64 uc-hub.json
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
                exec bin/uc-hub/dist/uc-hub-darwin-amd64 uc-hub.json
                ;;
            arm64)
                exec bin/uc-hub/dist/uc-hub-darwin-arm64 uc-hub.json
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
                exec bin/uc-hub/dist/uc-hub-android-arm uc-hub.json
                ;;
            aarch64)
                exec bin/uc-hub/dist/uc-hub-android-arm64 uc-hub.json
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
