# Changelog

## 20250720 

### Updated database.php to accept read/write hosts

```php
'read' => [
    'host' => env('DB_READ_HOST', env('DB_HOST')),
],
'write' => [
    'host' => env('DB_WRITE_HOST', env('DB_HOST')),
],
// 'host' => env('DB_HOST', '127.0.0.1'),
```

### Early Returned Clients & Project Delete API Endpoint

With this response, the request always returns successfully, but the projects and clients are not being deleted.

### Add Sidebar Counts via Middleware

```php
if ($user->currentTeam !== null) {
    $currentTeamCounts = [
        'projects' => Project::where('organization_id', $user->currentTeam->id)->whereNull('archived_at')->count(),
        'clients' => Client::where('organization_id', $user->currentTeam->id)->whereNull('archived_at')->count(),
        'members' => Member::where('organization_id', $user->currentTeam->id)->count(),
        'tags' => Tag::where('organization_id', $user->currentTeam->id)->count(),
    ];
}

'current_team' => 'counts' => $currentTeamCounts,
 ```

resources/js/Layouts/AppLayout.vue
```js
const page = usePage();
const counts = computed(() => page.props.auth.user.current_team?.counts || {});
```

resources/js/Components/NavigationSidebarLink.vue
resources/js/Components/NavigationSidebarItem.vue

### Hide ProjectsChartCard on Dashboard

Disable Dashboard.ThisWeekOverview.ProjectsChartCard

**File:** `resources/js/Components/Dashboard/ThisWeekOverview.vue`

**Removed import:**
```js
- import ProjectsChartCard from '@/Components/Dashboard/ProjectsChartCard.vue';
```

**Removed API query:**
```js
- const { data: weeklyProjectOverview } = useQuery({
-     queryKey: ['weeklyProjectOverview', organizationId],
-     queryFn: () => {
-         return api.weeklyProjectOverview({
-             params: {
-                 organization: organizationId.value!,
-             },
-         });
-     },
-     enabled: computed(() => !!organizationId.value),
- });
```

**Removed component from template:**
```vue
- <ProjectsChartCard
-     v-if="weeklyProjectOverview"
-     :weekly-project-overview="
-         weeklyProjectOverview
-     "></ProjectsChartCard>
```

### Remove Redundant Status Columns from Clients & Projects Tables

The status columns were always showing "Active" even for archived items, which was confusing since tabs already handle Active/Archived filtering.

#### Projects Table Changes

**File:** `resources/js/Components/Common/Project/ProjectTableHeading.vue`
- **Removed:** Status column header
```vue
- <div class="px-3 py-1.5 text-left font-semibold text-text-primary">Status</div>
```

**File:** `resources/js/Components/Common/Project/ProjectTableRow.vue`
- **Removed:** Hardcoded "Active" status column
```vue
- <div class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary flex space-x-1 items-center font-medium">
-     <CheckCircleIcon class="w-5"></CheckCircleIcon>
-     <span>Active</span>
- </div>
```

**File:** `resources/js/Components/Common/Project/ProjectTable.vue`
- **Updated:** Grid template to remove one column (minmax(120px, auto))
```js
- return `grid-template-columns: minmax(300px, 1fr) minmax(150px, auto) minmax(140px, auto) minmax(130px, auto) ${props.showBillableRate ? 'minmax(130px, auto)' : ''} minmax(120px, auto) 80px;`;
+ return `grid-template-columns: minmax(300px, 1fr) minmax(150px, auto) minmax(140px, auto) minmax(130px, auto) ${props.showBillableRate ? 'minmax(130px, auto)' : ''} 80px;`;
```

#### Clients Table Changes

**File:** `resources/js/Components/Common/Client/ClientTableHeading.vue`
- **Removed:** Status column header
```vue
- <div class="px-3 py-1.5 text-left font-semibold text-text-primary">Status</div>
```

**File:** `resources/js/Components/Common/Client/ClientTableRow.vue`
- **Removed:** Hardcoded "Active" status column
```vue
- <div class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary flex space-x-1 items-center font-medium">
-     <CheckCircleIcon class="w-5"></CheckCircleIcon>
-     <span>Active</span>
- </div>
```

**File:** `resources/js/Components/Common/Client/ClientTable.vue`
- **Updated:** Grid template from 4 columns to 3 columns
```vue
- style="grid-template-columns: 1fr 150px 200px 80px"
+ style="grid-template-columns: 1fr 150px 80px"
```

### Projects Page Improvements

Fixed sorting order and column layout on projects index page.

#### Updated Sorting Logic

**File:** `resources/js/Components/Common/Project/ProjectTable.vue`
- **Changed:** Sort by client name (ascending) first, then project name (ascending)
- **Previous:** Only sorted by project name
```js
- const sortedProjects = computed(() => {
-   return [...props.projects].sort((a, b) => a.name.localeCompare(b.name));
- });

+ const sortedProjects = computed(() => {
+   return [...props.projects].sort((a, b) => {
+     // Get client names, handling null clients
+     const clientA = clients.value.find(client => client.id === a.client_id)?.name || '';
+     const clientB = clients.value.find(client => client.id === b.client_id)?.name || '';
     
+     // First sort by client name
+     const clientComparison = clientA.localeCompare(clientB);
+     if (clientComparison !== 0) {
+       return clientComparison;
+     }
     
+     // Then sort by project name
+     return a.name.localeCompare(b.name);
+   });
+ });
```

#### Moved Client Column Before Project Name

**File:** `resources/js/Components/Common/Project/ProjectTableHeading.vue`
- **Column order changed from:** Name → Client → Total Time → Progress → Billable Rate → Edit
- **Column order changed to:** Client → Name → Total Time → Progress → Billable Rate → Edit

```vue
<div class="px-3 py-1.5 text-left font-semibold text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">Client</div>
  <div
      class="py-1.5 pr-3 text-left font-semibold text-text-primary">
      Name
  </div>
```

**File:** `resources/js/Components/Common/Project/ProjectTableRow.vue`
- **Moved client data column to first position**
```vue
<div class="whitespace-nowrap min-w-0 px-3 py-4 text-sm text-text-secondary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
    <div v-if="project.client_id" class="overflow-ellipsis overflow-hidden">
        {{ client?.name }}
    </div>
    <div v-else>No client</div>
</div>
  <div
      class="whitespace-nowrap min-w-0 flex items-center space-x-5 py-4 pr-3 text-sm font-medium text-text-primary">
      <!-- project name content -->
  </div>
```

### Fix Projects Table Column Widths

Fixed layout issue where Client column was too wide after column reordering.

**File:** `resources/js/Components/Common/Project/ProjectTable.vue`
- **Fixed:** Grid template to make Client column smaller and Name column flexible
```js
- return `grid-template-columns: minmax(300px, 1fr) minmax(150px, auto) minmax(140px, auto) minmax(130px, auto) ${props.showBillableRate ? 'minmax(130px, auto)' : ''} 80px;`;
+ return `grid-template-columns: minmax(150px, auto) minmax(300px, 1fr) minmax(140px, auto) minmax(130px, auto) ${props.showBillableRate ? 'minmax(130px, auto)' : ''} 80px;`;
```

**Result:** Client column now has `minmax(150px, auto)` and Name column gets the flexible `minmax(300px, 1fr)` width.

### Fix Clients Table Missing Header

Added missing "Projects" header to the second column in clients table.

**File:** `resources/js/Components/Common/Client/ClientTableHeading.vue`
- **Added:** "Projects" header text to previously empty column
```vue
- <div class="px-3 py-1.5 text-left font-semibold text-text-primary"></div>
+ <div class="px-3 py-1.5 text-left font-semibold text-text-primary">Projects</div>
```

### Remove Status Column from Members Table

Removed redundant status column from members table to maintain consistency with projects and clients tables.

**File:** `resources/js/Components/Common/Member/MemberTableHeading.vue`
- **Removed:** Status column header
```vue
- <div class="px-3 py-1.5 text-left font-semibold text-text-primary">Status</div>
```

**File:** `resources/js/Components/Common/Member/MemberTableRow.vue`
- **Removed:** Status column showing Active/Inactive based on placeholder status
```vue
- <div class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary flex space-x-1 items-center font-medium">
-     <CheckCircleIcon v-if="member.is_placeholder === false" class="w-5"></CheckCircleIcon>
-     <span v-if="member.is_placeholder === false">Active</span>
-     <UserCircleIcon v-if="member.is_placeholder === true" class="w-5"></UserCircleIcon>
-     <span v-if="member.is_placeholder === true">Inactive</span>
- </div>
```

**File:** `resources/js/Components/Common/Member/MemberTable.vue`
- **Updated:** Grid template from 6 columns to 5 columns
```vue
- style="grid-template-columns: 1fr 1fr 180px 180px 150px 130px"
+ style="grid-template-columns: 1fr 1fr 180px 180px 130px"
```

**Result:** Members table now shows: Name → Email → Role → Billable Rate → Edit

### Remove Pie Chart from Reporting Pages

Completely removed the ReportingPieChart component from the entire application.

**Files modified:**
- `resources/js/Components/Common/Reporting/ReportingOverview.vue`  
- `resources/js/Pages/SharedReport.vue`

**Component removed:** `ReportingPieChart` (equivalent to the `ProjectsChartCard` removed from dashboard)

**Removed imports:**
```js
- import ReportingPieChart from '@/Components/Common/Reporting/ReportingPieChart.vue';
```

**Removed from templates:**
```vue
- <div class="px-2 lg:px-4">
-     <ReportingPieChart
-         :data="groupedPieChartData"></ReportingPieChart>
- </div>
```

**Removed computed properties:**
```js
- const groupedPieChartData = computed(() => { ... });
```

**File to delete:** `resources/js/Components/Common/Reporting/ReportingPieChart.vue` (component file no longer needed)

**Layout changes:**
- Removed sidebar layout (grid-cols-4) 
- Table now spans full width
- Bar chart (ReportingChart) remains for data visualization

**Result:** ReportingPieChart component completely removed from the application - no imports, no usage, no data generation.

### Performance Optimization: Projects Search Filter

Optimized the search filter in Projects.vue to improve performance when searching through projects and their associated clients.

**File modified:** `resources/js/Pages/Projects.vue`

**Problem:** O(n*m) complexity - the client lookup inside the filter loop was inefficient
- For each project (n), was calling `clients.value.find()` (m operations)
- With many projects and clients, this created performance bottlenecks

**Solution:** Create clientsMap before filtering for O(1) lookup

**Before:**
```js
// Search in client name
const client = clients.value.find(client => client.id === project.client_id);
const clientNameMatch = client?.name.toLowerCase().includes(query) || false;
```

**After:**
```js
// Create clients map for O(1) lookup performance
const clientsMap = new Map(clients.value.map(c => [c.id, c]));

filteredProjects = filteredProjects.filter((project) => {
    // Search in client name
    const client = clientsMap.get(project.client_id);
    const clientNameMatch = client?.name.toLowerCase().includes(query) || false;
    // ...
});
```

**Performance improvement:** O(n*m) → O(n+m) complexity
- Map creation: O(m) - done once
- Lookups: O(1) per project instead of O(m)
- Significant improvement with large datasets

### Fix Client Table Column Positioning

Fixed layout issue where Projects column had excessive spacing from Name column.

**File:** `resources/js/Components/Common/Client/ClientTableRow.vue`

**Problem:** Projects column had excessive padding pushing it away from Name column.

**Fixed styling:**
```vue
- class="whitespace-nowrap flex items-center space-x-5 3xl:pl-12 py-4 pr-3 text-sm font-medium text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12"
+ class="whitespace-nowrap py-4 pr-3 text-sm font-medium text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12"

- class="whitespace-nowrap flex items-center space-x-5 3xl:pl-12 py-4 pr-3 text-sm font-medium text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12"
+ class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary"
```

**Result:** Projects column now appears directly next to Name column with proper spacing.

### Restore Weekly Project Overview API Query

Fixed issue where removing ProjectsChartCard accidentally broke other dashboard functionality.

**File:** `resources/js/Components/Dashboard/ThisWeekOverview.vue`

**Problem:** When removing the ProjectsChartCard component, the `weeklyProjectOverview` API query was also removed, which might be used by other parts of the dashboard.

**Fixed by restoring API query:**
```js
// Set up the queries
const { data: weeklyProjectOverview } = useQuery({
    queryKey: ['weeklyProjectOverview', organizationId],
    queryFn: () => {
        return api.weeklyProjectOverview({
            params: {
                organization: organizationId.value!,
            },
        });
    },
    enabled: computed(() => !!organizationId.value),
});
```

**Result:** Dashboard data should now load properly without the ProjectsChartCard component being displayed.

### Add Search/Filter Functionality to Projects and Clients Pages

Added real-time search functionality with wildcard matching to improve user experience.

#### Projects Page Search

**File:** `resources/js/Pages/Projects.vue`

**Added imports:**
```js
import { MagnifyingGlassIcon } from '@heroicons/vue/20/solid';
```

**Added search state:**
```js
const searchQuery = ref('');
```

**Updated filtering logic:**
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
        filteredProjects = filteredProjects.filter((project) => {
            // Search in project name
            const projectNameMatch = project.name.toLowerCase().includes(query);
            
            // Search in client name
            const client = clients.value.find(client => client.id === project.client_id);
            const clientNameMatch = client?.name.toLowerCase().includes(query) || false;
            
            return projectNameMatch || clientNameMatch;
        });
    }

    return filteredProjects;
});
```

**Added search input UI:**
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

#### Clients Page Search

**File:** `resources/js/Pages/Clients.vue`

**Added imports:**
```js
import { MagnifyingGlassIcon } from '@heroicons/vue/20/solid';
```

**Added search state:**
```js
const searchQuery = ref('');
```

**Updated filtering logic:**
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

**Added search input UI:**
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

#### Features

- **Real-time filtering:** Results update as you type
- **Wildcard search:** Substring/partial matching
- **Projects page:** Searches both project names AND client names
- **Clients page:** Searches client names
- **Case-insensitive:** Search works regardless of capitalization
- **Works with tabs:** Search applies to both Active and Archived tabs
- **Clean UI:** Search input with magnifying glass icon, positioned next to action buttons

### Remove Pull Request Template

**File:** `.github/PULL_REQUEST_TEMPLATE.md`
- **Action:** Completely removed file
- **Reason:** Template was blocking contributions with early-stage project notice

**Original content removed:**
```markdown
```
