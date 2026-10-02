<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StorePostRequest;
use App\Http\Resources\Api\V1\PostResource;
use App\Services\PostCreator;
use Illuminate\Http\JsonResponse;

class PostController extends Controller
{
    public function store(StorePostRequest $request, PostCreator $creator): JsonResponse
    {
        $post = $creator->createArticle($request->user(), $request->validated());

        return (new PostResource($post))
            ->response()
            ->setStatusCode(201);
    }
}
