<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function index()
    {
        //i don't know why this is not working
//        $variants = Variant::with(['productVariants' => function ($query) {
//            return $query->select('variant')->groupBy('variant');
//        }])->orderBy('title')->distinct()->get()->groupBy('title');

        //that's why i used this
        $variants = Variant::with(['productVariants'])->orderBy('title')->distinct()->get()->groupBy('title');
        $variants = $variants->map(function ($item) {
            return $item->map(function ($item) {
                return $item->productVariants->unique('variant')->pluck('variant');
            });
        })->map(function ($item) {
            return $item->flatten();
        });
        $products = Product::with('productVariantPrices.productVariantOne:id,variant', 'productVariantPrices.productVariantTwo:id,variant', 'productVariantPrices.productVariantThree:id,variant')->paginate(2);
        return view('products.index', compact('products', 'variants'));
    }

    public function search()
    {
        $title = request('title');
        $variant = request('variant');
        $price_from = request('price_from');
        $price_to = request('price_to');
        $date = request('date');

        $productsSearch = Product::query();
        if ($title) {
            $productsSearch->where('title', 'like', '%' . $title . '%');
        }
        if ($variant) {
            $productsSearch->whereHas('productVariants', function ($query) use ($variant) {
                $query->where('variant', $variant);
            });
        }
        if ($price_from) {
            $productsSearch->whereHas('productVariantPrices', function ($query) use ($price_from) {
                $query->where('price', '>=', $price_from);
            });
        }
        if ($price_to) {
            $productsSearch->whereHas('productVariantPrices', function ($query) use ($price_to) {
                $query->where('price', '<=', $price_to);
            });
        }
        if ($date) {
            $productsSearch->whereHas('productVariantPrices', function ($query) use ($date) {
                $query->where('date', $date);
            });
        }
        $products = $productsSearch->paginate(2);
//        return $products;
        $variants = Variant::with(['productVariants'])->orderBy('title')->distinct()->get()->groupBy('title');
        $variants = $variants->map(function ($item) {
            return $item->map(function ($item) {
                return $item->productVariants->unique('variant')->pluck('variant');
            });
        })->map(function ($item) {
            return $item->flatten();
        });
        return view('products.index', compact('products', 'variants'));

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function create()
    {
        $variants = Variant::all();
        return view('products.create', compact('variants'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {

    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function show($product)
    {

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function edit(Product $product)
    {
        $variants = Variant::all();
        return view('products.edit', compact('variants'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Product $product)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\Response
     */
    public function destroy(Product $product)
    {
        //
    }
}
