<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\Client;
use App\Models\Organization;
use App\Models\Project;
use App\Models\User;
use App\Service\PermissionStore as AppPermissionStore;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\MessageBag;
use Inertia\Inertia;
use Laravel\Fortify\Features;
use Laravel\Jetstream\Jetstream;
use Symfony\Component\HttpFoundation\Response;

class ShareInertiaData
{
    /**
     * Handle the incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var AppPermissionStore $permissions */
        $permissions = app(AppPermissionStore::class);
        Inertia::share([
            'jetstream' => function () use ($request) {
                /** @var User|null $user */
                $user = $request->user();

                return [
                    'canCreateTeams' => $user !== null &&
                        Jetstream::userHasTeamFeatures($user) &&
                        Gate::forUser($user)->check('create', Jetstream::newTeamModel()),
                    'canManageTwoFactorAuthentication' => Features::canManageTwoFactorAuthentication(),
                    'canUpdatePassword' => Features::enabled(Features::updatePasswords()),
                    'canUpdateProfileInformation' => Features::canUpdateProfileInformation(),
                    'hasEmailVerification' => Features::enabled(Features::emailVerification()),
                    'flash' => $request->session()->get('flash', []),
                    'hasAccountDeletionFeatures' => Jetstream::hasAccountDeletionFeatures(),
                    'hasApiFeatures' => Jetstream::hasApiFeatures(),
                    'hasTeamFeatures' => Jetstream::hasTeamFeatures(),
                    'hasTermsAndPrivacyPolicyFeature' => Jetstream::hasTermsAndPrivacyPolicyFeature(),
                    'managesProfilePhotos' => Jetstream::managesProfilePhotos(),
                ];
            },
            'auth' => [
                'permissions' => $request->user() !== null && $request->user()->currentTeam !== null ? $permissions->getPermissions($request->user()->currentTeam) : [],
                'user' => function () use ($request): array {
                    /** @var User|null $user */
                    $user = $request->user();

                    if ($user === null) {
                        return [];
                    }

                    return array_merge([
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'email_verified_at' => $user->email_verified_at,
                        'current_team_id' => $user->current_team_id,
                        'profile_photo_path' => $user->profile_photo_path,
                        'timezone' => $user->timezone,
                        'week_start' => $user->week_start,
                        'profile_photo_url' => $user->profile_photo_url,
                        'two_factor_enabled' => Features::enabled(Features::twoFactorAuthentication())
                            && ! is_null($user->two_factor_secret),
                        'current_team' => $user->currentTeam !== null ? [
                            'id' => $user->currentTeam->id,
                            'user_id' => $user->currentTeam->user_id,
                            'name' => $user->currentTeam->name,
                            'personal_team' => $user->currentTeam->personal_team,
                            'currency' => $user->currentTeam->currency,
                            // Sidebar counts respecting visibility (projects assigned to the user)
                            'counts' => (function () use ($user) {
                                /** @var Organization $organization */
                                $organization = $user->currentTeam;
                                /** @var AppPermissionStore $permissionStore */
                                $permissionStore = app(AppPermissionStore::class);
                                $canViewAllProjects = $permissionStore->has($organization, 'projects:view:all');

                                // Projects count (active only), restricted to assigned projects if not allowed to view all
                                $projectsQuery = Project::query()
                                    ->whereBelongsTo($organization, 'organization')
                                    ->whereNull('archived_at');
                                if (! $canViewAllProjects) {
                                    $projectsQuery->visibleByEmployee($user);
                                }
                                $projectsCount = (clone $projectsQuery)->count();

                                // Clients count: distinct clients with at least one visible active project
                                $clientsCount = Client::query()
                                    ->whereBelongsTo($organization, 'organization')
                                    ->whereNull('archived_at')
                                    ->whereHas('projects', function ($query) use ($organization, $user, $canViewAllProjects): void {
                                        /** @var \Illuminate\Database\Eloquent\Builder<Project> $query */
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
                        ] : null,
                    ], array_filter([
                        'all_teams' => $user->organizations->map(function (Organization $organization): array {
                            return [
                                'id' => $organization->id,
                                'name' => $organization->name,
                                'personal_team' => $organization->personal_team,
                                'currency' => $organization->currency,
                                'membership' => [
                                    'role' => $organization->membership->role,
                                    'id' => $organization->membership->id,
                                ],
                            ];
                        })->all(),
                    ]));
                },
            ],
            'errorBags' => function () {
                /** @var array<string, MessageBag>|null $bags */
                $bags = Session::get('errors')?->getBags();
                $bagsCollection = collect($bags ?: []);

                return $bagsCollection->mapWithKeys(function (MessageBag $bag, string $key) {
                    return [$key => $bag->messages()];
                })->all();
            },
        ]);

        return $next($request);
    }
}
