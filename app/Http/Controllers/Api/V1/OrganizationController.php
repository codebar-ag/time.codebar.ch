<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Enums\Role;
use App\Http\Requests\V1\Organization\OrganizationUpdateRequest;
use App\Http\Resources\V1\Organization\OrganizationResource;
use App\Models\Client;
use App\Models\Member;
use App\Models\Organization;
use App\Models\Project;
use App\Models\Tag;
use App\Service\BillableRateService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;

class OrganizationController extends Controller
{
    /**
     * Get organization
     *
     * @operationId getOrganization
     *
     * @throws AuthorizationException
     */
    public function show(Organization $organization): OrganizationResource
    {
        $this->checkPermission($organization, 'organizations:view');

        $showBillableRate = $this->member($organization)->role !== Role::Employee->value || $organization->employees_can_see_billable_rates;

        return new OrganizationResource($organization, $showBillableRate);
    }

    /**
     * Get counts for sidebar items
     *
     * @operationId getOrganizationCounts
     *
     * @throws AuthorizationException
     */
    public function getCounts(Organization $organization): JsonResponse
    {
        $this->checkPermission($organization, 'organizations:view');

        $counts = [
            'projects' => Project::where('organization_id', $organization->id)->whereNull('archived_at')->count(),
            'clients' => Client::where('organization_id', $organization->id)->whereNull('archived_at')->count(),
            'members' => Member::where('organization_id', $organization->id)->count(),
            'tags' => Tag::where('organization_id', $organization->id)->count(),
        ];

        return response()->json(['data' => $counts]);
    }

    /**
     * Update organization
     *
     * @operationId updateOrganization
     *
     * @throws AuthorizationException
     */
    public function update(Organization $organization, OrganizationUpdateRequest $request, BillableRateService $billableRateService): OrganizationResource
    {
        $this->checkPermission($organization, 'organizations:update');

        if ($request->getName() !== null) {
            $organization->name = $request->getName();
        }
        if ($request->getEmployeesCanSeeBillableRates() !== null) {
            $organization->employees_can_see_billable_rates = $request->getEmployeesCanSeeBillableRates();
        }
        if ($request->getNumberFormat() !== null) {
            $organization->number_format = $request->getNumberFormat();
        }
        if ($request->getCurrencyFormat() !== null) {
            $organization->currency_format = $request->getCurrencyFormat();
        }
        if ($request->getDateFormat() !== null) {
            $organization->date_format = $request->getDateFormat();
        }
        if ($request->getIntervalFormat() !== null) {
            $organization->interval_format = $request->getIntervalFormat();
        }
        if ($request->getTimeFormat() !== null) {
            $organization->time_format = $request->getTimeFormat();
        }
        $hasBillableRate = $request->has('billable_rate');
        if ($hasBillableRate) {
            $oldBillableRate = $organization->billable_rate;
            $organization->billable_rate = $request->getBillableRate();
        }
        $organization->save();

        if ($hasBillableRate && $oldBillableRate !== $request->getBillableRate()) {
            $billableRateService->updateTimeEntriesBillableRateForOrganization($organization);
        }

        return new OrganizationResource($organization, true);
    }
}
