<?php

namespace App\Http\Controllers;

use App\DTOs\Request\CreateRequestDTO;
use App\DTOs\Request\UpdateRequestDTO;
use App\Http\Requests\CreateRequestRequest;
use App\Http\Requests\UpdateRequestRequest;
use App\Services\Interfaces\IRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class RequestController extends Controller
{
    public function __construct(
        private readonly IRequestService $requestService
    ) {
        $this->authorizeResource(Request::class);
    }

    public function index(): JsonResponse
    {
        $user = Auth::user();
        $requests = match ($user->role_id) {
            1 => $this->requestService->getAllRequests(
                request()->only(['status', 'time_type', 'nurse_gender']),
                request()->input('per_page', 15)
            ),
            default => $this->requestService->getUserRequests(
                $user->id,
                request()->only(['status', 'time_type']),
                request()->input('per_page', 15)
            ),
        };

        return response()->json($requests);
    }

    public function store(CreateRequestRequest $request): JsonResponse
    {
        $dto = CreateRequestDTO::fromArray($request->validated());
        $response = $this->requestService->createRequest($dto, Auth::id());

        return response()->json($response, 201);
    }

    public function show(int $id): JsonResponse
    {
        $response = $this->requestService->getRequest($id);
        return response()->json($response);
    }

    public function update(UpdateRequestRequest $request, int $id): JsonResponse
    {
        $dto = UpdateRequestDTO::fromArray($request->validated());
        $response = $this->requestService->updateRequest($id, $dto);

        return response()->json($response);
    }

    public function destroy(int $id): JsonResponse
    {
        $this->requestService->deleteRequest($id);
        return response()->json(['message' => 'Request removed from admin view, but still available to users.'], 200);
    }
}