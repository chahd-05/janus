<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Habits;
use Illuminate\Http\Request;

class HabitController extends Controller
{
        public function index()
    {
        return CategoryResource::collection(Habits::all());
    }

    public function show(Habits $habit){
        return new CategoryResource($habit);
    }

    public function store(Request $request){
        $incomingFields = $request->validate([
            'title' => 'required',
            'description' => 'required',
            'frequency' => 'required',
            'target_day' => 'required',
            'is_active' => 'required',
            'color' => 'required'
        ]);
        $habit = Habits::create($incomingFields);
        return new CategoryResource($habit);
    }
    public function update(Request $request, Habits $habit){
        $incomingFields = $request->validate([
            'title' => 'required',
            'description' => 'required',
            'frequency' => 'required',
            'target_day' => 'required',
            'is_active' => 'required',
            'color' => 'required'
        ]);
        $habit->update($incomingFields);
        return new CategoryResource($habit);
    }
    public function destroy(Habits $habit){
        $habit->delete();
        return response()->noContent();
    }
}
