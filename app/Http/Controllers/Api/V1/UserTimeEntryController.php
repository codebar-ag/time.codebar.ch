<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\V1\TimeEntry\TimeEntryCollection;
use App\Http\Resources\V1\TimeEntry\TimeEntryResource;
use App\Models\Organization;
use App\Models\TimeEntry;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class UserTimeEntryController extends Controller
{
    /**
     * Get all time entries of the current user
     *
     * This endpoint is independent of organization.
     *
     * @operationId getMyTimeEntries
     */
    public function my(): TimeEntryCollection
    {
        $user = $this->user();

        $timeEntries = TimeEntry::query()
            ->whereBelongsTo($user, 'user')
            ->orderBy('start', 'desc')
            ->limit(100) // Limit to avoid performance issues
            ->get();

        return new TimeEntryCollection($timeEntries);
    }

    /**
     * Get the active time entry of the current user
     *
     * This endpoint is independent of organization.
     *
     * @operationId getMyActiveTimeEntry
     */
    public function myActive(): JsonResponse
    {
        $user = $this->user();

        $activeTimeEntriesOfUser = TimeEntry::query()
            ->whereBelongsTo($user, 'user')
            ->whereNull('end')
            ->orderBy('start', 'desc')
            ->get();

        if ($activeTimeEntriesOfUser->count() > 1) {
            Log::warning('User has more than one active time entry.', [
                'user' => $user->getKey(),
            ]);
        }

        $activeTimeEntry = $activeTimeEntriesOfUser->first();

        if ($activeTimeEntry !== null) {
            return response()->json([
                'data' => new TimeEntryResource($activeTimeEntry),
            ]);
        } else {
            return response()->json([
                'data' => null,
                'message' => 'No active time entry',
            ], 200);
        }
    }
}
