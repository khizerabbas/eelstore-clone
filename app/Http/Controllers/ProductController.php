<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\ProductSearch;
use App\Models\Category;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductController extends Controller
{
    public function index(Request $request, ?Category $currentCategory = null, ?Company $currentBrand = null)
    {
        // ---- Global price range (for slider/inputs) ----
        $minPriceRaw = Product::where('is_active', true)
            ->min(DB::raw(Product::effectivePriceSql()));

        $maxPriceRaw = Product::where('is_active', true)
            ->max(DB::raw(Product::effectivePriceSql()));

        $minPrice = (int) floor($minPriceRaw ?? 0);
        $maxPrice = (int) ceil($maxPriceRaw ?? 0);

        // ---- Read filters from request ----
        $selectedCategories = (array) $request->input('categories', []);
        $selectedCompanies  = (array) $request->input('companies', []);
        $onSale             = $request->boolean('on_sale', false);

        $currentMin = $request->input('min_price', $minPrice);
        $currentMax = $request->input('max_price', $maxPrice);

        $currentMin = is_numeric($currentMin) ? (float) $currentMin : $minPrice;
        $currentMax = is_numeric($currentMax) ? (float) $currentMax : $maxPrice;

        if ($currentMin > $currentMax) {
            [$currentMin, $currentMax] = [$currentMax, $currentMin];
        }

        $sort    = $request->input('sort', 'default');
        $perPage = (int) $request->input('per_page', 20);
        if (! in_array($perPage, [12, 20, 40, 60])) {
            $perPage = 20;
        }

        // ---- Determine which selected categories actually have those companies ----
        $categoriesWithSelectedCompanies = [];
        if (! empty($selectedCompanies)) {
            $categoriesWithSelectedCompanies = Product::where('is_active', true)
                ->whereIn('company_id', $selectedCompanies)
                ->pluck('category_id')
                ->unique()
                ->all();
        }

        // Intersect with user-selected categories
        $categoriesWithCompanies = [];
        $categoriesWithoutCompanies = [];

        if (! empty($selectedCategories)) {
            $categoriesWithCompanies = array_values(
                array_intersect($selectedCategories, $categoriesWithSelectedCompanies)
            );
            $categoriesWithoutCompanies = array_values(
                array_diff($selectedCategories, $categoriesWithCompanies)
            );
        }

        // ---- Base product query ----
        $query = Product::with(['category', 'company'])
            ->where('is_active', true);

        // Combined category + company logic:
        // - If both categories AND companies are selected:
        //   - For categories that have these companies: filter by company
        //   - For categories WITHOUT companies: include all products from those categories
        if (! empty($selectedCategories) && ! empty($selectedCompanies)) {
            $query->where(function ($q) use (
                $categoriesWithCompanies,
                $categoriesWithoutCompanies,
                $selectedCompanies
            ) {
                // Part 1: categories that have the selected companies
                if (! empty($categoriesWithCompanies)) {
                    $q->where(function ($q2) use ($categoriesWithCompanies, $selectedCompanies) {
                        $q2->whereIn('category_id', $categoriesWithCompanies)
                            ->whereIn('company_id', $selectedCompanies);
                    });
                }

                // Part 2: categories that don't have companies at all
                if (! empty($categoriesWithoutCompanies)) {
                    if (! empty($categoriesWithCompanies)) {
                        $q->orWhereIn('category_id', $categoriesWithoutCompanies);
                    } else {
                        // edge case: only categories without companies
                        $q->whereIn('category_id', $categoriesWithoutCompanies);
                    }
                }
            });
        }
        // Only categories selected (no companies)
        elseif (! empty($selectedCategories)) {
            $query->whereIn('category_id', $selectedCategories);
        }
        // Only companies selected (no categories)
        elseif (! empty($selectedCompanies)) {
            $query->whereIn('company_id', $selectedCompanies);
        }
        // else: no category/company filter

        // ---- Other filters ----
        // Same rule as Product::isOnSale(): a real discount, not just the "on sale" tick
        if ($onSale) {
            $query->where('is_on_sale', true)
                ->where('sale_price', '>', 0)
                ->whereColumn('sale_price', '<', 'price');
        }

        // CAST the bounds: SQLite binds PHP floats as text, which never compares equal/between numbers
        $query->whereRaw(
            '(' . Product::effectivePriceSql() . ') BETWEEN CAST(? AS DECIMAL(10,2)) AND CAST(? AS DECIMAL(10,2))',
            [$currentMin, $currentMax]
        );

        // ---- Sorting ----
        switch ($sort) {
            case 'latest':
                $query->orderBy('created_at', 'desc');
                break;

            case 'price_low_high':
                $query->orderByRaw(Product::effectivePriceSql() . ' asc');
                break;

            case 'price_high_low':
                $query->orderByRaw(Product::effectivePriceSql() . ' desc');
                break;

            default:
                $query->orderBy('name');
                break;
        }

        $products = $query
            ->paginate($perPage)
            ->withQueryString();

        // Page number past the end (old link, fewer products now) → last real page instead of an empty grid
        if ($products->isEmpty() && $products->currentPage() > 1 && $products->total() > 0) {
            return $this->redirectToPage($request, $products->lastPage());
        }

        // ---- Sidebar data: categories with counts ----
        $categories = Category::where('is_active', true)
            ->withCount(['products' => function ($q) {
                $q->where('is_active', true);
            }])
            ->orderBy('name')
            ->get();

        // ---- Sidebar data: companies nested under categories ----
        $companyCounts = Product::select(
            'category_id',
            'company_id',
            DB::raw('count(*) as products_count')
        )
            ->whereNotNull('company_id')
            ->where('is_active', true)
            ->groupBy('category_id', 'company_id')
            ->get();

        $companyIds = $companyCounts->pluck('company_id')->unique()->all();

        $companyNames = Company::whereIn('id', $companyIds)
            ->where('is_active', true)
            ->pluck('name', 'id');

        $categoryCompanies = [];

        foreach ($companyCounts as $row) {
            if (! $companyNames->has($row->company_id)) {
                continue;
            }

            $categoryCompanies[$row->category_id][] = [
                'id'    => $row->company_id,
                'name'  => $companyNames[$row->company_id],
                'count' => $row->products_count,
            ];
        }

        return view('shop.index', [
            'currentCategory'    => $currentCategory,
            'currentBrand'       => $currentBrand,
            'products'           => $products,
            'categories'         => $categories,
            'categoryCompanies'  => $categoryCompanies,
            'selectedCategories' => $selectedCategories,
            'selectedCompanies'  => $selectedCompanies,
            'onSale'             => $onSale,
            'minPrice'           => $minPrice,
            'maxPrice'           => $maxPrice,
            'currentMin'         => $currentMin,
            'currentMax'         => $currentMax,
            'sort'               => $sort,
            'perPage'            => $perPage,
        ]);
    }





    public function show(string $slug)
    {
        $product = Product::with(['category', 'company'])
            ->where('slug', $slug)
            ->where('is_active', true)
            ->firstOrFail();

        $related = Product::where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->where('is_active', true)
            ->take(4)
            ->get();

        return view('product.show', compact('product', 'related'));
    }

    public function search(Request $request)
    {
        $queryText   = trim($request->input('q', ''));
        $categoryId  = $request->input('category');

        // Category picked but no words typed → that category's page
        if ($queryText === '' && $categoryId && ($category = Category::where('is_active', true)->find($categoryId))) {
            return redirect()->route('category.show', $category->slug);
        }

        $products = collect();

        if ($queryText !== '' || $categoryId) {
            $productsQuery = Product::with(['category', 'company'])
                ->where('is_active', true);

            // Forgiving matching: "2.5mm" ↔ "2.5 mm", any word order, brand/category/SKU too (see ProductSearch)
            if ($queryText !== '') {
                ProductSearch::apply($productsQuery, $queryText);
            } else {
                $productsQuery->orderBy('name');
            }

            if (!empty($categoryId)) {
                $productsQuery->where('category_id', $categoryId);
            }

            $products = $productsQuery
                ->paginate(12)
                ->withQueryString();

            if ($products->isEmpty() && $products->currentPage() > 1 && $products->total() > 0) {
                return $this->redirectToPage($request, $products->lastPage());
            }
        }

        return view('search.results', [
            'query'      => $queryText,
            'products'   => $products,
            'categories' => Category::where('is_active', true)->whereNull('parent_id')->orderBy('sort_order')->orderBy('name')->get(['name', 'slug']),
        ]);
    }

    // Same URL with another page number. Built from the raw query string because category/brand pages
    // add their own filter to the request, which shouldn't show up in the address bar.
    private function redirectToPage(Request $request, int $page)
    {
        parse_str((string) $request->getQueryString(), $query);

        return redirect()->to($request->url() . '?' . http_build_query(array_merge($query, ['page' => $page])));
    }
}
