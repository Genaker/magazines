<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreMagazineCategoryRequest;
use App\Http\Requests\Api\V1\StoreSiteCategoryRequest;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Models\Category;
use App\Models\Magazine;
use App\Support\Slugger;
use Illuminate\Http\JsonResponse;

class CategoryController extends Controller
{
    public function storeSite(StoreSiteCategoryRequest $request): JsonResponse
    {
        $category = $this->createCategory($request->validated(), magazineId: null);

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    public function storeForMagazine(StoreMagazineCategoryRequest $request, Magazine $magazine): JsonResponse
    {
        $category = $this->createCategory($request->validated(), magazineId: $magazine->id);

        return (new CategoryResource($category))
            ->response()
            ->setStatusCode(201);
    }

    /** @param  array<string, mixed>  $data */
    private function createCategory(array $data, ?int $magazineId): Category
    {
        $category = Category::query()->create([
            'name' => $data['name'],
            'slug' => Slugger::unique($data['name'], new Category, 'slug'),
            'parent_id' => $data['parent_id'] ?? null,
            'magazine_id' => $magazineId,
            'description' => $data['description'] ?? null,
            'icon' => $data['icon'] ?? null,
            'sort_order' => $data['sort_order'] ?? 0,
        ]);

        if ($magazineId !== null) {
            $category->load('magazine');
        }

        return $category;
    }
}
