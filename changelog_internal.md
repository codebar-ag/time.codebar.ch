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
`
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


### Remove Active from Clients & Projects

Remove Active Icon + Label from clients & projects pages.

### Projects.index
- Sort by clients.name asc then projects.name asc
- move clients.name column before projects.name column