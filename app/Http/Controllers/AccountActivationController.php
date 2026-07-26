<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\StoreAccountActivationRequest;
use App\Http\Requests\UpdateAccountActivationRequest;
use App\Http\Resources\AccountActivationResource;
use App\Models\AccountActivation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

/**
 * CRUD endpoints for account activation records.
 *
 * Code generation, hashing and email delivery belong to the activation service
 * (authentication module); this controller manages the record lifecycle only.
 */
class AccountActivationController extends Controller
{
    /**
     * List paginated activation records with their owning user.
     */
    public function index(): AnonymousResourceCollection
    {
        $activations = AccountActivation::with('user')->latest('id')->paginate(15);

        return AccountActivationResource::collection($activations);
    }

    /**
     * Store an activation record.
     *
     * Placeholder values are used for the hash, expiration and status until the
     * activation service is in place to generate and deliver the real code.
     */
    public function store(StoreAccountActivationRequest $request): JsonResponse
    {
        $data = $request->validated();
        $data['code_hash'] = '';
        $data['status'] = 'PENDING';
        $data['expires_at'] = now()->addHours(AccountActivation::VALIDITY_HOURS);

        $activation = AccountActivation::create($data);

        return response()->json([
            'data' => new AccountActivationResource($activation->load('user')),
            'message' => 'Account activation record created successfully.',
            'errors' => null,
        ], Response::HTTP_CREATED);
    }

    /**
     * Show a single activation record.
     */
    public function show(AccountActivation $accountActivation): JsonResponse
    {
        return response()->json([
            'data' => new AccountActivationResource($accountActivation->load('user')),
            'message' => null,
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Update the lifecycle fields of an activation record.
     */
    public function update(UpdateAccountActivationRequest $request, AccountActivation $accountActivation): JsonResponse
    {
        $accountActivation->update($request->validated());

        return response()->json([
            'data' => new AccountActivationResource($accountActivation->load('user')),
            'message' => 'Account activation updated successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }

    /**
     * Delete an activation record.
     */
    public function destroy(AccountActivation $accountActivation): JsonResponse
    {
        $accountActivation->delete();

        return response()->json([
            'data' => null,
            'message' => 'Account activation removed successfully.',
            'errors' => null,
        ], Response::HTTP_OK);
    }
}
