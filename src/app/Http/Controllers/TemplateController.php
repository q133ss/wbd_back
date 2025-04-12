<?php

namespace App\Http\Controllers;

use App\Http\Requests\TemplateController\TemplateRequest;
use App\Models\User;
use Illuminate\Http\Request;

class TemplateController extends Controller
{
    private User|null $user;


    public function __construct()
    {
        $this->user = auth('sanctum')->user();
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json($this->user?->templates);
    }
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $type)
    {
        return response()->json($this->user?->templates?->where('type', $type)->first());
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(TemplateRequest $request, string $type)
    {
        $template = $this->user?->templates?->where('type', $type)->first();

        $updated = $template->update([
            'text' => $request->text
        ]);
        return response()->json($template);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
