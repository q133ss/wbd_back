<?php

namespace App\Http\Controllers\Front;

use App\Http\Controllers\Controller;
use App\Models\Ad;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $adsQuery = Ad::withFilter($request)->withSorting($request);
        $ads = $adsQuery->paginate(18);
        return response()->json($ads);
    }

    public function show(string $id)
    {
        return Ad::findOrFail($id);
    }

    public function related(string $id)
    {
        $ad = Ad::findOrFail($id);
        if($ad->product?->category_id == null)
        {
            $related = Ad::where('id', '!=', $id)->orderBy('created_at', 'desc')->take(6)->get();
        }else{
            $related = Ad::whereHas('product', function($query) use ($ad) {
                $query->where('category_id', $ad->product?->category_id);
            })
            ->where('id', '!=', $id)
            ->inRandomOrder()
            ->take(6)
            ->get();
        }
        return response()->json($related);
    }
}
