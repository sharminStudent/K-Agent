<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreLeadRequest;
use App\Services\LeadService;
use Illuminate\Http\JsonResponse;

class LeadController extends Controller
{
    public function __construct(
        protected LeadService $leadService,
    ) {
    }

    public function store(StoreLeadRequest $request): JsonResponse
    {
        $lead = $this->leadService->storeLead($request->validated());

        return response()->json([
            'data' => [
                'lead_id' => $lead->id,
                'agent_id' => $lead->agent_id,
                'session_id' => $lead->chatSession?->public_id,
                'status' => $lead->status,
                'created_at' => $lead->created_at?->toISOString(),
            ],
        ], 201);
    }
}
