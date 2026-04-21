# UC Framework

## 1. Install Dependencies

Install the required packages using your package manager:

```bash
apt install lighttpd php-fpm php-sqlite3
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

Generate the database:

```bash
php bin/index.php db print | php bin/index.php db exec
```

**Important:** Rerun the compile command whenever you modify routes or units. For additional CLI commands, run `php bin/index.php` to see available options. CLI commands use regular routes with an empty HTTP method and are prefixed with `cli/` in the route URL path.

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

---

## 6. Using Adminer for Database Management

Adminer is a reusable user-land unit included in this project for convenient database management across projects.

### Access Adminer

Navigate to:

```
http://127.0.0.1:8080/adminer
```

### Authentication

**For localhost (127.0.0.1):**
- **Password:** Leave empty (no password required)

**For other IP addresses:**
- **Password:** `root` (default, using Adminer's passwordless plugin)

You can customize allowed IPs and the default password by editing:

```
src/Adminer/res/index.php
```

### Database Connection

When connecting to the database in Adminer:

**System:** `sqlite`

**Database:** `../var/dat/.sqlite`

This path points to your application's SQLite database file. Use this to browse tables, run queries, and manage your data through the web interface.