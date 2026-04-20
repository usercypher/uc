# 3LP (ELP Stack): [L]inux + [L]ighttpd + sq[L]ite + [P]HP

A lightweight, efficient web stack optimized for simplicity and performance.

---

## 1. Install Dependencies

### Debian/Ubuntu
```bash
sudo apt update
sudo apt install lighttpd php-fpm php-sqlite3
```

### Red Hat/CentOS/Fedora
```bash
sudo dnf install lighttpd php-fpm php-pdo php-sqlite
```

For CentOS 7:
```bash
sudo yum install lighttpd php-fpm php-pdo php-sqlite
```

### Arch Linux
```bash
sudo pacman -S lighttpd php-fpm php-sqlite
```

### Alpine Linux
```bash
apk add lighttpd php82-fpm php82-pdo_sqlite
```

### openSUSE
```bash
sudo zypper install lighttpd php-fpm php-pdo php-sqlite
```

---

## 2. Clone UC Framework Repository

```bash
sudo git clone https://github.com/usercypher/uc.git
cd uc
```

---

## 3. Setup UC Framework

Copy the default configuration file:

```bash
sudo cp config.php.default config.php
```

Compile routes and units:

```bash
sudo php bin/compile.php
```

This serializes your application's routes and units for optimized performance. **Run this command every time you change routes or units.**

---

## 4. Start the Project

```bash
sudo chmod +x boot.sh
sudo ./boot.sh
```

**If the boot script fails due to PHP version mismatch:**

Find your installed PHP-FPM version then run the boot script with your version:

```bash
sudo ./boot.sh php-fpm8.3
```

Replace `8.3` with your installed PHP version.

---

## 5. Access Your Application

Open your browser and navigate to:

```
http://127.0.0.1:8080
```

The application runs in the foreground. Stop it with **Ctrl+C** or close the terminal.
