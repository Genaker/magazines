<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\MagazineMemberRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreMagazineRequest;
use App\Http\Resources\Api\V1\MagazineResource;
use App\Models\Magazine;
use App\Support\CustomFieldSchema;
use App\Support\CustomFields;
use App\Support\Slugger;
use Illuminate\Http\JsonResponse;

class MagazineController extends Controller
{
    public function store(StoreMagazineRequest $request): JsonResponse
    {
        $data = $request->validated();

        $magazine = Magazine::query()->create([
            'owner_id' => $request->user()->id,
            'name' => $data['name'],
            'slug' => Slugger::unique($data['name'], new Magazine, 'slug'),
            'description' => $data['description'] ?? null,
            'custom_fields' => CustomFields::collect($request->input('custom_fields'), CustomFieldSchema::CONTEXT_MAGAZINE),
        ]);

        $magazine->members()->attach($request->user()->id, [
            'role' => MagazineMemberRole::Owner->value,
        ]);

        return (new MagazineResource($magazine))
            ->response()
            ->setStatusCode(201);
    }
}
