<?php

namespace App\Http\Controllers\WB;

use App\Http\Controllers\Controller;
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
        $product = $this->service->fetchProduct($id);
        return response()->json($product);
    }

    public function addProduct(string $id)
    {
        return $this->service->addProduct($id);
    }
}
