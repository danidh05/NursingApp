<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // Import AuthorizesRequests

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
 */
class ServiceController extends Controller
{
    use AuthorizesRequests; // Use the AuthorizesRequests trait
    
    /**
     * @OA\Get(
     *     path="/api/services",
     *     summary="List all services with area-based pricing",
     *     description="Retrieve a list of all services with pricing based on user's area. If user has an area assigned and area-specific pricing exists, shows area price and area name. Otherwise shows original price without area information.",
     *     tags={"Services"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Services list retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="services", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Home Nursing"),
     *                 @OA\Property(property="description", type="string", example="Professional nursing care at home"),
     *                 @OA\Property(property="price", type="number", format="float", example=120.00, description="Area-specific price if user has area and pricing exists, otherwise original price"),
     *                 @OA\Property(property="discount_price", type="number", format="float", example=45.00),
     *                 @OA\Property(property="service_pic", type="string", example="https://example.com/service.jpg"),
     *                 @OA\Property(property="category_id", type="integer", example=1),
     *                 @OA\Property(property="area_name", type="string", example="Beirut", description="Area name (only included when showing area-specific pricing)"),
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

        $services = Service::with(['areaPrices' => function ($query) use ($userAreaId) {
            if ($userAreaId) {
                $query->where('area_id', $userAreaId);
            }
        }])->get();

        // Transform the response to include area-specific pricing
        $services->transform(function ($service) use ($userAreaId) {
            $areaPrice = $service->areaPrices->first();
            
            if ($userAreaId && $areaPrice) {
                // User has area and area pricing exists - show area-specific price
                $service->price = $areaPrice->price;
                $service->area_name = $areaPrice->area->name;
            } else {
                // User has no area or no area pricing exists - show original price
                $service->price = $service->getOriginal('price');
                // Don't include area_name when showing original price
            }
            
            // Remove the areaPrices relationship from the response
            unset($service->areaPrices);
            
            return $service;
        });

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
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:price',
            'service_pic'=>'nullable|string|url',
            'category_id' => 'required|exists:categories,id' // Assuming there's a category table
        ]);

        $service = Service::create($validatedData);

        return response()->json(['message' => 'Service created successfully.', 'service' => $service], 201);
    }

    /**
     * @OA\Get(
     *     path="/api/services/{id}",
     *     summary="Get service details",
     *     description="Retrieve details of a specific service. Available to both users and admins.",
     *     tags={"Services"},
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
     *         description="Service details retrieved successfully",
     *         @OA\JsonContent(
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
     *         response=404,
     *         description="Service not found"
     *     )
     * )
     */
    public function show($id)
    {
        $service = Service::findOrFail($id);
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
            'price' => 'sometimes|numeric',
            'description' => 'sometimes|nullable|string',
             'service_pic'=>'sometimes|nullable|string|url',
            'discount_price' => 'sometimes|nullable|numeric|min:0|lt:price',

           'category_id' => 'sometimes|exists:categories,id', // Include category_id validation if needed
        ]);
    
        $service->update($validatedData);
    
        return response()->json(['message' => 'Service updated successfully.'], 200);
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
    
        // Perform a hard delete
        $service->delete();
    
        return response()->json(['message' => 'Service deleted successfully.'], 200);
    }
}