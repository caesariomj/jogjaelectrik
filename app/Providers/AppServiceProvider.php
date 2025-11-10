<?php

namespace App\Providers;

use App\Models\Category;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Password::defaults(function () {
            return Password::min(8)
                ->mixedCase()
                ->numbers();
        });

        $currentRoute = $this->app->request->getRequestUri();

        if (! $this->app->runningInConsole()) {
            if (! str_contains($currentRoute, 'admin')) {
                $primaryCategories = Category::queryPrimaryWithSubcategories(
                    columns: [
                        'categories.id',
                        'categories.name',
                        'categories.slug',
                    ])
                    ->orderBy('categories.name')
                    ->get()
                    ->groupBy(function ($category) {
                        return $category->id;
                    })
                    ->take(2)
                    ->map(function ($grouppedCategories) {
                        $category = $grouppedCategories->first();

                        $subcategories = $grouppedCategories->map(function ($subcategory) {
                            return (object) [
                                'id' => $subcategory->subcategory_id,
                                'name' => $subcategory->subcategory_name,
                                'slug' => $subcategory->subcategory_slug,
                            ];
                        });

                        return (object) [
                            'id' => $category->id,
                            'name' => $category->name,
                            'slug' => $category->slug,
                            'subcategories' => $subcategories->values(),
                        ];
                    })
                    ->values();

                View::share('primaryCategories', $primaryCategories);
            }
        }
    }
}
