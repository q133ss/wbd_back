<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Promocode;
use Illuminate\Http\Request;

class PromocodeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $promocodes = Promocode::query()
            ->when($request->get('search'), function ($query, $search) {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('promocode', 'like', "%{$search}%");
            })
            ->paginate(15);

        return view('admin.promocodes.index', compact('promocodes'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $tariffs = \App\Models\Tariff::where('is_hidden', false)->get();
        return view('admin.promocodes.create', compact('tariffs'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'promocode'  => ['required', 'string', 'max:255', 'unique:promocodes,promocode'],
            'start_date' => ['required', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
            'max_usage'  => ['nullable', 'integer', 'min:1'],
            'data'       => ['required', 'array'], // массив из ЧПУ формы
        ]);

        // Преобразуем data в JSON для хранения
        $validated['data'] = json_encode($validated['data'], JSON_UNESCAPED_UNICODE);

        Promocode::create($validated);

        return redirect()->route('admin.promocodes.index')
            ->with('success', 'Промокод успешно создан.');
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $promocode = Promocode::findOrFail($id);
        $tariffs = \App\Models\Tariff::where('is_hidden', false)->get();
        return view('admin.promocodes.edit', compact('promocode', 'tariffs'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Promocode $promocode)
    {
        $validated = $request->validate([
            'name'       => ['required', 'string', 'max:255'],
            'promocode'  => ['required', 'string', 'max:255', 'unique:promocodes,promocode,' . $promocode->id],
            'start_date' => ['required', 'date'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
            'max_usage'  => ['nullable', 'integer', 'min:1'],
            'data'       => ['required', 'array'],
        ]);

        // Преобразуем data в JSON
        $validated['data'] = json_encode($validated['data'], JSON_UNESCAPED_UNICODE);

        $promocode->update($validated);

        return redirect()->route('admin.promocodes.index')
            ->with('success', 'Промокод успешно обновлён.');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $promocode = Promocode::findOrFail($id);
        $promocode->delete();

        return redirect()->route('admin.promocodes.index')
            ->with('success', 'Промокод удален.');
    }
}
