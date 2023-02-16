<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
//            return $query->select('variant')->distinct('variant');
//        }])->orderBy('title')->distinct()->get()->groupBy('title');
//        return response()->json($variants);

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

    public function upload(Request $request): \Illuminate\Http\JsonResponse
    {
        $file = $request->file('file');
        $name = time() . '.' . $file->getClientOriginalExtension();
        $destinationPath = public_path('/images');
        $file->move($destinationPath, $name);

        return response()->json(['filename' => $name]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\RedirectResponse
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'nullable',
            'sku' => 'required|unique:products,sku',
            'product_image' => 'nullable|array',
            'product_variant' => 'nullable|array',
            'product_variant_prices' => 'nullable|array'
        ]);

        //insert product
        $productData = $request->only('title', 'description', 'sku');
        $product = Product::create($productData);

        // Insert Images
        if (count($request->product_image)) {
            $productImages = [];
            foreach ($request->product_image as $image) {
                $productImages[] = [
                    'product_id' => $product->id,
                    'file_path' => $image,
                ];
            }
            $product->productImages()->insert($productImages);
        }
        // Insert Variants
        if (count($request->product_variant)) {
            $productVariantsData = [];
            foreach ($request->product_variant as $variant) {
                if ($variant['option'] && count($variant['tags'])) {
                    foreach ($variant['tags'] as $tags) {
                        $productVariantsData[] = [
                            'product_id' => $product->id,
                            'variant' => $tags,
                            'variant_id' => $variant['option'],
                            'created_at' => now(),
                            'updated_at' => now()
                        ];
                    }
                }
            }
            ProductVariant::insert($productVariantsData);
        }

        if (count($request->product_variant_prices)) {
            foreach ($request->product_variant_prices as $product_variant_price) {
                $title = substr($product_variant_price['title'], 0, -1);
                $titleArr = explode('/', $title);
                $product_variant_price1 = null;
                $product_variant_price2 = null;
                $product_variant_price3 = null;
                if (isset($titleArr[0])) {
                    $product_variant_price1 = ProductVariant::where('product_id', $product->id)->where('variant', $titleArr[0])->latest()->first()->id;
                }
                if(isset($titleArr[1])){
                    $product_variant_price2 = ProductVariant::where('product_id', $product->id)->where('variant', $titleArr[1])->latest()->first()->id;
                }
                if(isset($titleArr[2])){
                    $product_variant_price3 = ProductVariant::where('product_id', $product->id)->where('variant', $titleArr[2])->latest()->first()->id;
                }
                ProductVariantPrice::create([
                    'product_variant_one' => $product_variant_price1,
                    'product_variant_two' => $product_variant_price2,
                    'product_variant_three' => $product_variant_price3,
                    'price' => $product_variant_price['price'],
                    'stock' => $product_variant_price['stock'],
                    'product_id' => $product->id
                ]);
            }
        }

        return redirect()->back()->with('success', 'Product Saved');
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
