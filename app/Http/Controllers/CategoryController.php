<?php


namespace App\Http\Controllers;

use App\Models\Category;
use App\Services\FirebaseStorageService;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    /**
     * @OA\Get(
     *     path="/api/categories",
     *     summary="List all categories",
     *     description="Retrieve a list of all categories. Available to both users and admins.",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(
     *         response=200,
     *         description="Categories list retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="categories", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Home Care"),
     *                 @OA\Property(property="image_url", type="string", example="https://firebasestorage.googleapis.com/v0/b/.../categories/image.jpg", nullable=true, description="Firebase Storage URL for category image"),
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
     *         description="Forbidden - Access denied"
     *     )
     * )
     */
    public function index()
    {
        $this->authorize('viewAny', Category::class);
        $categories = Category::all();
        return response()->json(['categories' => $categories], 200);
    }
    

    /**
     * @OA\Get(
     *     path="/api/categories/{id}",
     *     summary="Get category details",
     *     description="Retrieve details of a specific category. Available to both users and admins.",
     *     tags={"Categories"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Category ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category details retrieved successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="category", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Home Care"),
     *                 @OA\Property(property="image_url", type="string", example="https://firebasestorage.googleapis.com/v0/b/.../categories/image.jpg", nullable=true, description="Firebase Storage URL for category image"),
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
     *         description="Category not found"
     *     )
     * )
     */
    public function show($id)
    {
        $category = Category::findOrFail($id);
        return response()->json(['category' => $category], 200);
    }

    /**
     * @OA\Post(
     *     path="/api/admin/categories",
     *     summary="Create a new category (Admin only)",
     *     description="Create a new category with optional image. Only accessible by admins. If image upload fails, a 422 error is returned with a message.",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name"},
     *                 @OA\Property(property="name", type="string", example="Home Care", description="Category name"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Category image (optional, max 2MB)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Category created successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category created successfully."),
     *             @OA\Property(property="category", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Home Care"),
     *                 @OA\Property(property="image_url", type="string", example="https://firebasestorage.googleapis.com/v0/b/...", nullable=true),
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
     *         description="Validation error or failed image upload",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Failed to upload image: ..."),
     *             @OA\Property(property="errors", type="object", nullable=true)
     *         )
     *     )
     * )
     */
    public function store(Request $request, FirebaseStorageService $firebaseStorage)
    {
        $this->authorize('create', Category::class);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'image' => 'nullable|image|max:2048', // 2MB max
        ]);

        $categoryData = ['name' => $validatedData['name']];

        // Handle image upload if provided
        if ($request->hasFile('image')) {
            try {
                $imageUrl = $firebaseStorage->uploadFile($request->file('image'), 'category-images');
                $categoryData['image_url'] = $imageUrl;
            } catch (\Exception $e) {
                return response()->json([
                    'message' => 'Failed to upload image: ' . $e->getMessage()
                ], 422);
            }
        }

        $category = Category::create($categoryData);

        return response()->json([
            'message' => 'Category created successfully.',
            'category' => $category,
        ], 201);
    }
    
    
    

    /**
     * @OA\Put(
     *     path="/api/admin/categories/{id}",
     *     summary="Update category details (Admin only)",
     *     description="Update a category's information. Only accessible by admins.",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Category ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="multipart/form-data",
     *             @OA\Schema(
     *                 required={"name"},
     *                 @OA\Property(property="name", type="string", example="Home Care", description="Category name"),
     *                 @OA\Property(property="image", type="string", format="binary", description="Category image (optional, max 2MB)")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category updated successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category updated successfully."),
     *             @OA\Property(property="category", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Home Care"),
     *                 @OA\Property(property="image_url", type="string", example="https://firebasestorage.googleapis.com/v0/b/...", nullable=true),
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
     *         response=404,
     *         description="Category not found"
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
   public function update(Request $request, Category $category, FirebaseStorageService $firebaseStorage)
{
    // Ensure the update policy is being authorized
    $this->authorize('update', $category);

    // Validate request data
    $validatedData = $request->validate([
        'name' => 'required|string|max:255',
        'image' => 'nullable|image|max:2048', // 2MB max
    ]);

    $updateData = ['name' => $validatedData['name']];

    // Handle image upload if provided
    if ($request->hasFile('image')) {
        // Delete old image if exists
        if ($category->image_url) {
            $firebaseStorage->deleteFile($category->image_url);
        }
        
        $imageUrl = $firebaseStorage->uploadFile($request->file('image'), 'category-images');
        $updateData['image_url'] = $imageUrl;
    }

    // Update the category with validated data
    $category->update($updateData);

    return response()->json(['message' => 'Category updated successfully.', 'category' => $category], 200);
}


    /**
     * @OA\Delete(
     *     path="/api/admin/categories/{id}",
     *     summary="Delete a category (Admin only)",
     *     description="Delete a category from the system. Cannot delete if it has associated services. Only accessible by admins.",
     *     tags={"Admin"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="Category ID",
     *         @OA\Schema(type="integer", example=1)
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Category deleted successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Category deleted successfully.")
     *         )
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Cannot delete category with associated services",
     *         @OA\JsonContent(
     *             @OA\Property(property="message", type="string", example="Cannot delete category with associated services.")
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
     *         description="Category not found"
     *     )
     * )
     */
    public function destroy(Category $category, FirebaseStorageService $firebaseStorage)
    {
        $this->authorize('delete', $category); // Ensure only admins can delete

        if ($category->services()->count()) {
            return response()->json(['message' => 'Cannot delete category with associated services.'], 400);
        }

        // Delete image from Firebase if exists
        if ($category->image_url) {
            $firebaseStorage->deleteFile($category->image_url);
        }

        $category->delete();
        return response()->json(['message' => 'Category deleted successfully.'], 200);
    }
}