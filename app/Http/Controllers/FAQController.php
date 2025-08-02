<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFAQRequest;
use App\Http\Requests\UpdateFAQRequest;
use App\Services\FAQService;
use App\Services\FAQTranslationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @OA\Tag(
 *     name="FAQs",
 *     description="API Endpoints for FAQ management"
 * )
 */
class FAQController extends Controller
{
    public function __construct(
        private FAQService $faqService,
        private FAQTranslationService $faqTranslationService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/faqs",
     *     summary="Get all active FAQs with translations",
     *     description="Retrieve all active FAQs ordered by their display order with content translated based on Accept-Language header. Authentication required.",
     *     tags={"FAQs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Accept-Language",
     *         in="header",
     *         description="Language preference (en, ar) - affects FAQ content",
     *         required=false,
     *         @OA\Schema(type="string", example="ar")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="List of active FAQs with translations",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="FAQs retrieved successfully"),
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="question", type="string", example="ما هي الخدمات التي تقدمونها؟"),
     *                 @OA\Property(property="answer", type="string", example="نقدم مجموعة واسعة من خدمات التمريض"),
     *                 @OA\Property(property="order", type="integer", example=1),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="translation", type="object", description="Translation info (only included when translation exists)",
     *                     @OA\Property(property="locale", type="string", example="ar"),
     *                     @OA\Property(property="question", type="string", example="ما هي الخدمات التي تقدمونها؟"),
     *                     @OA\Property(property="answer", type="string", example="نقدم مجموعة واسعة من خدمات التمريض")
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $locale = app()->getLocale();
        $faqs = $this->faqService->getActiveFAQs();
        
        $faqs = $this->faqTranslationService->getFAQsWithTranslations($faqs, $locale);
        
        return response()->json([
            'success' => true,
            'message' => 'FAQs retrieved successfully',
            'data' => $faqs->map(fn($faq) => [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'order' => $faq->order,
                'is_active' => $faq->is_active,
                'translation' => $faq->translation,
                'created_at' => $faq->created_at->toISOString(),
                'updated_at' => $faq->updated_at->toISOString(),
            ])
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/faqs/{id}",
     *     summary="Get a specific FAQ with translations",
     *     description="Retrieve a specific FAQ by its ID with content translated based on Accept-Language header. Authentication required.",
     *     tags={"FAQs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="FAQ ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="Accept-Language",
     *         in="header",
     *         description="Language preference (en, ar) - affects FAQ content",
     *         required=false,
     *         @OA\Schema(type="string", example="ar")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="FAQ details with translations",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="FAQ retrieved successfully"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="question", type="string", example="ما هي الخدمات التي تقدمونها؟"),
     *                 @OA\Property(property="answer", type="string", example="نقدم مجموعة واسعة من خدمات التمريض"),
     *                 @OA\Property(property="order", type="integer", example=1),
     *                 @OA\Property(property="is_active", type="boolean", example=true),
     *                 @OA\Property(property="translation", type="object", description="Translation info (only included when translation exists)",
     *                     @OA\Property(property="locale", type="string", example="ar"),
     *                     @OA\Property(property="question", type="string", example="ما هي الخدمات التي تقدمونها؟"),
     *                     @OA\Property(property="answer", type="string", example="نقدم مجموعة واسعة من خدمات التمريض")
     *                 ),
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
     *         description="Unauthorized - Authentication required"
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $locale = app()->getLocale();
        $faq = $this->faqService->getFAQById($id);
        
        if (!$faq) {
            return response()->json([
                'success' => false,
                'message' => 'FAQ not found'
            ], 404);
        }

        $faq = $this->faqTranslationService->getFAQWithTranslations($faq, $locale);
        
        return response()->json([
            'success' => true,
            'message' => 'FAQ retrieved successfully',
            'data' => [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'order' => $faq->order,
                'is_active' => $faq->is_active,
                'translation' => $faq->translation,
                'created_at' => $faq->created_at->toISOString(),
                'updated_at' => $faq->updated_at->toISOString(),
            ]
        ]);
    }
}