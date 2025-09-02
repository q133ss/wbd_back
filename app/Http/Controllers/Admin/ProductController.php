<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $products = Product::with('category')
            ->withFilter($request)
            ->paginate(20)
            ->appends($request->query()); // чтобы пагинация сохраняла фильтры

        return view('admin.products', compact('products'));
    }
}
