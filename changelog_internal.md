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
