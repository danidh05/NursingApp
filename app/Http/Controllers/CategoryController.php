<?php


namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CategoryController extends Controller
{
    use AuthorizesRequests;

    // List all categories (Accessible by both Admins and Users)
    public function index()
    {
        $this->authorize('viewAny', Category::class);
        $categories = Category::all();
        return response()->json(['categories' => $categories], 200);
    }
    

    // Show a specific category (Accessible by both Admins and Users)
    public function show($id)
    {
        $category = Category::findOrFail($id);
        return response()->json(['category' => $category], 200);
    }

    // Store a new category (Admin only)
    public function store(Request $request)
    {
        $this->authorize('create', Category::class); // Ensure only admins can create

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        $category = Category::create($validatedData);

        return response()->json(['message' => 'Category created successfully.', 'category' => $category], 201);
    }

    // Update an existing category (Admin only)
   public function update(Request $request, Category $category)
{
    // Ensure the update policy is being authorized
    $this->authorize('update', $category);

    // Validate request data
    $validatedData = $request->validate([
        'name' => 'required|string|max:255', // Make sure this matches the expected structure
    ]);

    // Update the category with validated data
    $category->update($validatedData);

    return response()->json(['message' => 'Category updated successfully.'], 200);
}


    // Delete a category (Admin only)
    public function destroy(Category $category)
    {
        $this->authorize('delete', $category); // Ensure only admins can delete

        if ($category->services()->count()) {
            return response()->json(['message' => 'Cannot delete category with associated services.'], 400);
        }

        $category->delete();
        return response()->json(['message' => 'Category deleted successfully.'], 200);
    }
}