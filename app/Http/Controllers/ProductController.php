<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantPrice;
use App\Models\Variant;
use Carbon\Carbon;
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
            $productsSearch->whereDate('created_at', Carbon::parse($date)->format('Y-m-d'));
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

        // Insert Variant Prices
        if (count($request->product_variant_prices)) {
            foreach ($request->product_variant_prices as $product_variant_price) {
                $title = substr($product_variant_price['title'], 0, -1);
                $titleArr = explode('/', $title);
                $product_variant_price1 = null;
                $product_variant_price2 = null;
                $product_variant_price3 = null;
                if (isset($titleArr[0])) {
                    $product_variant1 = ProductVariant::where('product_id', $product->id)->where('variant', $titleArr[0])->latest()->first();
                    if($product_variant1) {
                        $product_variant_price1 = $product_variant1->id;
                    }
                }
                if(isset($titleArr[1])){
                    $product_variant2 = ProductVariant::where('product_id', $product->id)->where('variant', $titleArr[1])->latest()->first();
                    if($product_variant2) {
                        $product_variant_price2 = $product_variant2->id;
                    }
                }
                if(isset($titleArr[2])){
                    $product_variant3 = ProductVariant::where('product_id', $product->id)->where('variant', $titleArr[2])->latest()->first();
                    if($product_variant3) {
                        $product_variant_price3 = $product_variant3->id;
                    }
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

        session()->flash('success', 'A new Product has added successfully !!');
        return redirect()->route('product.index');
//        return redirect()->back()->with('success', 'Product Saved');
    }


    /**
     * Display the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function show($product)
    {
        $product = Product::findOrfail($product);
        $product->load(['productImages']);
        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Product $product
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Http\Response|\Illuminate\View\View
     */
    public function edit(Product $product)
    {
        $variants = Variant::all();
        $product->load(['productVariants' => function($query) use ($product) {
            $query->distinct('variant');
        },'productImages:id,product_id,file_path', 'productVariantPrices.productVariantOne:id,variant', 'productVariantPrices.productVariantTwo:id,variant', 'productVariantPrices.productVariantThree:id,variant']);
//        return $product;
        return view('products.edit', compact('variants', 'product'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\Models\Product $product
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, Product $product)
    {
        $request->validate([
            'title' => 'required',
            'description' => 'nullable',
            'sku' => 'required|unique:products,sku,' . $product->id,
            'product_image' => 'nullable|array',
            'product_variant' => 'nullable|array',
            'product_variant_prices' => 'nullable|array'
        ]);

        //update product
        $productData = $request->only('title', 'description', 'sku');
        $product->update($productData);

        //update images
        if (count($request->product_image)) {
            $productImages = [];
            foreach ($request->product_image as $image) {
                $productImages[] = [
                    'product_id' => $product->id,
                    'file_path' => $image,
                ];
            }
            //delete old images
            $product->productImages()->delete();
            $product->productImages()->insert($productImages);
        }

        //update variants
        if (count($request->product_variant)) {
            //delete previous variants
            $product->productVariants()->delete();
            $productVariantsData = [];
            //prepared new data for inserting updated product variants
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
            //insert updated variants
            ProductVariant::insert($productVariantsData);
        }

        //update variant prices and delete old price variants
        if (count($request->product_variant_prices)) {
            $product->productVariantPrices()->delete();
            foreach ($request->product_variant_prices as $product_variant_price) {
                $title = substr($product_variant_price['title'], 0, -1);
                $titleArr = explode('/', $title);
                $product_variant_price1 = null;
                $product_variant_price2 = null;
                $product_variant_price3 = null;
                if (isset($titleArr[0])) {
                    $product_variant1 = ProductVariant::where('product_id', $product->id)->where('variant', $titleArr[0])->latest()->first();
                    if($product_variant1) {
                        $product_variant_price1 = $product_variant1->id;
                    }
                }
                if(isset($titleArr[1])){
                    $product_variant2 = ProductVariant::where('product_id', $product->id)->where('variant', $titleArr[1])->latest()->first();
                    if($product_variant2) {
                        $product_variant_price2 = $product_variant2->id;
                    }
                }
                if(isset($titleArr[2])){
                    $product_variant3 = ProductVariant::where('product_id', $product->id)->where('variant', $titleArr[2])->latest()->first();
                    if($product_variant3) {
                        $product_variant_price3 = $product_variant3->id;
                    }
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

        $product->load(['productVariants' => function($query) use ($product) {
            $query->distinct('variant');
        },'productImages:id,product_id,file_path', 'productVariantPrices.productVariantOne:id,variant', 'productVariantPrices.productVariantTwo:id,variant', 'productVariantPrices.productVariantThree:id,variant']);

        return response()->json(['status' => 'success', 'message' => 'Product Updated Successfully', 'data' => $product]);

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
