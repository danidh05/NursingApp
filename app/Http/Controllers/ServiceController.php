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
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/services/uuid_timestamp.jpg", description="Full URL to service image"),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="area_name", type="string", example="Beirut", description="Area name (only included when showing area-specific pricing)"),
     *                 @OA\Property(property="translation", type="object", description="Translation info (only included when translation exists)",
     *                     @OA\Property(property="locale", type="string", example="ar"),
     *                     @OA\Property(property="name", type="string", example="رعاية التمريض المنزلية"),
     *                     @OA\Property(property="description", type="string", example="رعاية تمريضية مهنية في المنزل"),
     *                     @OA\Property(property="details", type="string", example="خدمات تمريضية منزلية شاملة"),
     *                     @OA\Property(property="instructions", type="string", example="اتبع هذه التعليمات..."),
     *                     @OA\Property(property="service_includes", type="string", example="يشمل: الأدوية، المراقبة")
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
     *     description="Create a new service with image upload and translations. Use form-data (multipart/form-data) for file uploads. Only accessible by admins.",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name","price","category_id","locale"},
     *                 @OA\Property(property="name", type="string", example="Home Nursing Care", description="Service name"),
     *                 @OA\Property(property="price", type="number", format="float", example=50.00, description="Service price"),
     *                 @OA\Property(property="discount_price", type="number", format="float", example=45.00, description="Discounted price (must be less than regular price)"),
     *                 @OA\Property(property="category_id", type="integer", example=1, description="Category ID (REQUIRED)"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Service image file (jpg, png, webp, max 2MB)"),
     *                 @OA\Property(property="description", type="string", example="Professional nursing care at home", description="Translatable description"),
     *                 @OA\Property(property="details", type="string", example="Comprehensive home nursing services", description="Translatable details"),
     *                 @OA\Property(property="instructions", type="string", example="Follow these instructions...", description="Translatable instructions"),
     *                 @OA\Property(property="service_includes", type="string", example="Includes: medication, monitoring", description="Translatable service includes"),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en", description="Translation locale (REQUIRED)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Service created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service created successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Home Nursing Care"),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/services/uuid_timestamp.jpg"),
     *                 @OA\Property(property="price", type="string", example="50.00")
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
            'locale' => 'nullable|string|in:en,ar',
        ]);

        // Default locale to 'en' if not provided
        $locale = $validatedData['locale'] ?? 'en';

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
        $service->translations()->create([
            'locale' => $locale,
            'name' => $validatedData['name'],
            'description' => $request->description ?? null,
            'details' => $request->details ?? null,
            'instructions' => $request->instructions ?? null,
            'service_includes' => $request->service_includes ?? null,
        ]);

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
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/services/uuid_timestamp.jpg", description="Full URL to service image"),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="area_name", type="string", example="Beirut", description="Area name (only included when showing area-specific pricing)"),
     *                 @OA\Property(property="translation", type="object", description="Translation info (only included when translation exists)",
     *                     @OA\Property(property="locale", type="string", example="ar"),
     *                     @OA\Property(property="name", type="string", example="رعاية التمريض المنزلية"),
     *                     @OA\Property(property="description", type="string", example="رعاية تمريضية مهنية في المنزل"),
     *                     @OA\Property(property="details", type="string", example="خدمات تمريضية منزلية شاملة"),
     *                     @OA\Property(property="instructions", type="string", example="اتبع هذه التعليمات..."),
     *                     @OA\Property(property="service_includes", type="string", example="يشمل: الأدوية، المراقبة")
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
         *     description="Update a service's information with image upload and translations. **CRITICAL FOR FILE UPLOADS:** This endpoint accepts both PUT (for non-file updates) and POST with `_method=PUT` (for file uploads). When uploading files, you MUST: 1) Use POST method (not PUT), 2) Include `_method=PUT` in form-data, 3) Use multipart/form-data. This is Laravel's method spoofing - required because PHP only populates \$_FILES for POST requests. Only accessible by admins.",
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
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
         *             @OA\Schema(
         *                 @OA\Property(property="_method", type="string", example="PUT", description="**REQUIRED when using POST for file uploads:** Set this field to 'PUT' when using POST method. This enables Laravel method spoofing. Omit this field if using actual PUT request (without file uploads)."),
         *                 @OA\Property(property="name", type="string", example="Home Nursing Care - Updated", description="Service name"),
         *                 @OA\Property(property="price", type="number", format="float", example=55.00, description="Service price"),
         *                 @OA\Property(property="discount_price", type="number", format="float", example=50.00, description="Discounted price (must be less than regular price)"),
         *                 @OA\Property(property="category_id", type="integer", example=1, description="Category ID"),
         *                 @OA\Property(property="image", type="string", format="binary", description="Service image file (jpg, png, webp, max 2MB) - optional, updates image if provided"),
     *                 @OA\Property(property="description", type="string", example="Updated professional nursing care", description="Translatable description"),
     *                 @OA\Property(property="details", type="string", example="Updated comprehensive services", description="Translatable details"),
     *                 @OA\Property(property="instructions", type="string", example="Updated instructions", description="Translatable instructions"),
     *                 @OA\Property(property="service_includes", type="string", example="Updated includes", description="Translatable service includes"),
     *                 @OA\Property(property="locale", type="string", enum={"en","ar"}, example="en", description="Translation locale (optional, defaults to 'en' if not provided)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Service updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Service updated successfully."),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Home Nursing Care - Updated"),
     *                 @OA\Property(property="image", type="string", example="http://localhost:8000/storage/services/new_uuid_timestamp.jpg"),
     *                 @OA\Property(property="price", type="string", example="55.00")
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
    
        // Get locale from request, default to 'en' if not provided
        $locale = $request->input('locale');
        
        // Trim whitespace if it's a string
        if (is_string($locale)) {
            $locale = trim($locale);
        }
        
        // Default to 'en' if locale is not provided or invalid
        if (!$locale || !in_array($locale, ['en', 'ar'])) {
            $locale = 'en';
        }
    
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
        ]);
        
        // Add locale to validated data
        $validatedData['locale'] = $locale;

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
        $service->translations()->updateOrCreate(
            ['locale' => $locale],
            [
                'name' => $validatedData['name'] ?? $service->name,
                'description' => $request->description ?? null,
                'details' => $request->details ?? null,
                'instructions' => $request->instructions ?? null,
                'service_includes' => $request->service_includes ?? null,
            ]
        );
    
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