# UC Framework

## 1. Install Dependencies

**Linux/macOS**

Install PHP CGI and SQLite:

```bash
apt install php-cgi php-sqlite3
```

(Use your distribution's package manager if not using `apt`.)

**Windows**

No dependencies are required. Replace `bin/php-windows` with the PHP version required by your project.

---

## 2. Get the Repository

**Linux/macOS**

```bash
git clone https://github.com/usercypher/uc.git
cd uc
```

> Alternatively, download the ZIP if Git is not installed.

**Windows**

Download:

https://github.com/usercypher/uc/archive/refs/heads/main.zip

Extract the ZIP, then open **Command Prompt** in the extracted project folder.

---

## 3. Configure the Project

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
./compile.sh
```

**Windows**

```bat
compile.bat
```

Create the database.

**Linux/macOS**

```bash
./cli.sh db print | ./cli.sh db exec
```

**Windows**

```bat
cli.bat db print | cli.bat db exec
```

> Re-run the compile command whenever routes or units change. Run `cli.sh` or `cli.bat` without arguments to view available CLI commands.

---

## 4. Start the Server

**Linux/macOS**

```bash
chmod +x uc-web.sh
./uc-web.sh
```

**Windows**

```bat
uc-web.bat
```

---

## 5. Open the Application

Visit:

**http://127.0.0.1:8080**

Press **Ctrl+C** to stop the server.

---

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
|-------|-------|
| System | `sqlite` |
| Database | `var/lib/.sqlite` |