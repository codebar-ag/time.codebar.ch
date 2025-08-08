# Internal Changes Guide

This document contains all the modifications needed to transform the open source codebase into our internal version. Apply these changes in order after pulling the latest upstream changes.

## Table of Contents
1. [What We Added](#what-we-added)
2. [What We Changed](#what-we-changed)
3. [What We Deleted](#what-we-deleted)

---

## What We Added

### 1. Search Functionality for Projects and Clients

#### Add Search to Projects Page

**File:** `resources/js/Pages/Projects.vue`

Add the search import:
```js
import { MagnifyingGlassIcon } from '@heroicons/vue/20/solid';
```

Add search state variable:
```js
const searchQuery = ref('');
```

Ensure clients are available for client-name matching:
```js
onMounted(() => {
    useProjectsStore().fetchProjects();
    useOrganizationStore().fetchOrganization();
    useClientsStore().fetchClients(); // needed for client-name search
});
```

Replace the existing `shownProjects` computed with:
```js
const shownProjects = computed(() => {
    let filteredProjects = projects.value.filter((project) => {
        if (activeTab.value === 'active') {
            return !project.is_archived;
        }
        return project.is_archived;
    });

    // Apply search filter
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase().trim();
        
        // Create clients map for O(1) lookup performance
        const clientsMap = new Map(clients.value.map(c => [c.id, c]));
        
        filteredProjects = filteredProjects.filter((project) => {
            // Search in project name
            const projectNameMatch = project.name.toLowerCase().includes(query);
            
            // Search in client name
            const clientNameMatch = project.client_id
                ? (clientsMap.get(project.client_id)?.name.toLowerCase().includes(query) ?? false)
                : false;
            
            return projectNameMatch || clientNameMatch;
        });
    }

    return filteredProjects;
});
```

Add search input UI (replace the existing action button container):
```vue
<div class="flex items-center space-x-3">
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <MagnifyingGlassIcon class="h-5 w-5 text-text-secondary" />
        </div>
        <input
            v-model="searchQuery"
            type="text"
            placeholder="Search projects or clients..."
            class="block w-64 pl-10 pr-3 py-2 border border-input-border rounded-md leading-5 bg-input-background text-text-primary placeholder-text-secondary focus:outline-none focus:ring-1 focus:ring-accent-500 focus:border-accent-500 sm:text-sm"
        />
    </div>
    <SecondaryButton>Create Project</SecondaryButton>
</div>
```

#### Add Search to Clients Page

**File:** `resources/js/Pages/Clients.vue`

Add the search import:
```js
import { MagnifyingGlassIcon } from '@heroicons/vue/20/solid';
```

Add search state variable:
```js
const searchQuery = ref('');
```

Replace the existing `shownClients` computed with:
```js
const shownClients = computed(() => {
    let filteredClients = clients.value.filter((client) => {
        if (activeTab.value === 'active') {
            return !client.is_archived;
        }
        return client.is_archived;
    });

    // Apply search filter
    if (searchQuery.value.trim()) {
        const query = searchQuery.value.toLowerCase().trim();
        filteredClients = filteredClients.filter((client) => {
            return client.name.toLowerCase().includes(query);
        });
    }

    return filteredClients;
});
```

Note: The API lists are paginated server-side. The search above filters the currently loaded page in the UI. If you need to search across all items, either implement pagination controls on these pages or increase `PAGINATION_PER_PAGE_DEFAULT` for your environment.

Add search input UI (replace the existing action button container):
```vue
<div class="flex items-center space-x-3">
    <div class="relative">
        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
            <MagnifyingGlassIcon class="h-5 w-5 text-text-secondary" />
        </div>
        <input
            v-model="searchQuery"
            type="text"
            placeholder="Search clients..."
            class="block w-64 pl-10 pr-3 py-2 border border-input-border rounded-md leading-5 bg-input-background text-text-primary placeholder-text-secondary focus:outline-none focus:ring-1 focus:ring-accent-500 focus:border-accent-500 sm:text-sm"
        />
    </div>
    <SecondaryButton>Create Client</SecondaryButton>
</div>
```

### 2. Sidebar Counts via Middleware (visibility-aware)

**File:** `app/Http/Middleware/ShareInertiaData.php`

Add counts to `auth.user.current_team`, optimized and respecting visibility:

```php
use App\Models\Project;
use App\Models\Client;
use App\Service\PermissionStore as AppPermissionStore;

// inside the current_team array in the shared Inertia props
'counts' => (function () use ($user) {
    $organization = $user->currentTeam;
    if ($organization === null) {
        return [];
    }
    /** @var AppPermissionStore $permissionStore */
    $permissionStore = app(AppPermissionStore::class);
    $canViewAllProjects = $permissionStore->has($organization, 'projects:view:all');

    // Active projects count; if the user can't view all, restrict to projects assigned to them
    $projectsQuery = Project::query()
        ->whereBelongsTo($organization, 'organization')
        ->whereNull('archived_at');
    if (! $canViewAllProjects) {
        $projectsQuery->visibleByEmployee($user);
    }
    $projectsCount = (clone $projectsQuery)->count();

    // Clients count: active clients that have at least one visible active project
    $clientsCount = Client::query()
        ->whereBelongsTo($organization, 'organization')
        ->whereNull('archived_at')
        ->whereHas('projects', function ($query) use ($organization, $user, $canViewAllProjects) {
            /** @var \Illuminate\Database\Eloquent\Builder<App\Models\Project> $query */
            $query->whereBelongsTo($organization, 'organization')
                ->whereNull('archived_at');
            if (! $canViewAllProjects) {
                $query->visibleByEmployee($user);
            }
        })
        ->distinct('clients.id')
        ->count('clients.id');

    return [
        'projects' => $projectsCount,
        'clients' => $clientsCount,
    ];
})(),
```

**Files:** `resources/js/Components/NavigationSidebarLink.vue` and `resources/js/Components/NavigationSidebarItem.vue`

Add a `count` prop to both, and render a small badge in the link when present.

**File:** `resources/js/Layouts/AppLayout.vue`

Pass counts into the sidebar items:

```vue
<NavigationSidebarItem
    v-if="canViewProjects()"
    title="Projects"
    :icon="FolderIcon"
    :count="page.props.auth.user.current_team?.counts?.projects"
    :href="route('projects')"
    :current="route().current('projects')"
/>
<NavigationSidebarItem
    v-if="canViewClients()"
    title="Clients"
    :icon="UserCircleIcon"
    :count="page.props.auth.user.current_team?.counts?.clients"
    :current="route().current('clients')"
    :href="route('clients')"
/>
```

---

## What We Changed

### 1. Database Configuration for Read/Write Hosts

**File:** `config/database.php`

In the database connection configuration (likely under `connections.pgsql` or similar), update to:
```php
'read' => [
    'host' => env('DB_READ_HOST', env('DB_HOST')),
],
'write' => [
    'host' => env('DB_WRITE_HOST', env('DB_HOST')),
],
// Comment out or remove the single host line:
// 'host' => env('DB_HOST', '127.0.0.1'),
```

### 2. Early Return for Client & Project Delete API Endpoints

**Find the API controller files for clients and projects deletion (likely in `app/Http/Controllers/Api/`)**

Modify the delete endpoints to return early without actually performing the deletion:
```php
// In Client delete endpoint
public function delete($id)
{
    // Early return - don't actually delete
    return response()->json(['message' => 'Client deletion disabled'], 200);
    
    // Original deletion logic commented out or removed
}

// In Project delete endpoint  
public function delete($id)
{
    // Early return - don't actually delete
    return response()->json(['message' => 'Project deletion disabled'], 200);
    
    // Original deletion logic commented out or removed
}
```

Implemented in:
- `app/Http/Controllers/Api/V1/ClientController.php::destroy()` now returns `{ message: 'Client deletion disabled' }` with HTTP 200 and does not delete.
- `app/Http/Controllers/Api/V1/ProjectController.php::destroy()` now returns `{ message: 'Project deletion disabled' }` with HTTP 200 and does not delete.

### 2.1 Frontend: Remove Delete UI for Clients and Projects

- Removed delete menu items from:
  - `resources/js/Components/Common/Client/ClientMoreOptionsDropdown.vue`
  - `resources/js/Components/Common/Project/ProjectMoreOptionsDropdown.vue`
- Disabled delete handlers in row components:
  - `resources/js/Components/Common/Client/ClientTableRow.vue`
  - `resources/js/Components/Common/Project/ProjectTableRow.vue`
- Client and Project Pinia stores no longer expose delete methods:
  - `resources/js/utils/useClients.ts` (delete removed)
  - `resources/js/utils/useProjects.ts` (delete removed)

Archiving remains available via update endpoints using the `is_archived` flag, which toggles `archived_at`.

### 2.2 Tests

- Disabled delete E2E tests (now incompatible with product decision):
  - `e2e/clients.spec.ts`: deletion test is `test.skip` and now verifies archive action instead.
  - `e2e/projects.spec.ts`: deletion test is `test.skip` and now verifies archive action instead.

### 3. Project Table Sorting Logic

**File:** `resources/js/Components/Common/Project/ProjectTable.vue`

Replace the existing `sortedProjects` computed with:
```js
const sortedProjects = computed(() => {
    return [...props.projects].sort((a, b) => {
        // Get client names, handling null clients
        const clientA = clients.value.find(client => client.id === a.client_id)?.name || '';
        const clientB = clients.value.find(client => client.id === b.client_id)?.name || '';
        
        // First sort by client name
        const clientComparison = clientA.localeCompare(clientB);
        if (clientComparison !== 0) {
            return clientComparison;
        }
        
        // Then sort by project name
        return a.name.localeCompare(b.name);
    });
});
```

### 4. Project Table Column Reordering

**File:** `resources/js/Components/Common/Project/ProjectTableHeading.vue`

Reorder columns to: Client → Name → Total Time → Progress → Billable Rate → Edit
```vue
<div class="px-3 py-1.5 text-left font-semibold text-text-primary pl-4 sm:pl-6 lg:pl-8">Client</div>
<div class="py-1.5 pr-3 text-left font-semibold text-text-primary">Name</div>
<!-- Keep other existing headers in order -->
```

**File:** `resources/js/Components/Common/Project/ProjectTableRow.vue`

Move client data column to first position:
```vue
<div class="whitespace-nowrap min-w-0 px-3 py-4 text-sm text-text-secondary pl-4 sm:pl-6 lg:pl-8">
    <div v-if="project.client_id" class="overflow-ellipsis overflow-hidden">
        {{ client?.name }}
    </div>
    <div v-else>No client</div>
</div>
<div class="whitespace-nowrap min-w-0 flex items-center space-x-5 py-4 pr-3 text-sm font-medium text-text-primary">
    <!-- project name content -->
</div>
<!-- Keep other existing columns in order -->
```

**File:** `resources/js/Components/Common/Project/ProjectTable.vue`

Update grid template to accommodate column reordering:
```js
// In the computed grid template
return `grid-template-columns: minmax(150px, auto) minmax(300px, 1fr) minmax(140px, auto) minmax(130px, auto) ${props.showBillableRate ? 'minmax(130px, auto)' : ''} 80px;`;
```

### 5. Client Table Header Fix

**File:** `resources/js/Components/Common/Client/ClientTableHeading.vue`

Add missing "Projects" header:
```vue
<div class="px-3 py-1.5 text-left font-semibold text-text-primary">Projects</div>
```

### 6. Client Table Column Positioning Fix
### 7. Server-side Ordering by Name

Applied server-side ordering by `name` to ensure predictable sort on initial load:

- `app/Http/Controllers/Api/V1/ProjectController.php::index()` now orders by `name`.
- `app/Http/Controllers/Api/V1/ClientController.php::index()` now orders by `name`.

Additionally, client and project tables apply UI sorting:

- Projects sorted by client name, then project name in `resources/js/Components/Common/Project/ProjectTable.vue` via `sortedProjects`.
- Clients sorted by name in `resources/js/Components/Common/Client/ClientTable.vue` via `sortedClients`.


**File:** `resources/js/Components/Common/Client/ClientTableRow.vue`

Fix excessive spacing in Projects column:
```vue
<!-- Name column -->
<div class="whitespace-nowrap py-4 pr-3 text-sm font-medium text-text-primary pl-4 sm:pl-6 lg:pl-8">
    <!-- name content -->
</div>

<!-- Projects column -->
<div class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary">
    <!-- projects content -->
</div>
```

### 7. PHP 8.4 Runtime Upgrade

Update the project's runtime to PHP 8.4.

- File: `composer.json`
- Change the PHP requirement and set the Composer platform to ensure consistent installs.

Update:
```json
{
  "require": {
    "php": "8.4.*"
  },
  "config": {
    "platform": {
      "php": "8.4.0"
    }
  }
}
```

After changing the file:
- Run: `composer update --no-interaction`
- Ensure your local/CI runtime uses PHP 8.4.
- Run the test suite: `composer test`

TODO: Update all GitHub Actions workflows to use PHP 8.4 (`php-version: '8.4'`) in `.github/workflows/` for PHPUnit, Playwright, PHPStan, and NPM build/typecheck jobs.

---

## What We Deleted

### 1. Status Columns from All Tables

#### Projects Table
**File:** `resources/js/Components/Common/Project/ProjectTableHeading.vue`
Remove:
```vue
<div class="px-3 py-1.5 text-left font-semibold text-text-primary">Status</div>
```

**File:** `resources/js/Components/Common/Project/ProjectTableRow.vue`
Remove:
```vue
<div class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary flex space-x-1 items-center font-medium">
    <CheckCircleIcon class="w-5"></CheckCircleIcon>
    <span>Active</span>
</div>
```

#### Clients Table
**File:** `resources/js/Components/Common/Client/ClientTableHeading.vue`
Remove:
```vue
<div class="px-3 py-1.5 text-left font-semibold text-text-primary">Status</div>
```

**File:** `resources/js/Components/Common/Client/ClientTableRow.vue`
Remove:
```vue
<div class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary flex space-x-1 items-center font-medium">
    <CheckCircleIcon class="w-5"></CheckCircleIcon>
    <span>Active</span>
</div>
```

**File:** `resources/js/Components/Common/Client/ClientTable.vue`
Update grid template:
```vue
<!-- Change from 4 columns to 3 columns -->
style="grid-template-columns: 1fr 150px 80px"
```

#### Members Table
**File:** `resources/js/Components/Common/Member/MemberTableHeading.vue`
Remove:
```vue
<div class="px-3 py-1.5 text-left font-semibold text-text-primary">Status</div>
```

**File:** `resources/js/Components/Common/Member/MemberTableRow.vue`
Remove:
```vue
<div class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary flex space-x-1 items-center font-medium">
    <CheckCircleIcon v-if="member.is_placeholder === false" class="w-5"></CheckCircleIcon>
    <span v-if="member.is_placeholder === false">Active</span>
    <UserCircleIcon v-if="member.is_placeholder === true" class="w-5"></UserCircleIcon>
    <span v-if="member.is_placeholder === true">Inactive</span>
</div>
```

**File:** `resources/js/Components/Common/Member/MemberTable.vue`
Update grid template:
```vue
<!-- Change from 6 columns to 5 columns -->
style="grid-template-columns: 1fr 1fr 180px 180px 130px"
```

### 2. ProjectsChartCard from Dashboard

**File:** `resources/js/Components/Dashboard/ThisWeekOverview.vue`

Remove import:
```js
import ProjectsChartCard from '@/Components/Dashboard/ProjectsChartCard.vue';
```

Remove from template:
```vue
<ProjectsChartCard
    v-if="weeklyProjectOverview"
    :weekly-project-overview="weeklyProjectOverview">
</ProjectsChartCard>
```

Keep the API query but remove the component usage.

### 3. ReportingPieChart from Reporting Pages

**Files:** `resources/js/Components/Common/Reporting/ReportingOverview.vue` and `resources/js/Pages/SharedReport.vue`

Remove import:
```js
import ReportingPieChart from '@/Components/Common/Reporting/ReportingPieChart.vue';
```

Remove from template:
```vue
<div class="px-2 lg:px-4">
    <ReportingPieChart :data="groupedPieChartData"></ReportingPieChart>
</div>
```

Remove computed properties:
```js
const groupedPieChartData = computed(() => { /* ... */ });
```

**File to delete entirely:** `resources/js/Components/Common/Reporting/ReportingPieChart.vue`

### 4. Redundant CSS Classes

Clean up duplicate `3xl:pl-12` classes in all table components. In the following files, remove redundant class declarations:

- `resources/js/Components/Common/Client/ClientTableRow.vue`
- `resources/js/Components/Common/Client/ClientTableHeading.vue`
- `resources/js/Components/Common/Tag/TagTableHeading.vue`
- `resources/js/Components/Common/Tag/TagTableRow.vue`
- `resources/js/Components/Common/ProjectMember/ProjectMemberTableHeading.vue`
- `resources/js/Components/Common/ProjectMember/ProjectMemberTableRow.vue`
- `resources/js/Components/Common/Invitation/InvitationTableRow.vue`
- `resources/js/Components/Common/Invitation/InvitationTableHeading.vue`
- `resources/js/Components/Common/Report/ReportTableHeading.vue`
- `resources/js/Components/Common/Report/ReportTableRow.vue`
- `resources/js/Components/Common/Member/MemberTableRow.vue`
- `resources/js/Components/Common/Member/MemberTableHeading.vue`
- `resources/js/Components/Common/Project/ProjectTableRow.vue`
- `resources/js/Components/Common/Project/ProjectTableHeading.vue`
- `resources/js/Components/Common/Task/TaskTableHeading.vue`
- `resources/js/Components/Common/Task/TaskTableRow.vue`

Change:
```css
class="... pl-4 sm:pl-6 lg:pl-8 3xl:pl-12"
```
To:
```css
class="... pl-4 sm:pl-6 lg:pl-8"
```

### 5. Pull Request Template

**File to delete:** `.github/PULL_REQUEST_TEMPLATE.md`

---

### 6. Repository Templates and Infrastructure Files

Delete the following files/directories if present (clean up OSS-related templates and infra we do not use internally):

- `.github/PULL_REQUEST_TEMPLATE.md` (already covered above)
- `FUNDING.md`
- `.github/ISSUE_TEMPLATE/` (entire directory)
- `.github/workflows/build-private.yml`
- `.github/workflows/build-public.yml`
- `.github/workflows/generate-api-docs.yml`
- `docker/` (entire directory)
- `CODE_OF_CONDUCT.md` (or `CODE_OF_CONDUCTS.md` if present)
- `docker-compose.yml` (or `docker-composer.yml` if present)
- `CONTRIBUTING.md` (or `CONTRIBUTING:md` if present)
- `LICENSE` or `LICENSE.md` (if file name differs, remove the license file used by the upstream project)
- `SECURITY.md`

These deletions ensure the internal fork does not expose or enforce upstream community processes or CI workflows.

## Application Order

1. Apply database configuration changes
2. Apply API endpoint changes (early returns)
3. Apply all deletions first (status columns, components, files)
4. Apply UI changes (column reordering, styling fixes)
5. Add new functionality (search, sidebar counts)
6. Clean up CSS classes

This ensures that deletions don't conflict with additions and changes are applied in a logical sequence.
