<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreFAQRequest;
use App\Http\Requests\UpdateFAQRequest;
use App\Services\FAQService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="Admin - FAQs",
 *     description="API Endpoints for FAQ management (Admin only)"
 * )
 */
class FAQController extends Controller
{
    public function __construct(
        private FAQService $faqService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/admin/faqs",
     *     summary="Get all FAQs (Admin)",
     *     description="Retrieve all FAQs including inactive ones, ordered by display order",
     *     tags={"Admin - FAQs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of all FAQs",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="FAQs retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="question", type="string", example="What services do you offer?"),
     *                 @OA\Property(property="answer", type="string", example="We offer a wide range of nursing services including home care, elderly care, and specialized medical assistance."),
     *                 @OA\Property(property="order", type="integer", example=1),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="available_translations", type="array", @OA\Items(type="string"), example={"ar", "en"}, description="Available translation locales"),
     *                 @OA\Property(property="translation_count", type="integer", example=2, description="Number of translations available"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $faqs = $this->faqService->getAllFAQs();
        
        return response()->json([
            'success' => true,
            'message' => 'FAQs retrieved successfully',
            'data' => $faqs->map(fn($faq) => [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'order' => $faq->order,
                'is_active' => $faq->is_active,
                'available_translations' => $faq->getAvailableLocales(), // Show available translation locales
                'translation_count' => $faq->translations()->count(), // Count of translations
                'created_at' => $faq->created_at->toISOString(),
                'updated_at' => $faq->updated_at->toISOString(),
            ])
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/faqs",
     *     summary="Create a new FAQ (Admin)",
     *     description="Create a new FAQ entry",
     *     tags={"Admin - FAQs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"question", "answer"},
     *             @OA\Property(property="question", type="string", example="What services do you offer?", description="The FAQ question"),
     *             @OA\Property(property="answer", type="string", example="We offer a wide range of nursing services including home care, elderly care, and specialized medical assistance.", description="The FAQ answer"),
     *             @OA\Property(property="order", type="integer", example=1, description="Display order (optional)"),
     *             @OA\Property(property="is_active", type="boolean", example=true, description="Whether the FAQ is active (optional)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="FAQ created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="FAQ created successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="question", type="string", example="What services do you offer?"),
     *                 @OA\Property(property="answer", type="string", example="We offer a wide range of nursing services including home care, elderly care, and specialized medical assistance."),
     *                 @OA\Property(property="order", type="integer", example=1),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function store(StoreFAQRequest $request): JsonResponse
    {
        $faqDTO = $this->faqService->createFAQ($request->validated());
        
        return response()->json([
            'success' => true,
            'message' => 'FAQ created successfully',
            'data' => $faqDTO->toArray()
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/faqs/{id}",
     *     summary="Get a specific FAQ (Admin)",
     *     description="Retrieve a specific FAQ by its ID",
     *     tags={"Admin - FAQs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="FAQ ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="FAQ details",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="FAQ retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="question", type="string", example="What services do you offer?"),
     *                 @OA\Property(property="answer", type="string", example="We offer a wide range of nursing services including home care, elderly care, and specialized medical assistance."),
     *                 @OA\Property(property="order", type="integer", example=1),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="FAQ not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $faq = $this->faqService->getFAQById($id);
        
        if (!$faq) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'FAQ retrieved successfully',
            'data' => [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'order' => $faq->order,
                'is_active' => $faq->is_active,
                'created_at' => $faq->created_at->toISOString(),
                'updated_at' => $faq->updated_at->toISOString(),
            ]
        ]);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/faqs/{id}",
     *     summary="Update an FAQ (Admin)",
     *     description="Update an existing FAQ entry",
     *     tags={"Admin - FAQs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="FAQ ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="question", type="string", example="What services do you offer?", description="The FAQ question"),
     *             @OA\Property(property="answer", type="string", example="We offer a wide range of nursing services including home care, elderly care, and specialized medical assistance.", description="The FAQ answer"),
     *             @OA\Property(property="order", type="integer", example=1, description="Display order"),
     *             @OA\Property(property="is_active", type="boolean", example=true, description="Whether the FAQ is active")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="FAQ updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="FAQ updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="question", type="string", example="What services do you offer?"),
     *                 @OA\Property(property="answer", type="string", example="We offer a wide range of nursing services including home care, elderly care, and specialized medical assistance."),
     *                 @OA\Property(property="order", type="integer", example=1),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="FAQ not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function update(UpdateFAQRequest $request, int $id): JsonResponse
    {
        $faqDTO = $this->faqService->updateFAQ($id, $request->validated());
        
        if (!$faqDTO) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'FAQ updated successfully',
            'data' => $faqDTO->toArray()
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/faqs/{id}",
     *     summary="Delete an FAQ (Admin)",
     *     description="Delete an FAQ entry",
     *     tags={"Admin - FAQs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="FAQ ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="FAQ deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="FAQ deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="FAQ not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function destroy(int $id): JsonResponse
    {
        $deleted = $this->faqService->deleteFAQ($id);
        
        if (!$deleted) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'FAQ deleted successfully'
        ]);
    }

    /**
     * @OA\Patch(
     *     path="/api/admin/faqs/{id}/toggle",
     *     summary="Toggle FAQ active status (Admin)",
     *     description="Toggle the active status of an FAQ",
     *     tags={"Admin - FAQs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="FAQ ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="FAQ status toggled successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="FAQ status toggled successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="question", type="string", example="What services do you offer?"),
     *                 @OA\Property(property="answer", type="string", example="We offer a wide range of nursing services including home care, elderly care, and specialized medical assistance."),
     *                 @OA\Property(property="order", type="integer", example=1),
     *                 @OA\Property(property="is_active", type="boolean", example=false),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="FAQ not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function toggleStatus(int $id): JsonResponse
    {
        $faqDTO = $this->faqService->toggleFAQStatus($id);
        
        if (!$faqDTO) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ not found'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'FAQ status toggled successfully',
            'data' => $faqDTO->toArray()
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/faqs/reorder",
     *     summary="Reorder FAQs (Admin)",
     *     description="Reorder FAQs by providing an array of FAQ IDs in the desired order",
     *     tags={"Admin - FAQs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"faq_ids"},
     *             @OA\Property(property="faq_ids", type="array", @OA\Items(type="integer"), example={1,3,2,4}, description="Array of FAQ IDs in the desired order")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="FAQs reordered successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="FAQs reordered successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function reorder(Request $request): JsonResponse
    {
        $request->validate([
            'faq_ids' => 'required|array',
            'faq_ids.*' => 'integer|exists:faqs,id'
        ]);

        $this->faqService->reorderFAQs($request->faq_ids);

        return response()->json([
            'success' => true,
            'message' => 'FAQs reordered successfully'
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/admin/faqs/{id}/translations",
     *     summary="Get FAQ translations (Admin)",
     *     description="Get all translations for a specific FAQ",
     *     tags={"Admin - FAQs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="FAQ ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="FAQ translations retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Translations retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="locale", type="string", example="ar"),
     *                 @OA\Property(property="question", type="string", example="ما هي الخدمات التي تقدمونها؟"),
     *                 @OA\Property(property="answer", type="string", example="نقدم مجموعة واسعة من خدمات التمريض"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="FAQ not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function getTranslations(int $id): JsonResponse
    {
        $faq = $this->faqService->getFAQById($id);
        
        if (!$faq) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ not found'
            ], 404);
        }

        $translations = $faq->translations()->get()->map(function($translation) {
            return [
                'id' => $translation->id,
                'locale' => $translation->locale,
                'question' => $translation->question,
                'answer' => $translation->answer,
                'created_at' => $translation->created_at->toISOString(),
                'updated_at' => $translation->updated_at->toISOString()
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Translations retrieved successfully',
            'data' => $translations
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/faqs/{id}/translations",
     *     summary="Add translation to FAQ (Admin)",
     *     description="Add a translation for a specific FAQ",
     *     tags={"Admin - FAQs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="FAQ ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"locale", "question", "answer"},
     *             @OA\Property(property="locale", type="string", example="ar", description="Language code (ar, en)"),
     *             @OA\Property(property="question", type="string", example="ما هي الخدمات التي تقدمونها؟", description="Translated question"),
     *             @OA\Property(property="answer", type="string", example="نقدم مجموعة واسعة من خدمات التمريض", description="Translated answer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Translation added successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Translation added successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="locale", type="string", example="ar"),
     *                 @OA\Property(property="question", type="string", example="ما هي الخدمات التي تقدمونها؟"),
     *                 @OA\Property(property="answer", type="string", example="نقدم مجموعة واسعة من خدمات التمريض"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="FAQ not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function addTranslation(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'locale' => 'required|string|in:ar,en',
            'question' => 'required|string|max:255',
            'answer' => 'required|string'
        ]);

        $faq = $this->faqService->getFAQById($id);
        
        if (!$faq) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ not found'
            ], 404);
        }

        // Check if translation already exists
        if ($faq->hasTranslation($request->locale)) {
            return response()->json([
                'success' => false,
                'message' => 'Translation for this locale already exists. Use update instead.'
            ], 422);
        }

        $faq->setTranslation($request->locale, [
            'question' => $request->question,
            'answer' => $request->answer
        ]);

        $translation = $faq->translations()->where('locale', $request->locale)->first();

        return response()->json([
            'success' => true,
            'message' => 'Translation added successfully',
            'data' => [
                'id' => $translation->id,
                'locale' => $translation->locale,
                'question' => $translation->question,
                'answer' => $translation->answer,
                'created_at' => $translation->created_at->toISOString(),
                'updated_at' => $translation->updated_at->toISOString()
            ]
        ], 201);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/faqs/{id}/translations/{locale}",
     *     summary="Update FAQ translation (Admin)",
     *     description="Update a specific translation for an FAQ",
     *     tags={"Admin - FAQs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="FAQ ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="locale",
     *         in="path",
     *         required=true,
     *         description="Language code",
     *         @OA\Schema(type="string", example="ar")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"question", "answer"},
     *             @OA\Property(property="question", type="string", example="ما هي الخدمات التي تقدمونها؟", description="Translated question"),
     *             @OA\Property(property="answer", type="string", example="نقدم مجموعة واسعة من خدمات التمريض", description="Translated answer")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Translation updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Translation updated successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="locale", type="string", example="ar"),
     *                 @OA\Property(property="question", type="string", example="ما هي الخدمات التي تقدمونها؟"),
     *                 @OA\Property(property="answer", type="string", example="نقدم مجموعة واسعة من خدمات التمريض"),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="FAQ or translation not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function updateTranslation(Request $request, int $id, string $locale): JsonResponse
    {
        $request->validate([
            'question' => 'required|string|max:255',
            'answer' => 'required|string'
        ]);

        $faq = $this->faqService->getFAQById($id);
        
        if (!$faq) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ not found'
            ], 404);
        }

        if (!$faq->hasTranslation($locale)) {
            return response()->json([
                'success' => false,
                'message' => 'Translation not found'
            ], 404);
        }

        $faq->setTranslation($locale, [
            'question' => $request->question,
            'answer' => $request->answer
        ]);

        $translation = $faq->translations()->where('locale', $locale)->first();

        return response()->json([
            'success' => true,
            'message' => 'Translation updated successfully',
            'data' => [
                'id' => $translation->id,
                'locale' => $translation->locale,
                'question' => $translation->question,
                'answer' => $translation->answer,
                'created_at' => $translation->created_at->toISOString(),
                'updated_at' => $translation->updated_at->toISOString()
            ]
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/faqs/{id}/translations/{locale}",
     *     summary="Delete FAQ translation (Admin)",
     *     description="Delete a specific translation for an FAQ",
     *     tags={"Admin - FAQs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="FAQ ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="locale",
     *         in="path",
     *         required=true,
     *         description="Language code",
     *         @OA\Schema(type="string", example="ar")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Translation deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Translation deleted successfully")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="FAQ or translation not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin access required"
     *     )
     * )
     */
    public function deleteTranslation(int $id, string $locale): JsonResponse
    {
        $faq = $this->faqService->getFAQById($id);
        
        if (!$faq) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ not found'
            ], 404);
        }

        $translation = $faq->translations()->where('locale', $locale)->first();
        
        if (!$translation) {
            return response()->json([
                'success' => false,
                'message' => 'Translation not found'
            ], 404);
        }

        $translation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Translation deleted successfully'
        ]);
    }
}