# ASSAN — OpenCode Bootstrap & First Deployment Instructions

## 0. Mission

You are OpenCode, acting as the implementation/deployment agent for the ASSAN project.

The immediate goal is **NOT to customize Aimeos**.

The immediate goal is:

1. Obtain the official open-source Aimeos standalone Laravel shop.
2. Put it into the user's GitHub repository named **ASSAN**.
3. Run it locally and verify that the untouched system works.
4. Deploy the untouched system on a genuinely free test environment.
5. Verify the public storefront and admin panel.
6. Report exactly what worked, what failed, and the URLs/credentials/setup information needed for the next stage.
7. Do not begin branding, feature changes, UI redesign, or business-specific modifications until the user explicitly approves the first test.

The user will provide the destination GitHub repository URL separately.

Use this placeholder until the user supplies it:

`DESTINATION_REPO_URL`

Do NOT invent a GitHub URL.

---

# 1. Source project

Use the official Aimeos standalone shop repository/distribution:

- GitHub: https://github.com/aimeos/aimeos
- Official documentation: https://aimeos.org/docs/
- Official standalone installation uses the Aimeos shop distribution.

The official Aimeos README currently states that the standalone shop requires:

- PHP >= 8.2
- Composer >= 2.2
- MySQL >= 5.7.8, MariaDB >= 10.2.2, PostgreSQL >= 9.6, or SQL Server 2019+
- Apache, Nginx, or PHP's integrated development server

Do not blindly hard-code versions from this document. First inspect the current upstream repository's `composer.json`, `composer.lock`, README, Docker files, and Laravel/Aimeos requirements. Use the versions required by the current upstream code.

Official installation reference:

`composer create-project aimeos/aimeos myshop`

The standalone distribution includes both the shop frontend and the administration backend.

Expected routes after a successful setup:

- Storefront: `/shop`
- Administration: `/admin`

---

# 2. Critical operating rules

## DO

- Work from the official Aimeos repository/distribution.
- Preserve upstream functionality.
- Inspect the project before modifying it.
- Use Git at every meaningful step.
- Keep secrets out of Git.
- Use `.env.example` for documented configuration.
- Create clear commits.
- Verify commands actually succeed.
- Record versions of PHP, Composer, Node/npm if present, database, Laravel, and Aimeos.
- Test both storefront and admin.
- Prefer PostgreSQL for the cloud test if the current Aimeos version supports it cleanly.
- Use only free services for this experiment.
- Stop and ask the user before any action that could incur a charge.
- Stop before destructive operations such as deleting a GitHub repository, force-pushing over unrelated user work, deleting a database, or overwriting an existing project without a backup.

## DO NOT

- Do not redesign the UI.
- Do not rename Aimeos to ASSAN inside the application yet.
- Do not replace logos.
- Do not remove Aimeos copyright/license notices.
- Do not add custom business features.
- Do not install unnecessary packages.
- Do not introduce Docker if the normal deployment path works.
- Do not switch databases just because another database is familiar; verify compatibility first.
- Do not create a paid Render/Vercel/Railway/etc. resource.
- Do not commit `.env`.
- Do not expose database passwords, API keys, SMTP passwords, GitHub tokens, or other secrets in logs, commits, or the final report.
- Do not force-push unless explicitly authorized.

---

# 3. Destination GitHub repository

Destination:

`DESTINATION_REPO_URL`

Before pushing:

1. Inspect whether the destination repository is empty.
2. If it is empty, initialize normally.
3. If it already contains files, inspect them first.
4. Never destroy existing unrelated work.
5. If the destination contains an existing README, LICENSE, `.gitignore`, or other files, determine whether they conflict with the imported project.
6. If there is ambiguity, stop and ask the user rather than deleting data.

The desired final repository is:

`ASSAN`

with the Aimeos source as the initial project.

---

# 4. Git workflow

## Step 1 — inspect the local environment

Run and record:

```bash
git --version
php --version
composer --version
node --version
npm --version
```

If a command is unavailable, report it.

On Windows, use PowerShell-compatible commands when necessary.

## Step 2 — inspect Aimeos upstream

Clone or obtain the official repository:

```bash
git clone https://github.com/aimeos/aimeos.git
cd aimeos
```

Inspect:

```bash
git remote -v
git branch -a
git status
git log -5 --oneline
```

Inspect at minimum:

```text
README.md
composer.json
composer.lock
.env.example
.gitignore
Dockerfile
docker-compose.yml
render.yaml
```

Only files that actually exist should be inspected.

Determine the current default branch and current release/version.

## Step 3 — create/prepare ASSAN repository

If the user wants the repository itself to contain the Aimeos standalone distribution, preserve the complete application source.

Preferred approach:

- Clone/import the Aimeos source.
- Change `origin` to `DESTINATION_REPO_URL`.
- Preserve upstream history when practical.
- Do not accidentally push to `aimeos/aimeos`.

Example:

```bash
git remote rename origin upstream
git remote add origin DESTINATION_REPO_URL
git remote -v
```

Then push the appropriate branch:

```bash
git push -u origin <current-branch>
```

If the destination repository already has a different default branch, inspect it and merge safely rather than overwriting it.

---

# 5. First local installation

Before changing application code, install the project exactly as upstream expects.

If the repository itself is already a complete standalone Aimeos application, follow its existing instructions.

If the repository is only the framework/package source and not a runnable standalone shop, use the official standalone distribution method described by Aimeos.

The official standalone installation is:

```bash
composer create-project aimeos/aimeos myshop
```

Do not create a second nested `myshop` directory if the cloned repository already IS the standalone application.

The target repository should end up with the actual runnable Laravel application at its root, not:

```text
ASSAN/
  myshop/
```

unless the upstream repository explicitly requires that structure.

---

# 6. Environment configuration

Create `.env` from the project's current `.env.example` when available:

```bash
cp .env.example .env
```

On Windows PowerShell, use the appropriate equivalent.

Generate an application key if required:

```bash
php artisan key:generate
```

Configure a local database.

Preferred local testing database:

1. PostgreSQL if already installed and convenient.
2. MySQL/MariaDB if already installed.
3. SQLite ONLY if the current Aimeos version explicitly supports the required shop functionality correctly.

Do not introduce a database workaround without verifying Aimeos compatibility.

Typical PostgreSQL variables are:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=assan
DB_USERNAME=...
DB_PASSWORD=...
```

Use the exact variables and driver names required by the current Laravel version.

Set:

```env
APP_URL=http://127.0.0.1:8000
SESSION_DRIVER=file
```

The Aimeos documentation specifically warns that the file session driver should be used so the shopping basket is stored correctly in the default setup.

Do not commit `.env`.

---

# 7. Database setup

First run Laravel migrations if required by the current project:

```bash
php artisan migrate
```

Then run the Aimeos setup command:

```bash
php artisan aimeos:setup
```

For a test/demo installation, if the current upstream documentation supports it, demo data can be installed with:

```bash
php artisan aimeos:setup --option=setup/default/demo:1
```

Use demo data for the first evaluation if it makes it easier to test the catalog, products, categories, basket, and admin interface.

After setup, clear relevant caches if required:

```bash
php artisan route:clear
php artisan view:clear
php artisan cache:clear
php artisan aimeos:clear
```

Do not run destructive database commands such as `migrate:fresh` against any database containing user data.

---

# 8. Public directory / permissions

Verify that the web process can write to the directories required by Aimeos.

The official Aimeos documentation references directories such as:

```text
public/aimeos
public/vendor
```

Create them only if the current project requires them.

On Linux:

```bash
mkdir -p public/aimeos public/vendor
```

Set only the minimum permissions required.

Do NOT use world-writable permissions in a production deployment unless absolutely necessary.

---

# 9. Local server

Start the application:

```bash
php artisan serve
```

Expected local URL:

```text
http://127.0.0.1:8000
```

Test:

```text
http://127.0.0.1:8000/shop
http://127.0.0.1:8000/admin
```

Verify:

- Storefront loads.
- CSS loads.
- JavaScript loads.
- Product catalog loads.
- Product detail works.
- Category/search works if demo data is present.
- Basket/cart works.
- Admin login works.
- Admin dashboard loads.
- Products can be viewed in admin.
- A test product can be created/edited if the setup permits.
- No fatal PHP/Laravel errors occur.
- Browser console does not contain obvious application errors.
- Laravel logs contain no unresolved fatal errors.

---

# 10. First-test acceptance criteria

Do NOT proceed to customization until ALL of these are tested or explicitly documented as unavailable.

## Application

- [ ] Laravel boots.
- [ ] Database connects.
- [ ] Migrations succeed.
- [ ] Aimeos setup succeeds.
- [ ] Storefront loads.
- [ ] Admin loads.
- [ ] Admin authentication works.
- [ ] Products are visible.
- [ ] Categories are visible.
- [ ] Product details work.
- [ ] Cart works.
- [ ] Checkout flow can be entered.
- [ ] No critical errors.

## GitHub

- [ ] ASSAN repository exists.
- [ ] Aimeos code is pushed.
- [ ] `.env` is NOT pushed.
- [ ] `.gitignore` works.
- [ ] README contains first-test information.
- [ ] Git history is understandable.
- [ ] `origin` points to the ASSAN repository.
- [ ] `upstream` points to the official Aimeos repository if upstream tracking is retained.

---

# 11. Free cloud deployment strategy

For the first cloud test, prioritize simplicity over production architecture.

## Preferred test architecture

```text
GitHub ASSAN
     |
     v
Render Free Web Service
     |
     v
Laravel + Aimeos
     |
     v
Render Free PostgreSQL
```

This avoids unnecessary third-party services for the first test.

Aimeos currently supports PostgreSQL, so PostgreSQL should be used if the current release's dependency requirements confirm compatibility.

Render currently offers free web services and free PostgreSQL for testing. The current documented limits include:

- Free web service: 0.1 CPU / 512 MB RAM
- Free web service spins down after 15 minutes without inbound traffic
- Free web services have 750 instance hours per workspace/month
- Free Render PostgreSQL: 1 GB
- Free Render PostgreSQL expires after 30 days
- Free Render PostgreSQL has no backups
- Free services are intended for testing/hobby use, not production

Therefore this setup is appropriate for the requested one-to-two-week evaluation, assuming the current limits remain the same when deployment is performed.

Reference:

https://render.com/docs/free

IMPORTANT:

Never add a payment method or select a paid service merely to make deployment easier.

If Render refuses the application because of memory/build constraints, investigate a genuinely free alternative before changing the architecture.

---

# 12. Render deployment

## Step 1 — create PostgreSQL

In Render:

1. Create a new PostgreSQL database.
2. Select the Free plan.
3. Choose a region close to the user if practical.
4. Record the internal database connection information.
5. Do not expose the password in GitHub.
6. Do not paste credentials into this instruction file.

The database must remain free.

## Step 2 — create Laravel Web Service

Create a new Render Web Service connected to:

`DESTINATION_REPO_URL`

Use the repository's actual detected branch.

Do not assume the project is Node.js.

Aimeos is Laravel/PHP, so configure the service as a PHP application using the deployment method supported by the current Render environment.

If the current Render PHP deployment requires Docker, use the simplest official-compatible Docker deployment.

If a native PHP build environment is available, prefer it over unnecessary Docker complexity.

---

# 13. Render build process

Before choosing exact commands, inspect:

- `composer.json`
- current Laravel version
- current PHP requirement
- package scripts
- public document root requirements
- any existing Dockerfile
- any existing deployment configuration

The deployment must serve:

```text
/public
```

as the web document root.

Do NOT expose the Laravel project root publicly.

A production-style web server must route requests through:

```text
public/index.php
```

Do not use:

```bash
php artisan serve
```

as the permanent cloud production server.

`php artisan serve` is only for local testing.

---

# 14. Cloud environment variables

Configure the Render Web Service environment variables.

At minimum, determine the exact variables from the project's current `.env.example` and Laravel configuration.

Typical variables include:

```text
APP_NAME
APP_ENV=production
APP_KEY
APP_DEBUG=false
APP_URL=<Render public URL>

LOG_CHANNEL=stderr

DB_CONNECTION=pgsql
DB_HOST=<Render database host>
DB_PORT=<Render database port>
DB_DATABASE=<database name>
DB_USERNAME=<database user>
DB_PASSWORD=<database password>

SESSION_DRIVER=file
```

Do NOT copy the user's local `.env` into Render.

Generate a separate production application key.

Never set:

```text
APP_DEBUG=true
```

on the public deployment.

---

# 15. Production build/setup

The deployment process must:

1. Install PHP dependencies with Composer.
2. Optimize the Composer autoloader where appropriate.
3. Prepare Laravel.
4. Generate/publish required Aimeos assets.
5. Run migrations/setup against the Render PostgreSQL database.
6. Clear/refresh caches as appropriate.
7. Start the web server.

Do NOT automatically run destructive commands such as:

```bash
migrate:fresh
db:wipe
```

in deployment.

If the deployment needs a one-time Aimeos setup command, run it carefully against the new empty test database.

If the build system cannot safely perform database setup during every deployment, separate the one-time initialization step from the normal build/start process.

---

# 16. Health check

Create/use a health endpoint only if the current application does not already provide a suitable one.

A basic health check may be:

```text
/
```

or another non-destructive Laravel route.

Do not add unnecessary application code merely for a health check if Render can successfully check the storefront URL.

The final public tests should include:

```text
https://<render-service>.onrender.com/shop
https://<render-service>.onrender.com/admin
```

Use the actual generated Render URL.

---

# 17. Important free-tier warning

Render Free Web Services sleep after inactivity.

This means:

- The first request after sleep can take approximately a minute.
- This is normal.
- It does not mean the application is broken.

Render Free PostgreSQL is suitable for this experiment but is NOT a permanent production database.

It expires after the documented free period and has no backups.

Therefore:

**This deployment is only for evaluation.**

Do not present it as production hosting.

---

# 18. Supabase fallback

If Render PostgreSQL causes a compatibility or deployment problem, Supabase PostgreSQL may be considered.

Current Supabase free-plan documentation indicates:

- 500 MB database
- 5 GB egress
- 1 GB file storage
- Free projects pause after one week of inactivity
- Maximum of two active free projects

Reference:

https://supabase.com/pricing

However, for this particular first test, prefer Render PostgreSQL because keeping the application and database within the same deployment platform reduces configuration complexity.

Use Supabase only if necessary.

---

# 19. Storage warning

Do not rely on local filesystem storage for important uploaded files on Render Free.

Render Free web services have ephemeral filesystems. Uploaded images/files stored only on the web service filesystem can disappear after restart, redeploy, or spin-down.

For this FIRST TEST:

- Demo/sample data is acceptable.
- Temporary uploaded files are acceptable.
- Do not claim uploaded files are production-safe.

Do not introduce an external object-storage system unless the user later asks for production architecture.

---

# 20. No frontend separation yet

Aimeos standalone already provides the web storefront and administration interface.

Do NOT create a Next.js frontend for the first test.

Do NOT create a Flutter mobile application for the first test.

Do NOT convert the application into a headless architecture.

The purpose of this stage is to answer one simple question:

> "Can we successfully run the original Aimeos online store and admin panel before customizing it?"

Only after that succeeds should the project be evaluated for:

- ASSAN branding
- custom storefront
- custom admin
- API/mobile app
- Flutter Android/iOS application
- localization
- Afghanistan-specific payment/delivery features
- product/business customizations

---

# 21. Testing checklist after cloud deployment

Open the deployed storefront.

Test:

### Storefront

- [ ] Home/store page loads.
- [ ] Product listing loads.
- [ ] Product details load.
- [ ] Category navigation works.
- [ ] Search works if available.
- [ ] Product media loads.
- [ ] Cart works.
- [ ] Quantity changes work.
- [ ] Checkout opens.
- [ ] No broken assets.
- [ ] No fatal errors.

### Admin

Open:

`/admin`

Test:

- [ ] Admin login.
- [ ] Dashboard.
- [ ] Catalog.
- [ ] Products.
- [ ] Categories.
- [ ] Customer area.
- [ ] Orders.
- [ ] Configuration.
- [ ] Product create/edit if safe.
- [ ] Logout/login again.

### Database

Confirm:

- [ ] Cloud database contains Aimeos tables.
- [ ] Products persist after web service restart.
- [ ] Admin changes persist.
- [ ] Cart/session behaves correctly.

---

# 22. Git commits

Use clear commits.

Suggested sequence:

```text
chore: import upstream Aimeos shop
chore: configure local test environment
chore: configure free cloud deployment
docs: add ASSAN first-run and deployment notes
```

Do not create fake commits claiming tests succeeded when they did not.

Only commit after verifying the working tree.

Before every push:

```bash
git status
git diff
git log -5 --oneline
```

Never commit secrets.

---

# 23. README for ASSAN

After the first successful test, update the repository README with ONLY operational information needed for now.

Include:

```text
# ASSAN

Initial test project based on Aimeos.

## Current status

Initial Aimeos evaluation — no custom branding/features yet.

## Local requirements

- PHP version
- Composer version
- Database
- Node/npm only if required

## Local setup

Commands used successfully.

## Storefront

Local URL.

## Admin

Local URL.

## Cloud test

Render URL.

## Important

This is an evaluation deployment, not production hosting.

## Upstream

Official Aimeos repository.

## License

Preserve and document the upstream licenses correctly.
```

Do not claim ASSAN owns the original Aimeos code.

---

# 24. License handling

The project is open source, but different Aimeos components may have different licenses.

Before distributing anything:

1. Inspect the upstream license files.
2. Inspect package licenses.
3. Preserve required copyright and license notices.
4. Do not remove third-party attribution.
5. Do not claim the original Aimeos code was written by ASSAN.
6. When the user later asks for commercial redistribution/rebranding, perform a fresh license audit of the exact dependency tree.

For the first test, preserve all upstream license files and notices.

---

# 25. Error handling

If something fails:

1. Stop.
2. Read the actual error.
3. Identify the root cause.
4. Check the official Aimeos documentation/issues.
5. Fix the smallest possible problem.
6. Do not randomly upgrade/downgrade packages.
7. Do not replace major components without understanding the dependency conflict.
8. Re-run the failed command.
9. Record the final solution.

If a dependency conflict occurs:

```text
composer why-not <package> <version>
composer prohibits <package> <version>
composer show
```

Use the exact Composer commands supported by the installed Composer version.

Do not use `composer update` across the entire dependency tree unless required.

Prefer the lock file supplied by the project when appropriate.

---

# 26. If OpenCode gets stuck

Before asking the user:

- Read the complete error.
- Inspect relevant config.
- Check PHP version.
- Check Composer version.
- Check database connectivity.
- Check environment variables.
- Check permissions.
- Check Laravel logs.
- Check Render deployment logs.
- Check Aimeos documentation.
- Check upstream GitHub issues.

Only ask the user for information that cannot be discovered from the environment.

Never ask the user to provide a secret that can be generated or retrieved through the provider dashboard.

---

# 27. Final report required from OpenCode

At the end, produce a concise but complete report containing:

## Repository

- Destination repository URL
- Branch
- Commit
- Upstream Aimeos commit/version
- Whether upstream remote was preserved

## Local environment

- OS
- PHP
- Composer
- Laravel
- Aimeos
- Database

## Local test

- Storefront URL
- Admin URL
- What was tested
- Pass/fail

## Cloud

- Provider
- Web service URL
- Database provider
- Free plan used
- Build command
- Start command
- Environment variables configured (names only, NEVER values)
- Deployment status
- Public storefront URL
- Public admin URL

## Problems

List every problem encountered and how it was solved.

## Known limitations

Especially:

- Free Render sleep
- Free database expiration
- Ephemeral filesystem
- No production backup
- Resource limitations

## Next step

End with:

> FIRST TEST COMPLETE — READY FOR USER REVIEW

or:

> FIRST TEST BLOCKED — USER ACTION REQUIRED

Do not begin customization automatically.

---

# 28. Absolute final rule

The user wants to test Aimeos before deciding whether to use it.

Therefore:

**DO NOT CUSTOMIZE AIMEOS.**

No:

- ASSAN logo
- ASSAN colors
- ASSAN name inside UI
- new features
- new database structure
- custom checkout
- custom payment system
- custom mobile app
- custom API
- custom theme
- business-specific logic

until the user explicitly approves the first test.

The only acceptable modifications before approval are:

1. Environment configuration.
2. Deployment configuration.
3. Minimal fixes required to make the untouched upstream project run.
4. Documentation.
5. Security-safe configuration such as `APP_DEBUG=false`.

The first milestone is simply:

**Official Aimeos → ASSAN GitHub → Local working store → Free cloud working store → User evaluation.**
