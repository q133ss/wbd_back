<?php

namespace App\Http\Controllers\WB;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Services\WBService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    private $service;

    public function __construct(WBService $service)
    {
        $this->service = $service;
    }

    public function fetchProduct(string $id)
    {
        return $this->service->fetchProduct($id);
    }

    public function addProduct(Request $request, string $id)
    {
        return $this->service->addProduct($request, $id);
    }

    public function getProduct(string $id)
    {
        return Product::findOrFail($id);
    }
}
