<?php

namespace App\Http\Controllers;

use App\Models\Service;
use App\Services\ServiceTranslationService;
use App\Services\ImageStorageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

/**
 * Service Controller
 * 
 * Manages nursing services with the following features:
 * 
 * ## Service Pictures (service_pic)
 * - Stored as URL strings in database (NOT file uploads)
 * - Validation: Must be valid URL format
 * - No file upload functionality - use external image hosting
 * - Field is nullable/optional
 * 
 * ## Categories (category_id)
 * - REQUIRED field for service creation
 * - Available categories: 1=Home Care, 2=Emergency Care, 3=Elderly Care, 4=Post-Surgery Care, 5=Chronic Disease Management
 * - Must reference existing category ID
 * 
 * ## Pricing
 * - Regular price (required)
 * - Optional discount_price (must be less than regular price)
 * 
 * ## Translations
 * - Supports English and Arabic translations
 * - Automatic language detection via Accept-Language header
 * - Fallback to English when translation not available
 */
class ServiceController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private ServiceTranslationService $serviceTranslationService,
        private ImageStorageService $imageStorageService
    ) {}

    /**
     * @OA\Get(
     *     path="/api/services",
     *     summary="List all services with area-based pricing and translations",
     *     description="Retrieve a list of all services with pricing based on user's area and content translated based on Accept-Language header. If user has an area assigned and area-specific pricing exists, shows area price and area name. Otherwise shows original price without area information.",
     *     tags={"Services"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="Accept-Language",
     *         in="header",
     *         description="Language preference (en, ar) - affects service names",
     *         required=false,
     *         @OA\Schema(type="string", example="ar")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Services list retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="services", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="رعاية التمريض المنزلية"),
     *                 @OA\Property(property="description", type="string", example="Professional nursing care at home"),
     *                 @OA\Property(property="price", type="number", format="float", example=120.00, description="Area-specific price if user has area and pricing exists, otherwise original price"),
     *                 @OA\Property(property="discount_price", type="number", format="float", example=45.00),
     *                 @OA\Property(property="service_pic", type="string", example="https://example.com/service.jpg"),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="area_name", type="string", example="Beirut", description="Area name (only included when showing area-specific pricing)"),
     *                 @OA\Property(property="translation", type="object", description="Translation info (only included when translation exists)",
     *                     @OA\Property(property="locale", type="string", example="ar"),
     *                     @OA\Property(property="name", type="string", example="رعاية التمريض المنزلية")
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function index()
    {
        $user = Auth::user()->fresh();
        $userAreaId = $user->area_id;
        $locale = app()->getLocale();

        $services = Service::with(['areaPrices' => function ($query) use ($userAreaId) {
            if ($userAreaId) {
                $query->where('area_id', $userAreaId);
            }
        }, 'translations'])->get();

        $services = $this->serviceTranslationService->getServicesWithPricingAndTranslations(
            $services, 
            $userAreaId, 
            $locale
        );

        return response()->json(['services' => $services], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/services",
     *     summary="Create a new service (Admin only)",
     *     description="Create a new service. Only accessible by admins.",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","price","category_id"},
     *             @OA\Property(property="name", type="string", example="Home Nursing", description="Service name"),
     *             @OA\Property(property="description", type="string", example="Professional nursing care at home", description="Service description"),
     *             @OA\Property(property="price", type="number", format="float", example=50.00, description="Service price"),
     *             @OA\Property(property="discount_price", type="number", format="float", example=45.00, description="Discounted price (must be less than regular price)"),
     *             @OA\Property(property="service_pic", type="string", format="url", example="https://example.com/service.jpg", description="URL to service picture (stored as URL string, not file upload)"),
     *             @OA\Property(property="category_id", type="integer", example=1, description="Category ID (REQUIRED - must be valid category)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Service created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service created successfully."),
     *             @OA\Property(property="service", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Home Nursing"),
     *                 @OA\Property(property="description", type="string", example="Professional nursing care at home"),
     *                 @OA\Property(property="price", type="number", format="float", example=50.00),
     *                 @OA\Property(property="discount_price", type="number", format="float", example=45.00),
     *                 @OA\Property(property="service_pic", type="string", example="https://example.com/service.jpg"),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin role required"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function store(Request $request)
    {
        $this->authorize('create', Service::class);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'image' => 'nullable|image|max:2048', // 2MB max
            'category_id' => 'required|exists:categories,id',
            // Translation fields
            'description' => 'nullable|string',
            'details' => 'nullable|string',
            'instructions' => 'nullable|string',
            'service_includes' => 'nullable|string',
            'locale' => 'required|string|in:en,ar',
        ]);

        // Upload image if provided
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->uploadImage($request->file('image'), 'services');
        }

        // Create service
        $service = Service::create([
            'name' => $validatedData['name'],
            'price' => $validatedData['price'],
            'discount_price' => $validatedData['discount_price'] ?? null,
            'category_id' => $validatedData['category_id'],
            'image' => $imagePath,
        ]);

        // Create translation
        if ($request->has('locale')) {
            $service->translations()->create([
                'locale' => $request->locale,
                'name' => $validatedData['name'],
                'description' => $request->description ?? null,
                'details' => $request->details ?? null,
                'instructions' => $request->instructions ?? null,
                'service_includes' => $request->service_includes ?? null,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Service created successfully.',
            'data' => [
                'id' => $service->id,
                'name' => $service->name,
                'image' => $service->image_url,
                'price' => $service->price,
            ],
        ], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/services/{id}",
     *     summary="Get a specific service with area-based pricing and translations",
     *     description="Retrieve a specific service with pricing based on user's area and content translated based on Accept-Language header.",
     *     tags={"Services"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Service ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="Accept-Language",
     *         in="header",
     *         description="Language preference (en, ar) - affects service names",
     *         required=false,
     *         @OA\Schema(type="string", example="ar")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="service", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="رعاية التمريض المنزلية"),
     *                 @OA\Property(property="description", type="string", example="Professional nursing care at home"),
     *                 @OA\Property(property="price", type="number", format="float", example=120.00),
     *                 @OA\Property(property="discount_price", type="number", format="float", example=45.00),
     *                 @OA\Property(property="service_pic", type="string", example="https://example.com/service.jpg"),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="area_name", type="string", example="Beirut", description="Area name (only included when showing area-specific pricing)"),
     *                 @OA\Property(property="translation", type="object", description="Translation info (only included when translation exists)",
     *                     @OA\Property(property="locale", type="string", example="ar"),
     *                     @OA\Property(property="name", type="string", example="رعاية التمريض المنزلية")
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service not found"
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function show($id)
    {
        $user = Auth::user()->fresh();
        $userAreaId = $user->area_id;
        $locale = app()->getLocale();

        $service = Service::with(['areaPrices' => function ($query) use ($userAreaId) {
            if ($userAreaId) {
                $query->where('area_id', $userAreaId);
            }
        }, 'translations'])->findOrFail($id);

        $service = $this->serviceTranslationService->getServiceWithPricingAndTranslations(
            $service, 
            $userAreaId, 
            $locale
        );

        return response()->json(['service' => $service], 200);
    }

    /**
     * @OA\Put(
     *     path="/api/admin/services/{id}",
     *     summary="Update service details (Admin only)",
     *     description="Update a service's information. Only accessible by admins.",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Service ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             @OA\Property(property="name", type="string", example="Home Nursing", description="Service name"),
     *             @OA\Property(property="description", type="string", example="Professional nursing care at home", description="Service description"),
     *             @OA\Property(property="price", type="number", format="float", example=50.00, description="Service price"),
     *             @OA\Property(property="discount_price", type="number", format="float", example=45.00, description="Discounted price (must be less than regular price)"),
     *             @OA\Property(property="service_pic", type="string", format="url", example="https://example.com/service.jpg", description="URL to service picture (stored as URL string, not file upload)"),
     *             @OA\Property(property="category_id", type="integer", example=1, description="Category ID (must be valid category if provided)")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service updated successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin role required"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service not found"
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function update(Request $request, Service $service)
    {
        // Ensure the correct policy is called with the proper arguments
        $this->authorize('update', $service);
    
        $validatedData = $request->validate([
            'name' => 'sometimes|string|max:255',
            'price' => 'sometimes|numeric|min:0',
            'discount_price' => 'sometimes|nullable|numeric|min:0|lt:price',
            'image' => 'nullable|image|max:2048',
            'category_id' => 'sometimes|exists:categories,id',
            // Translation fields
            'description' => 'nullable|string',
            'details' => 'nullable|string',
            'instructions' => 'nullable|string',
            'service_includes' => 'nullable|string',
            'locale' => 'required|string|in:en,ar',
        ]);

        // Update image if provided
        if ($request->hasFile('image')) {
            $imagePath = $this->imageStorageService->updateImage(
                $request->file('image'),
                $service->image,
                'services'
            );
            $service->image = $imagePath;
        }

        // Update service fields
        $updateData = array_filter([
            'name' => $validatedData['name'] ?? null,
            'price' => $validatedData['price'] ?? null,
            'discount_price' => $validatedData['discount_price'] ?? null,
            'category_id' => $validatedData['category_id'] ?? null,
        ], fn($value) => $value !== null);

        if (!empty($updateData)) {
            $service->update($updateData);
        }

        // Update or create translation
        if ($request->has('locale')) {
            $service->translations()->updateOrCreate(
                ['locale' => $request->locale],
                [
                    'name' => $validatedData['name'] ?? $service->name,
                    'description' => $request->description ?? null,
                    'details' => $request->details ?? null,
                    'instructions' => $request->instructions ?? null,
                    'service_includes' => $request->service_includes ?? null,
                ]
            );
        }
    
        return response()->json([
            'success' => true,
            'message' => 'Service updated successfully.',
            'data' => [
                'id' => $service->id,
                'name' => $service->name,
                'image' => $service->image_url,
                'price' => $service->price,
            ],
        ], 200);
    }
    
    /**
     * @OA\Get(
     *     path="/api/services/quote",
     *     summary="Get pricing quote for services in a specific area",
     *     description="Get pricing information for multiple services in a specific area. This allows users to preview pricing before creating a request.",
     *     tags={"Services"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="service_ids",
     *         in="query",
     *         required=true,
     *         description="Array of service IDs",
     *         @OA\Schema(type="array", @OA\Items(type="integer"), example={1,2})
     *     ),
     *     @OA\Parameter(
     *         name="area_id",
     *         in="query",
     *         required=true,
     *         description="Area ID for pricing",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Quote retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="area", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Beirut")
     *             ),
     *             @OA\Property(property="services", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Home Nursing"),
     *                 @OA\Property(property="price", type="number", format="float", example=100.00),
     *                 @OA\Property(property="area_price", type="number", format="float", example=100.00)
     *             )),
     *             @OA\Property(property="total_price", type="number", format="float", example=200.00),
     *             @OA\Property(property="currency", type="string", example="USD")
     *         )
     *     ),
     *     @OA\Response(
     *         response=422,
     *         description="Validation error",
     *         @OA\JsonContent(
     *             @OA\Property(property="errors", type="object")
     *         )
     *     )
     * )
     */
    public function quote(Request $request)
    {
        $validated = $request->validate([
            'service_ids' => 'required|array|min:1',
            'service_ids.*' => 'exists:services,id',
            'area_id' => 'required|exists:areas,id',
        ]);

        $serviceIds = $validated['service_ids'];
        $areaId = $validated['area_id'];

        // Get the area
        $area = \App\Models\Area::findOrFail($areaId);

        // Get services with area pricing
        $services = Service::whereIn('id', $serviceIds)->get();
        $areaPrices = \App\Models\ServiceAreaPrice::whereIn('service_id', $serviceIds)
            ->where('area_id', $areaId)
            ->get()
            ->keyBy('service_id');

        $totalPrice = 0;
        $servicesWithPricing = [];

        foreach ($services as $service) {
            $areaPrice = $areaPrices->get($service->id);
            
            if (!$areaPrice) {
                return response()->json([
                    'errors' => [
                        'area_id' => ['The selected area does not have pricing configured for all requested services.']
                    ]
                ], 422);
            }

            $servicesWithPricing[] = [
                'id' => $service->id,
                'name' => $service->name,
                'price' => $areaPrice->price,
                'area_price' => $areaPrice->price,
            ];

            $totalPrice += $areaPrice->price;
        }

        return response()->json([
            'area' => [
                'id' => $area->id,
                'name' => $area->name,
            ],
            'services' => $servicesWithPricing,
            'total_price' => $totalPrice,
            'currency' => 'USD', // You can make this configurable
        ]);
    }

    /**
     * @OA\Get(
     *     path="/api/services/area/{area_id}",
     *     summary="Get all services for a specific area with pricing and translations",
     *     description="Retrieve all services available in a specific area with area-specific pricing when available, fallback to base prices when area pricing doesn't exist. Content is translated based on Accept-Language header.",
     *     tags={"Services"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="area_id",
     *         in="path",
     *         description="Area ID",
     *         required=true,
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Parameter(
     *         name="Accept-Language",
     *         in="header",
     *         description="Language preference (en, ar) - affects service names",
     *         required=false,
     *         @OA\Schema(type="string", example="ar")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Services for area retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="area", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Beirut")
     *             ),
     *             @OA\Property(property="services", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="رعاية التمريض المنزلية"),
     *                 @OA\Property(property="description", type="string", example="Professional nursing care at home"),
     *                 @OA\Property(property="price", type="number", format="float", example=120.00, description="Area-specific price if available, otherwise base price"),
     *                 @OA\Property(property="discount_price", type="number", format="float", example=45.00),
     *                 @OA\Property(property="service_pic", type="string", example="https://example.com/service.jpg"),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="category", type="object",
     *                     @OA\Property(property="id", type="integer", example=1),
     *                     @OA\Property(property="name", type="string", example="Home Care")
     *                 ),
     *                 @OA\Property(property="area_name", type="string", example="Beirut", description="Area name (only included when showing area-specific pricing)"),
     *                 @OA\Property(property="has_area_pricing", type="boolean", example=true, description="Whether this service has area-specific pricing"),
     *                 @OA\Property(property="translation", type="object", description="Translation info (only included when translation exists)",
     *                     @OA\Property(property="locale", type="string", example="ar"),
     *                     @OA\Property(property="name", type="string", example="رعاية التمريض المنزلية")
     *                 ),
     *                 @OA\Property(property="created_at", type="string", format="date-time"),
     *                 @OA\Property(property="updated_at", type="string", format="date-time")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Area not found",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Area not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     )
     * )
     */
    public function getServicesByArea(int $areaId)
    {
        // Verify area exists
        $area = \App\Models\Area::find($areaId);
        if (!$area) {
            return response()->json(['message' => 'Area not found'], 404);
        }

        $locale = app()->getLocale();
        $services = $this->serviceTranslationService->getServicesByArea($areaId, $locale);

        return response()->json([
            'area' => [
                'id' => $area->id,
                'name' => $area->name,
            ],
            'services' => $services
        ]);
    }

    /**
     * @OA\Delete(
     *     path="/api/admin/services/{id}",
     *     summary="Delete a service (Admin only)",
     *     description="Delete a service from the system. Only accessible by admins.",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Service ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Service deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Unauthorized"
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Forbidden - Admin role required"
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Service not found"
     *     )
     * )
     */
    public function destroy(Service $service)
    {
        $this->authorize('delete', $service);
    
        // Delete image if exists
        if ($service->image) {
            $this->imageStorageService->deleteImage($service->image);
        }
    
        // Perform a hard delete
        $service->delete();
    
        return response()->json([
            'success' => true,
            'message' => 'Service deleted successfully.',
        ], 200);
    }
}