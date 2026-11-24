<?php

namespace Tests\Feature;

use App\Models\Recipe;
use App\Models\Supplier;
use App\Models\SupplierProduct;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IngredientPageTest extends TestCase
{
    use RefreshDatabase;

    private function makeRecipe(string $title, array $ingredients): Recipe
    {
        $recipe = Recipe::create([
            'title' => $title,
            'category' => '家常菜',
            'cover' => '/images/recipes/default-cover.svg',
            'intro' => '测试用的菜谱介绍，写得足够长一点。',
            'prep_minutes' => 10,
            'cook_minutes' => 20,
            'difficulty' => '简单',
            'servings' => 2,
            'author' => '食光编辑',
        ]);

        foreach ($ingredients as $i => [$name, $amount]) {
            $recipe->ingredients()->create([
                'name' => $name,
                'amount' => $amount,
                'sort' => $i,
            ]);
        }

        return $recipe;
    }

    public function test_ingredient_page_lists_recipes_that_use_it(): void
    {
        $this->makeRecipe('五花肉炒蒜苗', [['五花肉', '300g'], ['蒜苗', '200g']]);
        $this->makeRecipe('梅菜扣肉', [['五花肉', '500g'], ['梅干菜', '80g']]);
        $this->makeRecipe('番茄炒蛋', [['番茄', '2 个'], ['鸡蛋', '3 个']]);

        $this->get('/ingredients/五花肉')
            ->assertOk()
            ->assertSee('五花肉炒蒜苗')
            ->assertSee('梅菜扣肉')
            ->assertDontSee('番茄炒蛋')
            ->assertSee('300g');
    }

    public function test_ingredient_page_shows_products_sold_in_supplier_shops(): void
    {
        $this->makeRecipe('五花肉炒蒜苗', [['五花肉', '300g']]);

        $supplier = Supplier::create([
            'name' => '老周蔬菜铺',
            'city' => '上海',
            'district' => '徐汇区',
            'address' => '永康路菜市场 12 号摊位',
            'hours' => '05:30–19:00',
            'category' => '肉禽',
            'rating' => 4.8,
            'cover' => '/images/recipes/default-cover.svg',
            'intro' => '测试用的食材店介绍，够十个字了。',
        ]);

        SupplierProduct::create([
            'supplier_id' => $supplier->id,
            'name' => '土猪五花肉',
            'category' => '肉禽',
            'price' => 38,
            'unit' => '500g',
        ]);

        $this->get('/ingredients/五花肉')
            ->assertOk()
            ->assertSee('土猪五花肉')
            ->assertSee('老周蔬菜铺');
    }

    public function test_unknown_ingredient_shows_empty_state_instead_of_error(): void
    {
        $this->get('/ingredients/人参果')
            ->assertOk()
            ->assertSee('站里还没有用到「人参果」的菜谱');
    }

    public function test_ingredient_tile_links_to_its_own_page(): void
    {
        $this->makeRecipe('五花肉炒蒜苗', [['五花肉', '300g']]);

        $this->get('/ingredients')
            ->assertOk()
            ->assertSee(route('ingredients.show', ['name' => '五花肉']), false);
    }

    public function test_recipe_search_matches_ingredient_names(): void
    {
        $this->makeRecipe('家常小炒', [['五花肉', '300g'], ['青椒', '2 个']]);

        $this->get('/recipes?q=五花肉')
            ->assertOk()
            ->assertSee('家常小炒');
    }
}
