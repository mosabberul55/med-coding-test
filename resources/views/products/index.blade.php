@extends('layouts.app')

@section('content')

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Products</h1>
    </div>


    <div class="card">
        <form action="{!! route('searchProduct') !!}" method="get" class="card-header">
            <div class="form-row justify-content-between">
                <div class="col-md-2">
                    <input type="text" name="title" placeholder="Product Title" class="form-control">
                </div>
                <div class="col-md-2">
                    <select name="variant" id="" class="form-control">
                        <option value="" disabled selected hidden>--Select A Variant--</option>
                        @foreach($variants as $key => $variant)
                            <optgroup label="{{$key}}">
                                @foreach($variants[$key] as $value)
                                    <option value="{{$value}}">{{$value}}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text">Price Range</span>
                        </div>
                        <input type="text" name="price_from" aria-label="Price From" placeholder="From"
                               class="form-control">
                        <input type="text" name="price_to" aria-label="Price To" placeholder="To" class="form-control">
                    </div>
                </div>
                <div class="col-md-2">
                    <input type="date" name="date" placeholder="Date" class="form-control">
                </div>
                <div class="col-md-1">
                    <button type="submit" class="btn btn-primary float-right"><i class="fa fa-search"></i></button>
                </div>
            </div>
        </form>
        <div class="card-body">
            <div class="table-response">
                <table class="table">
                    <thead>
                    <tr>
                        <th>#</th>
                        <th>Title</th>
                        <th>Description</th>
                        <th>Variant</th>
                        <th width="150px">Action</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach($products as $key => $product)
                        <tr>
                            <td>{{ $product->id }}</td>
                            <td>{{ $product->title }} <br> Created at : {{$product->created_at}}</td>
                            <td><small>{{$product->description}}</small></td>
                            <td>
                                <dl class="row mb-0" style="height: 80px; overflow: hidden" id="variant">
                                    @foreach($product->productVariantPrices as $productVariant)
                                        <dt class="col-sm-3 pb-0">
                                            {{$productVariant->productVariantTwo ? $productVariant->productVariantTwo->variant : '' }}
                                            / {{$productVariant->productVariantOne ? $productVariant->productVariantOne->variant : '' }}
                                            {{$productVariant->productVariantThree ? '/ '. $productVariant->productVariantThree->variant : '' }}
                                        </dt>
                                        <dd class="col-sm-9">
                                            <dl class="row mb-0">
                                                <dt class="col-sm-4 pb-0">Price
                                                    : {{ number_format($productVariant->price,2) }}</dt>
                                                <dd class="col-sm-8 pb-0">InStock
                                                    : {{ number_format($productVariant->stock,2) }}</dd>
                                            </dl>
                                        </dd>
                                    @endforeach
                                </dl>
                                <button onclick="$('#variant').toggleClass('h-auto')" class="btn btn-sm btn-link">Show
                                    more
                                </button>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('product.edit', 1) }}" class="btn btn-success">Edit</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>

                </table>
            </div>

        </div>

        <div class="card-footer">
            <div class="row">
                @if($products->total() > 0)
                    <div class="cols-12 col-md-4">
                        <p>Showing {{$products->firstItem()}} to {{$products->lastItem()}} out of {{$products->total()}}</p>
                    </div>
                @else
                    <div class="col-md-4">
                        <p>No Product Found</p>
                    </div>
                @endif

                <div class="cols-12 col-md-8">
                    {{ $products->links()}}
                </div>
            </div>
        </div>
    </div>

@endsection
