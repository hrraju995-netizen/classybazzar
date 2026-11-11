# Running Classybazar on Windows (Laragon)

PowerShell versions of the setup scripts. Same result as the `.sh` files, just
without needing Git Bash or WSL.

---

## First run

Open PowerShell in the project folder and run these **one line at a time** —
Windows PowerShell 5.1 does not understand `&&` as a separator, which is what
the `The token '&&' is not a valid statement separator` error means. Use `;`
if you want to chain commands.

```powershell
cd C:\laragon\www\classybazar-localhost
Expand-Archive .\classybazar-localhost.zip -DestinationPath . -Force
cd classybazar
powershell -ExecutionPolicy Bypass -File .\setup.ps1
powershell -ExecutionPolicy Bypass -File .\start.ps1
```

Then open **http://localhost:3000**.

### About `-ExecutionPolicy Bypass`

Windows blocks unsigned `.ps1` files by default, so running `.\setup.ps1`
directly usually fails with a "running scripts is disabled" error. The
`powershell -ExecutionPolicy Bypass -File ...` form above sidesteps that for
one command without changing any system setting.

If you would rather allow local scripts permanently, run this once in an
Administrator PowerShell:

```powershell
Set-ExecutionPolicy -Scope CurrentUser RemoteSigned
```

After that `.\setup.ps1` and `.\start.ps1` work directly.

---

## What the scripts handle for you

**Laragon's binaries are not on PATH.** Laragon only adds PHP, Composer and
Node to PATH inside its own terminal window. In plain PowerShell, `php` is
usually "not recognized". The scripts look under `C:\laragon\bin`, find the
newest PHP, Composer and Node, and add them to PATH for that session — so you
do not have to use Laragon's terminal or edit your system PATH.

If you have several PHP versions installed, the newest one wins.

**pdo_sqlite is usually disabled.** Laragon ships the extension but leaves it
commented out in `php.ini`. `setup.ps1` detects this, backs up your `php.ini`
as `php.ini.classybazar-backup`, uncomments `extension=pdo_sqlite` and
`extension=sqlite3`, then re-checks. If it still cannot enable it, it tells
you the exact file to edit.

**`storage:link` needs admin rights on Windows.** Laravel creates a real
symbolic link, which Windows refuses without Administrator or Developer Mode.
The script falls back to a **directory junction**, which does the same job and
needs no privileges. You should not have to open an admin terminal.

---

## Everyday commands

```powershell
.\start.ps1                          # dev mode, hot reload
.\start.ps1 -Prod                    # production build
.\stop.ps1                           # free ports 8000 / 3000
.\setup.ps1 -Fresh                   # wipe the database and reseed
.\start.ps1 -ApiPort 8001 -WebPort 3001
```

If you change `-ApiPort`, update `client\.env.local` to match:

```env
NEXT_PUBLIC_API_URL=http://127.0.0.1:8001/api
```

---

## Using Laragon's MySQL instead of SQLite

SQLite is the default because it needs no server and no credentials. If you
would rather use the MySQL that Laragon already runs, create a database named
`classybazar` in HeidiSQL, then edit `api\.env`:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=classybazar
DB_USERNAME=root
DB_PASSWORD=
```

Then:

```powershell
cd api
php artisan migrate:fresh --seed
cd ..
```

Nothing in the schema is SQLite-specific, so no migration changes are needed.

---

## Serving it as a Laragon virtual host (optional)

The scripts run the API on `php artisan serve`, which is fine for development.
If you would rather have Laragon's Apache/nginx serve it at
`http://classybazar-api.test`:

1. Point the virtual host's document root at
   `C:\laragon\www\classybazar-localhost\classybazar\api\public`
2. Reload Laragon (tray icon > Apache > Reload, or Menu > Reload all)
3. Update `client\.env.local`:
   ```env
   NEXT_PUBLIC_API_URL=http://classybazar-api.test/api
   ```
4. Add that origin to `api\.env`:
   ```env
   FRONTEND_URLS=http://localhost:3000,http://127.0.0.1:3000
   ```
   (this lists who may *call* the API, so it stays as the storefront's address)

The Next.js side still needs `npm run dev` — Laragon cannot serve it.

---

## Troubleshooting

**`The token '&&' is not a valid statement separator`**
PowerShell 5.1 does not support `&&`. Run the commands on separate lines, or
join them with `;`. (PowerShell 7 supports `&&` — the "Install the latest
PowerShell" banner in your terminal is offering exactly that upgrade.)

**`running scripts is disabled on this system`**
Use the `powershell -ExecutionPolicy Bypass -File .\setup.ps1` form above.

**`php is not recognized`**
The scripts try to find Laragon automatically. If Laragon is installed
somewhere other than `C:\laragon` or `D:\laragon`, either use Laragon's own
terminal (tray icon > Terminal) or add PHP to PATH via
Laragon menu > Tools > Path > Add Laragon to Path, then reopen PowerShell.

**`could not find driver` during migrations**
`pdo_sqlite` is still disabled. Open the `php.ini` that `php --ini` reports
and remove the `;` in front of `extension=pdo_sqlite` and
`extension=sqlite3`.

**Port 8000 or 3000 already in use**
Run `.\stop.ps1`. Laragon itself does not use these ports, but a previous run
of `start.ps1` that was closed uncleanly can leave a `node` or `php` process
behind.

**Storefront loads but product sections are empty**
The API is not reachable. Check `logs\api.log`, then confirm this returns
JSON:
```powershell
Invoke-WebRequest http://127.0.0.1:8000/api/settings -UseBasicParsing
```

**Browser console shows a CORS error**
The address you are browsing is not listed in `FRONTEND_URLS` in `api\.env`.
Add it, then restart the API.

---

Everything else — endpoints, database design, what is still unbuilt — is in
`README.md` and `api\README.md`.

---

## Image uploads hang on "Waiting for size"

This is the one thing on Windows that `php artisan serve` genuinely cannot do.

PHP's built-in development server handles **one request at a time**. When the
admin panel uploads a main image plus two gallery images, the browser opens
several uploads at once, the server can only answer the first, and the rest sit
spinning forever. On Linux you would work around it with
`PHP_CLI_SERVER_WORKERS=4`, but that relies on `fork()`, which Windows does not
have.

The fix is to let Laragon's Apache serve the API instead. Apache is already
running and handles concurrent requests properly.

### Point Laragon at the API

1. Laragon tray icon → **Menu → Apache → sites-enabled → open the folder**
2. Create `classybazar-api.test.conf`:

   ```apache
   <VirtualHost *:80>
       DocumentRoot "C:/laragon/www/classybazar/api/public"
       ServerName classybazar-api.test
       <Directory "C:/laragon/www/classybazar/api/public">
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

3. Laragon → **Menu → Apache → Reload** (Laragon adds the hosts entry itself)
4. Update `client\.env.local`:

   ```env
   NEXT_PUBLIC_API_URL=http://classybazar-api.test/api
   ```

5. Update `api\.env` so the storefront origin is still allowed and links point
   at the right host:

   ```env
   APP_URL=http://classybazar-api.test
   FRONTEND_URL=http://localhost:3000
   FRONTEND_URLS=http://localhost:3000,http://127.0.0.1:3000
   ```

6. `cd api; php artisan optimize:clear`

Now the API lives at **http://classybazar-api.test** and the admin panel at
**http://classybazar-api.test/admin**, with no `php artisan serve` window to
keep alive. Only `npm run dev` still needs a terminal.

Uploaded images are served from `api/public/storage`, so add the new host to
`next.config.mjs`:

```js
remotePatterns: [
  { protocol: 'https', hostname: '**' },
  { protocol: 'http', hostname: 'classybazar-api.test' },
]
```

### If you would rather stay on `php artisan serve`

Upload one image at a time and wait for each to finish before adding the next.
It works, it is just slow and easy to trip over.

---

## Servers keep stopping

`php artisan serve` and `npm run dev` are foreground processes: closing the
terminal or pressing Ctrl+C stops that server. Each needs its own window, left
open.

```powershell
Start-Process powershell -ArgumentList '-NoExit','-Command','cd C:\laragon\www\classybazar\api; php artisan serve'
Start-Process powershell -ArgumentList '-NoExit','-Command','cd C:\laragon\www\classybazar\client; npm run dev'
```

Moving the API to Apache (above) removes half of this problem permanently.
