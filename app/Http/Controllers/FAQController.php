<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFAQRequest;
use App\Http\Requests\UpdateFAQRequest;
use App\Services\FAQService;
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
        private FAQService $faqService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/faqs",
     *     summary="Get all active FAQs",
     *     description="Retrieve all active FAQs ordered by their display order. Authentication required.",
     *     tags={"FAQs"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="List of active FAQs",
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
        $faqs = $this->faqService->getActiveFAQs();
        
        return response()->json([
            'success' => true,
            'message' => 'FAQs retrieved successfully',
            'data' => $faqs->map(fn($faq) => [
                'id' => $faq->id,
                'question' => $faq->question,
                'answer' => $faq->answer,
                'order' => $faq->order,
                'is_active' => $faq->is_active,
                'created_at' => $faq->created_at->toISOString(),
                'updated_at' => $faq->updated_at->toISOString(),
            ])
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/faqs/{id}",
     *     summary="Get a specific FAQ",
     *     description="Retrieve a specific FAQ by its ID. Authentication required.",
     *     tags={"FAQs"},
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
     *         description="FAQ not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="FAQ not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized - Authentication required"
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
}