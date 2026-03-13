<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class ProductController extends Controller
{
    public function index(){
        //I MEAN, I THINK 50 IS A GOOD NUMBER :D
        return response()->json(Product::paginate(50));
    }

    public function store(Request $request){
        $validated = $request->validate([
            'name'   => 'required|string|max:255',
            'amount' => 'required|integer|min:0',
        ]);

        Log::info('Creating product', ['name' => $validated['name']]);

        $product = Product::create($validated);

        Log::info('Product ' . $product->name . ' created', ['id' => $product->id]);
        return response()->json($product, 201);
    }

    public function show(Product $product){
        return response()->json($product);
    }

    public function update(Request $request, Product $product){
        $validated = $request->validate([
            'name'   => 'string|max:255',
            'amount' => 'integer|min:0',
        ]);

        $product->update($validated);
        Log::info('Product ' . $product->name . ' updated', ['id' => $product->id]);

        return response()->json($product);
    }

    public function destroy(Product $product){
        $product->delete();

        Log::info('Product ' . $product->name . ' deleted', ['id' => $product->id]);
        return response()->json(['message' => 'Product deleted successfully'], 204);
    }
}
