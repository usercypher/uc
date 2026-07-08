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
                exec bin/uc-fcgi/dist/uc-fcgi-linux-amd64 uc-fcgi.json
                ;;
            aarch64)
                exec bin/uc-fcgi/dist/uc-fcgi-linux-arm64 uc-fcgi.json
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
                exec bin/uc-fcgi/dist/uc-fcgi-darwin-amd64 uc-fcgi.json
                ;;
            arm64)
                exec bin/uc-fcgi/dist/uc-fcgi-darwin-arm64 uc-fcgi.json
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
                exec bin/uc-fcgi/dist/uc-fcgi-android-arm uc-fcgi.json
                ;;
            aarch64)
                exec bin/uc-fcgi/dist/uc-fcgi-android-arm64 uc-fcgi.json
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
