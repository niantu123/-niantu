<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RestaurantController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $cuisine = (string) $request->query('cuisine');

        $restaurants = Restaurant::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($sub) use ($search) {
                    $sub->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%")
                        ->orWhere('intro', 'like', "%{$search}%");
                });
            })
            ->when($cuisine !== '', fn ($query) => $query->where('cuisine', $cuisine))
            ->orderByDesc('rating')
            ->paginate(6)
            ->withQueryString();

        $cuisines = Restaurant::query()->distinct()->orderBy('cuisine')->pluck('cuisine');

        return view('restaurants.index', compact('restaurants', 'cuisines', 'search', 'cuisine'));
    }

    public function show(Restaurant $restaurant): View
    {
        $chefs = $restaurant->chefs()->get();
        $waiters = $restaurant->waiters()->get();
        $signatureDishes = $restaurant->signatureDishes()->get();
        $menu = $restaurant->menuItems()->get()->groupBy('category');
        $recipes = $restaurant->recipes()->orderBy('title')->get();
        $images = $restaurant->images()->get();
        $comments = $restaurant->comments()->with('user')->take(20)->get();
        $commentsAvg = $restaurant->comments()->avg('rating');

        return view('restaurants.show', compact(
            'restaurant', 'chefs', 'waiters', 'signatureDishes', 'menu', 'recipes', 'images', 'comments', 'commentsAvg'
        ));
    }
}
