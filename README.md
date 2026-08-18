# UC Framework

## 1. Get the Repository

**Linux/macOS**

```bash
git clone https://github.com/usercypher/uc.git
cd uc
```

Or download the ZIP if Git is not installed.

**Windows**

Download and extract:

https://github.com/usercypher/uc/archive/refs/heads/main.zip

Then open Command Prompt in the extracted project folder.

## 2. Install Dependencies

**Linux/macOS**

Install PHP-FPM and SQLite:

```bash
apt install php php-fpm php-sqlite3
```

Use your distribution's package manager if needed.

**Windows**

Download PHP from https://www.php.net/downloads.php and extract it to:

```text
bin/php/php-windows-amd64
```

or:

```text
bin/php/php-windows-386
```

## 3. Configure and Initialize

Copy the default configuration.

**Linux/macOS**

```bash
cp config.data.php.example config.data.php
```

**Windows**

```bat
copy config.data.php.example config.data.php
```

Compile routes and units.

**Linux/macOS**

```bash
chmod +x compile.sh
./compile.sh
```

**Windows**

```bat
compile.bat
```

Create the database.

**Linux/macOS**

```bash
chmod +x cli.sh
./cli.sh db print | ./cli.sh db exec
```

**Windows**

```bat
cli.bat db print | cli.bat db exec
```

> Re-run the compile command whenever routes or units change. Run `cli.sh` or `cli.bat` without arguments to view available CLI commands.

## 4. Configure and Start the Server

**Linux/macOS**

Edit:

```text
bin/uc-web/uc-web.json
```

Set the `bin` value to your PHP-FPM binary path.

Then:

```bash
chmod +x uc-web.sh
chmod +x bin/uc-web/dist/*
./uc-web.sh
```

**Windows**

```bat
uc-web.bat
```

The Windows setup is automatic after placing the PHP binary in the appropriate `bin/php` folder.

## 5. Open the Application

Visit:

**http://127.0.0.1:8080**

Press **Ctrl+C** to stop the server.

## 6. Database Management (Adminer)

Adminer is included for SQLite database management.

Open:

**http://127.0.0.1:8080/adminer**

### Login

- **127.0.0.1:** Leave the password empty.
- **Other IPs:** Default password is `root`.

To change the password or allowed IPs, edit:

```text
src/Adminer/res/index.php
```

### Database

| Field | Value |
|---|---|
| System | `sqlite` |
| Database | `../var/lib/.sqlite` |
