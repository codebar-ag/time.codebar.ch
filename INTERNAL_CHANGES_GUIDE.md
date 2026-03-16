## INTERNAL CHANGES GUIDE

Authoritative, replayable guide for changes between the base commit and the current working state. Use this document to re-apply the exact same changes on top of any fresh checkout.

### Scope
- **Base commit (from):** f68f05d1aae30647f694c941597edc373561d50d
- **Current commit (to):** 46e97e82481d57d67ea9a6a512cac974ea6598c7
- **Current branch:** feature-relaunch
- **Uncommitted changes at capture time:** reflected in patch
- **Patch file (binary-safe):** `INTERNAL_CHANGES_GUIDE.patch`
- **Patch SHA256:** 4f286c4ee00a4202202d16d455f730d0707d3bbc7fa96ef6db870cbbce34c9cf

### Replay: Quick Start
1) Ensure a clean working tree.
   ```bash
git reset --hard && git clean -fd
   ```
2) Checkout the desired target revision (typically the base commit or a fresh branch).
   ```bash
git checkout f68f05d1aae30647f694c941597edc373561d50d
   ```
3) Verify patch integrity.
   ```bash
shasum -a 256 INTERNAL_CHANGES_GUIDE.patch
# must equal: 4f286c4ee00a4202202d16d455f730d0707d3bbc7fa96ef6db870cbbce34c9cf
   ```
4) Apply changes.
   ```bash
git apply --index --whitespace=nowarn INTERNAL_CHANGES_GUIDE.patch || git apply --3way INTERNAL_CHANGES_GUIDE.patch
   ```
5) Install deps and rebuild.
   ```bash
composer install --no-interaction --prefer-dist
npm ci
npm run build
   ```
6) Run tests/smoke.
   ```bash
php artisan test
npx playwright test
   ```

### Replay: Alternative via cherry-pick (single-commit path)
If the base commit is an ancestor of the current commit, you can replay by cherry-picking the to-commit on top of the base or any other branch:
```bash
git checkout <target-branch-or-detached>
git cherry-pick 324a12ffada544ce08be361c76534e75ce781a25
```
Resolve conflicts if any, then run the same install/build/test steps as above.

### Regenerate Patch (if needed)
From repo root:
```bash
git diff --binary f68f05d1aae30647f694c941597edc373561d50d > INTERNAL_CHANGES_GUIDE.patch
shasum -a 256 INTERNAL_CHANGES_GUIDE.patch
```

### Change Summary (high level)
- **Files changed:** 70
- **Insertions:** 2,165
- **Deletions:** 4,081

### Directory Footprint
```text
   2.9% .github/ISSUE_TEMPLATE/
  13.4% .github/workflows/
   1.4% .github/
   2.9% app/Http/Controllers/Api/V1/
   1.4% app/Http/Middleware/
   1.4% config/
   5.9% docker/local/8.3/
   1.4% docker/local/minio/
   1.4% docker/local/pgsql/
   2.9% docker/prod/deployment/octane/FrankenPHP/
  11.9% docker/prod/deployment/
   2.9% docker/prod/
   2.9% e2e/
   5.9% resources/js/Components/Common/Client/
   8.9% resources/js/Components/Common/Project/
   1.4% resources/js/Components/Common/Reporting/
   1.4% resources/js/Components/Dashboard/
   2.9% resources/js/Components/
   1.4% resources/js/Layouts/
   4.4% resources/js/Pages/
   4.4% resources/js/utils/
```

### File Changes (name-status)
```text
D       .github/ISSUE_TEMPLATE/1_bug_report.yml
D       .github/ISSUE_TEMPLATE/config.yml
D       .github/PULL_REQUEST_TEMPLATE.md
A       .github/workflows/README.md
D       .github/workflows/build-private.yml
D       .github/workflows/build-public.yml
D       .github/workflows/generate-api-docs.yml
M       .github/workflows/npm-build.yml
M       .github/workflows/npm-typecheck.yml
M       .github/workflows/phpstan.yml
M       .github/workflows/phpunit.yml
M       .github/workflows/playwright.yml
D       CODE_OF_CONDUCT.md
D       CONTRIBUTING.md
A       INTERNAL_CHANGES_GUIDE.md
A       INTERNAL_CHANGES_GUIDE.patch
D       LICENSE.md
D       SECURITY.md
M       app/Actions/Jetstream/AddOrganizationMember.php
M       app/Actions/Jetstream/UpdateOrganization.php
M       app/Http/Controllers/Api/V1/ClientController.php
M       app/Http/Controllers/Api/V1/ProjectController.php
M       app/Http/Middleware/ShareInertiaData.php
M       composer.json
M       composer.lock
M       config/database.php
D       docker-compose.yml
D       docker/local/8.3/Dockerfile
D       docker/local/8.3/php.ini
D       docker/local/8.3/start-container
D       docker/local/8.3/supervisord.conf
D       docker/local/minio/create_bucket.sh
D       docker/local/pgsql/create-testing-database.sql
D       docker/prod/Dockerfile
D       docker/prod/LICENSE
D       docker/prod/deployment/healthcheck
D       docker/prod/deployment/octane/FrankenPHP/Caddyfile
D       docker/prod/deployment/octane/FrankenPHP/supervisord.frankenphp.conf
D       docker/prod/deployment/php.ini
D       docker/prod/deployment/start-container
D       docker/prod/deployment/supervisord.conf
D       docker/prod/deployment/supervisord.horizon.conf
D       docker/prod/deployment/supervisord.reverb.conf
D       docker/prod/deployment/supervisord.scheduler.conf
D       docker/prod/deployment/supervisord.worker.conf
M       e2e/clients.spec.ts
M       e2e/projects.spec.ts
M       e2e/tasks.spec.ts
M       e2e/timetracker.spec.ts
M       package-lock.json
M       resources/js/Components/Common/Client/ClientMoreOptionsDropdown.vue
M       resources/js/Components/Common/Client/ClientTable.vue
M       resources/js/Components/Common/Client/ClientTableHeading.vue
M       resources/js/Components/Common/Client/ClientTableRow.vue
M       resources/js/Components/Common/Project/ProjectDropdown.vue
M       resources/js/Components/Common/Project/ProjectEditModal.vue
M       resources/js/Components/Common/Project/ProjectMoreOptionsDropdown.vue
M       resources/js/Components/Common/Project/ProjectTable.vue
M       resources/js/Components/Common/Project/ProjectTableHeading.vue
M       resources/js/Components/Common/Project/ProjectTableRow.vue
M       resources/js/Components/Common/Reporting/ReportingOverview.vue
M       resources/js/Components/Dashboard/RecentlyTrackedTasksCardEntry.vue
M       resources/js/Components/NavigationSidebarItem.vue
M       resources/js/Components/NavigationSidebarLink.vue
M       resources/js/Layouts/AppLayout.vue
M       resources/js/Pages/Clients.vue
M       resources/js/Pages/ProjectShow.vue
M       resources/js/Pages/Projects.vue
M       resources/js/utils/useClients.ts
M       resources/js/utils/useProjects.ts
M       resources/js/utils/useReporting.ts
M       tests/Unit/Endpoint/Api/V1/ClientEndpointTest.php
M       tests/Unit/Endpoint/Api/V1/ProjectEndpointTest.php
M       vite-module-loader.js
```

### File Change Stats (numstat)
Numbers are: [insertions] [deletions] [path]
```text
0	47	.github/ISSUE_TEMPLATE/1_bug_report.yml
0	8	.github/ISSUE_TEMPLATE/config.yml
0	11	.github/PULL_REQUEST_TEMPLATE.md
4	0	.github/workflows/README.md
0	199	.github/workflows/build-private.yml
0	218	.github/workflows/build-public.yml
0	66	.github/workflows/generate-api-docs.yml
1	1	.github/workflows/npm-build.yml
1	1	.github/workflows/npm-typecheck.yml
1	1	.github/workflows/phpstan.yml
1	1	.github/workflows/phpunit.yml
7	3	.github/workflows/playwright.yml
0	42	CODE_OF_CONDUCT.md
0	81	CONTRIBUTING.md
305	0	INTERNAL_CHANGES_GUIDE.md
12072	0	INTERNAL_CHANGES_GUIDE.patch
0	651	LICENSE.md
0	5	SECURITY.md
1	1	app/Actions/Jetstream/AddOrganizationMember.php
1	1	app/Actions/Jetstream/UpdateOrganization.php
3	8	app/Http/Controllers/Api/V1/ClientController.php
4	20	app/Http/Controllers/Api/V1/ProjectController.php
42	3	app/Http/Middleware/ShareInertiaData.php
4	1	composer.json
398	337	composer.lock
6	1	config/database.php
0	209	docker-compose.yml
0	65	docker/local/8.3/Dockerfile
0	8	docker/local/8.3/php.ini
0	26	docker/local/8.3/start-container
0	14	docker/local/8.3/supervisord.conf
0	10	docker/local/minio/create_bucket.sh
0	2	docker/local/pgsql/create-testing-database.sql
0	214	docker/prod/Dockerfile
0	21	docker/prod/LICENSE
0	35	docker/prod/deployment/healthcheck
0	68	docker/prod/deployment/octane/FrankenPHP/Caddyfile
0	65	docker/prod/deployment/octane/FrankenPHP/supervisord.frankenphp.conf
0	31	docker/prod/deployment/php.ini
0	55	docker/prod/deployment/start-container
0	13	docker/prod/deployment/supervisord.conf
0	14	docker/prod/deployment/supervisord.horizon.conf
0	14	docker/prod/deployment/supervisord.reverb.conf
0	26	docker/prod/deployment/supervisord.scheduler.conf
0	13	docker/prod/deployment/supervisord.worker.conf
7	11	e2e/clients.spec.ts
13	17	e2e/projects.spec.ts
4	10	e2e/tasks.spec.ts
2	2	e2e/timetracker.spec.ts
923	1310	package-lock.json
3	11	resources/js/Components/Common/Client/ClientMoreOptionsDropdown.vue
7	3	resources/js/Components/Common/Client/ClientTable.vue
3	4	resources/js/Components/Common/Client/ClientTableHeading.vue
9	18	resources/js/Components/Common/Client/ClientTableRow.vue
3	3	resources/js/Components/Common/Project/ProjectDropdown.vue
2	2	resources/js/Components/Common/Project/ProjectEditModal.vue
3	11	resources/js/Components/Common/Project/ProjectMoreOptionsDropdown.vue
15	3	resources/js/Components/Common/Project/ProjectTable.vue
2	6	resources/js/Components/Common/Project/ProjectTableHeading.vue
7	19	resources/js/Components/Common/Project/ProjectTableRow.vue
3	1	resources/js/Components/Common/Reporting/ReportingOverview.vue
2	1	resources/js/Components/Dashboard/RecentlyTrackedTasksCardEntry.vue
3	1	resources/js/Components/NavigationSidebarItem.vue
7	1	resources/js/Components/NavigationSidebarLink.vue
4	0	resources/js/Layouts/AppLayout.vue
32	4	resources/js/Pages/Clients.vue
2	2	resources/js/Pages/ProjectShow.vue
38	7	resources/js/Pages/Projects.vue
2	18	resources/js/utils/useClients.ts
1	18	resources/js/utils/useProjects.ts
3	2	resources/js/utils/useReporting.ts
12	9	tests/Unit/Endpoint/Api/V1/ClientEndpointTest.php
14	15	tests/Unit/Endpoint/Api/V1/ProjectEndpointTest.php
20	5	vite-module-loader.js
```

### High-Level Notes by Category
- **Repo hygiene:** Issue/PR templates removed; internal `INTERNAL_CHANGES_GUIDE.md` added; several project policy docs removed.
- **CI:** Multiple GitHub workflows adjusted; some workflows removed; minor 1-line tweaks in remaining workflows.
- **Backend:** Small changes in `ClientController`, `ProjectController`, and `ShareInertiaData` middleware; minor DB config tweak; Composer dependencies updated.
- **Docker:** Local and production Docker files removed (compose, Dockerfiles, configs, scripts).
- **Frontend:** Multiple Vue components updated across Clients/Projects tables, dropdowns, and pages; navigation and layout tweaks; utility hooks adjusted.
- **Tests:** e2e tests (`clients.spec.ts`, `projects.spec.ts`) updated.

### API Behavior Changes (Upgrade Notes)
- Clients API: `GET /api/v1/organizations/{org}/clients` now returns clients ordered by `name` ascending (was `created_at` desc). If you rely on ordering, update your consumers accordingly.
- Projects API: `GET /api/v1/organizations/{org}/projects` now returns projects ordered by `name` ascending (was `created_at`-based ordering in some flows). If you relied on creation-time ordering, sort client-side or use a dedicated query param in future versions.
- Clients API: `DELETE /api/v1/organizations/{org}/clients/{client}` is disabled. It now returns `200` with `{ message: "Client deletion disabled" }` and does not delete data.
- Projects API: `DELETE /api/v1/organizations/{org}/projects/{project}` is disabled. It now returns `200` with `{ message: "Project deletion disabled" }` and does not delete data.

### Step-by-Step Protocol (detailed)
1) Clean and position on base (or desired) revision.
   ```bash
git reset --hard && git clean -fd
git checkout f68f05d1aae30647f694c941597edc373561d50d
   ```
2) Verify the working tree is clean.
   ```bash
git status --porcelain=v1
# expect no output
   ```
3) Verify patch file checksum.
   ```bash
shasum -a 256 INTERNAL_CHANGES_GUIDE.patch
# must equal f780ccdef9900eee8db7dade5252efb5ff911cd76bf3d3c0fa9389a328642592
   ```
4) Apply patch (index + fallback 3-way if needed).
   ```bash
git apply --index --whitespace=nowarn INTERNAL_CHANGES_GUIDE.patch || git apply --3way INTERNAL_CHANGES_GUIDE.patch
   ```
5) Review staged changes.
   ```bash
git diff --staged --stat | cat
   ```
6) Commit the replayed changes (optional; if you want a local commit).
   ```bash
git commit -m "Replay: changes from f68f05d1..324a12ff (via INTERNAL_CHANGES_GUIDE)"
   ```
7) Dependencies and build.
   ```bash
composer install --no-interaction --prefer-dist
npm ci
npm run build
   ```
8) Test and lint (optional but recommended).
   ```bash
php artisan test
npx playwright test
   ```

### Commit Context
```text
46e97e8 (HEAD -> feature-relaunch) latest changes including API adjustments and CI tweaks
```

### Notes and Caveats
- If you replay onto a codebase whose base commit differs, 3-way apply may cause conflicts. Resolve and continue.
- The patch includes deletions for removed Docker and GitHub meta files; ensure this matches your target environment expectations.
- After replaying, check application boot (e.g., `php artisan`) and frontend dev server if used.

### Maintenance
- When HEAD changes, regenerate:
  ```bash
git diff --binary f68f05d1aae30647f694c941597edc373561d50d > INTERNAL_CHANGES_GUIDE.patch
shasum -a 256 INTERNAL_CHANGES_GUIDE.patch
  ```
- Update this guide’s header fields (current commit, checksum) and the sections for name-status, numstat, and directory footprint.
