<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Product;

class ProductController extends Controller
{
    public function index()
    {
        return Product::all();
    }
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


    public function show($id)
    {
        return Product::findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'code' => 'required|unique:products,code,' . $id,
            'name' => 'required',
            'brand' => 'required',
            'category' => 'required',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
        ]);

        $product->update($validated);
        return $product;
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->delete();

        return response()->json(['message' => 'Product deleted']);
    }
}

