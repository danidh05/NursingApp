<?php

namespace App\Http\Controllers;

use App\Models\DoctorCategory;
use Illuminate\Http\JsonResponse;

class DoctorCategoryController extends Controller
{
    public function index(): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        $cats = DoctorCategory::with('translations')->get();
        return response()->json([
            'success' => true,
            'data' => $cats->map(function ($cat) use ($locale) {
                $t = $cat->translate($locale);
                return [
                    'id' => $cat->id,
                    'name' => $t?->name,
                    'image' => $cat->image_url,
                ];
            }),
        ]);
    }

    public function show(DoctorCategory $doctorCategory): JsonResponse
    {
        $locale = app()->getLocale() ?: 'en';
        $t = $doctorCategory->translate($locale);
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $doctorCategory->id,
                'name' => $t?->name,
                'image' => $doctorCategory->image_url,
            ],
        ]);
    }
}

