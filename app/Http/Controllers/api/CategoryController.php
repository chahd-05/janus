<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index()
    {
        return CategoryResource::collection(category::all());
    }

    public function show(category $category){
        return new CategoryResource($category);
    }

    public function store(Request $request){
        $incomingFields = $request->validate([
            'name' => 'required'
        ]);
        $category = category::create($incomingFields);
        return new CategoryResource($category);
    }
    public function update(Request $request, category $category){
        $incomingFields = $request->validate([
            'name' => 'required'
        ]);
        $category->update($incomingFields);
        return new CategoryResource($category);
    }
    public function destroy(category $category){
        $category->delete();
        return response()->noContent();
    }
}
