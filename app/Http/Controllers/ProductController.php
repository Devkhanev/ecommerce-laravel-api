<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use mysql_xdevapi\Exception;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        try {
            $product = Product::all();
            return response()->json([
                'message' => 'Products retrieved successfully',
                'products' => $product]);

        } catch (\Exception $e){
            return response()->json([
                'message' => 'Load failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $validated  = $request->validate([
                'name' => 'required|string|max:30',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|numeric|min:0',
            ]);

            $product = Product::create([
                'name' => $validated['name'],
                'description' => $validated['description'],
                'price' => $validated['price'],
                'stock' => $validated['stock'],
            ]);

            return response()->json([
                'message' => 'Products created successfully',
                'product' => $product,
            ], 201);
        } catch (\Exception $e){
            return response()->json([
                'message' => 'Create failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        try {

            $product = Product::find($id);

            if(!$product){
                return response()->json([
                    'message' => 'Product not found',
                ], 404);
            }

            return response()->json([
                'message' => 'Products retrieved successfully',
                'product' => $product,
            ], 200);

        } catch (\Exception $e){
            return response()->json([
                'message' => 'Failed to retrieve product',
                'error' => $e->getMessage(),
            ],500);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {

        try {
            $product = Product::find($id);
            if(!$product){
                return response()->json([
                    'message' => 'Product not found',
                ], 404);
            }

            $validated  = $request->validate([
                'name' => 'required|string',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'stock' => 'required|numeric|min:0',
            ]);

            $product->update($validated);

            return response()->json([
                'message' => 'Product updated successfully',
                'product' => $product,
            ], 200);
        } catch (\Exception $e){
            return response()->json([
                'message' => 'Update failed',
                'error' => $e->getMessage(),
            ], 500);
        }

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $product = Product::find($id);

            if(!$product){
                return response()->json([
                    'message' => 'Product not found',
                ], 404);
            }

            $product->delete();

            return response()->json([
                'message' => 'Product deleted successfully',
            ],200);
        } catch (\Exception $e){
            return response()->json([
                'message' => 'Delete failed',
                'error' => $e->getMessage(),
            ], 500);
        }

    }
}
