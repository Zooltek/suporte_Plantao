<?php

namespace App\Http\Controllers\Admin\API\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\API\V1\CategoryResource;
use App\Http\Requests\Admin\API\V1\StoreCategoryRequest;
use App\Http\Requests\Admin\API\V1\StoreRootCategoryRequest;
use App\Http\Requests\Admin\API\V1\StoreSubcategoryRequest;
use App\Http\Requests\Admin\API\V1\UpdateCategoryRequest;
use App\Models\Category;
use App\Services\Admin\CategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use InvalidArgumentException;
use OpenApi\Attributes as OA;

class CategoryController extends Controller
{
    public function __construct(
        protected CategoryService $categoryService
    ) {}
    #[OA\Get(
        path: '/admin/api/v1/categories',
        summary: 'Listar todas as categorias',
        description: 'Retorna uma lista de todas as categorias com suas descrições',
        operationId: 'getCategoriesAdmin',
        tags: ['Admin - Categorias'],
        security: [['sanctum' => []]],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', type: 'array', items: new OA\Items(ref: '#/components/schemas/CategoryResource'))
                    ]
                )
            ),
            new OA\Response(response: 401, description: 'Não autenticado'),
            new OA\Response(response: 403, description: 'Não autorizado')
        ]
    )]
    public function index(): AnonymousResourceCollection
    {
        return CategoryResource::collection($this->categoryService->getAllCategories());
    }

    #[OA\Post(
        path: '/admin/api/v1/categories',
        summary: 'Criar nova categoria',
        operationId: 'storeCategoryAdmin',
        tags: ['Admin - Categorias'],
        security: [['sanctum' => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CategoryRequest')
        ),
        responses: [
            new OA\Response(
                response: 201,
                description: 'Criado com sucesso',
                content: new OA\JsonContent(ref: '#/components/schemas/CategoryResource')
            ),
            new OA\Response(response: 400, description: 'Dados inválidos')
        ]
    )]
    public function store(StoreCategoryRequest $request)
    {
        try {
            $category = $this->categoryService->create($request->validated());
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return new CategoryResource($category);
    }

    public function storeRoot(StoreRootCategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->categoryService->createRootCategory($request->validated());
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Categoria pai criada com sucesso.',
            'data' => new CategoryResource($category),
        ], 201);
    }

    public function storeSubcategory(StoreSubcategoryRequest $request): JsonResponse
    {
        try {
            $category = $this->categoryService->createSubcategory($request->validated());
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Subcategoria criada com sucesso.',
            'data' => new CategoryResource($category),
        ], 201);
    }

    #[OA\Get(
        path: '/admin/api/v1/categories/{id}',
        summary: 'Obter categoria específica',
        operationId: 'getCategoryAdmin',
        tags: ['Admin - Categorias'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'data', ref: '#/components/schemas/CategoryResource')
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Não encontrada')
        ]
    )]
    public function show(string $id): CategoryResource
    {
        return new CategoryResource(Category::with('description')->findOrFail($id));
    }

    #[OA\Put(
        path: '/admin/api/v1/categories/{id}',
        summary: 'Atualizar categoria',
        operationId: 'updateCategoryAdmin',
        tags: ['Admin - Categorias'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(ref: '#/components/schemas/CategoryRequest')
        ),
        responses: [
            new OA\Response(response: 200, description: 'Atualizada com sucesso'),
            new OA\Response(response: 404, description: 'Não encontrada')
        ]
    )]
    public function update(UpdateCategoryRequest $request, string $id)
    {
        $category = Category::findOrFail($id);

        try {
            $this->categoryService->update($category, $request->validated());
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Categoria atualizada com sucesso',
            'data'    => new CategoryResource($category->fresh('description'))
        ]);
    }

    #[OA\Delete(
        path: '/admin/api/v1/categories/{id}',
        summary: 'Excluir categoria',
        operationId: 'deleteCategoryAdmin',
        tags: ['Admin - Categorias'],
        security: [['sanctum' => []]],
        parameters: [
            new OA\Parameter(name: 'id', in: 'path', required: true, schema: new OA\Schema(type: 'string'))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: 'Excluído com sucesso',
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: 'success', type: 'boolean', example: true),
                        new OA\Property(property: 'message', type: 'string', example: 'Categoria excluída com sucesso')
                    ]
                )
            ),
            new OA\Response(response: 404, description: 'Não encontrada')
        ]
    )]
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);

        try {
            $this->categoryService->delete($category);
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }

        return response()->json([
            'success' => true,
            'message' => 'Categoria excluída com sucesso'
        ]);
    }
}
