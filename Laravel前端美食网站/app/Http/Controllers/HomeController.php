<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\Restaurant;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(): View
    {
        $recipes = Recipe::withAvg('comments', 'rating')->withCount('comments')->latest('id')->limit(6)->get();
        $restaurants = Restaurant::orderByDesc('rating')->limit(3)->get();
        $categories = RecipeController::categoryOptions();
        $works = Recipe::works()->withAvg('comments', 'rating')->latest('id')->limit(3)->get();

        return view('home', compact('recipes', 'restaurants', 'categories', 'works'));
    }
}
