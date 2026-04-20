# 3LP (ELP Stack): [L]inux + [L]ighttpd + sq[L]ite + [P]HP

## 1. Install Dependencies

Install the required packages using your package manager:

```bash
apt install lighttpd php-fpm php-pdo php-sqlite3
```

*(Adjust package names for `yum`, `pacman`, etc.)*

---

## 2. Clone the UC Framework Repository

```bash
git clone https://github.com/usercypher/uc.git
cd uc
```

---

## 3. Configure and Compile the Framework

Copy the default configuration:

```bash
cp config.php.default config.php
```

Compile routes and units:

```bash
php bin/compile.php
```

**Important:** Rerun this command whenever you modify routes or units.

---

## 4. Start the Application

Make the boot script executable and run it:

```bash
chmod +x boot.sh
./boot.sh
```

**To use a specific PHP-FPM or Lighttpd binary:**

```bash
./boot.sh php-fpm8.3 lighttpd
```

The boot script accepts optional arguments for `php-fpm` (with or without version) and `lighttpd`. Pass only the binaries you need to override.

---

## 5. Access Your Application

Open your browser and navigate to:

```
http://127.0.0.1:8080
```

The application runs in the foreground. Stop it with **Ctrl+C** or close the terminal.