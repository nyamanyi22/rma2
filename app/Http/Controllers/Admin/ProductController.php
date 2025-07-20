<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    // Get all products
   public function index()
{
    $products = Product::all();
    return response()->json($products);
}


    // Create new product
    public function store(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|unique:products,code',
            'name' => 'required|string',
            'brand' => 'nullable|string',
            'category' => 'nullable|string',
            'stock' => 'nullable|integer',
        ]);

        $product = Product::create($validated);

        return response()->json([
            'message' => 'Product created successfully.',
            'product' => $product,
        ], 201);
    }

    // Show single product by ID
    public function show($id)
    {
        return response()->json(Product::findOrFail($id));
    }

    // Update product
    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|string|unique:products,code,' . $id,
            'name' => 'required|string',
            'brand' => 'nullable|string',
            'category' => 'nullable|string',
            'stock' => 'nullable|integer',
        ]);

        $product->update($validated);

        return response()->json([
            'message' => 'Product updated successfully.',
            'product' => $product,
        ]);
    }

    // Delete product
    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }
}
