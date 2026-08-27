<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePersonRequest;
use App\Http\Requests\UpdatePersonRequest;
use App\Http\Resources\PersonResource;
use App\Models\Person;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PersonController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        // Retrieve all people and return them as a collection of PersonResource
        $people = Person::latest()->paginate(10);
        return PersonResource::collection($people);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePersonRequest $request): JsonResponse
    {
        $person = Person::create($request->validated());
        return (new PersonResource($person))->response()->setStatusCode(201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Person $person): PersonResource
    {
        return new PersonResource($person);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePersonRequest $request, Person $person): PersonResource
    {
        $person->update($request->validated());
        return new PersonResource($person);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Person $person): JsonResponse
    {
        $person->delete();
        return response()->json([
            'success' => true,
            'message' => 'Persona eliminada correctamente.'
        ], 200);
    }
}
