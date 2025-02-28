<?php

use App\Models\BillingModel\Subscription;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {

    dump(Subscription::first()->toArray());

    // $product = Product::first();
    // dd($product->toArray());
    
});
