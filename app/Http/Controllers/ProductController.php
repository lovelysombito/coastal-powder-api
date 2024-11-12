<?php

namespace App\Http\Controllers;

use App\Events\JobEvent;
use App\Http\Requests\ProductRequest;
use App\Jobs\Exports\ExportProducts;
use App\Jobs\Imports\ImportProducts;
use App\Models\Products;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use App\Helpers\ResponseHelper;

class ProductController extends Controller
{
    public function getAllProducts(Request $request)
    {   
        Log::info("ProductController@getAllProducts", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            $products = Products::paginate(config('constant.pagination.product'));
            if ($products->total() > 0) {
            return ResponseHelper::responseMessage(config('constant.status_code.success'), $products, 'Product List');
            } else {
                return ResponseHelper::responseMessage(config('constant.status_code.success'), [], 'Product List');
            }
        } catch (Exception $e) {
            Log::error("ProductController@getAllProducts - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }
    }

    public function addProduct(Request $request)
    {
        Log::info("ProductController@addProduct", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        $request->validate([
            'product_name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|numeric'           
        ]);

        Log::info("ProductController@addProduct - Attempt to add ".$request->product_name, ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

        try {

            $name = $request->product_name;
            $description = $request->description;
            $price = $request->price;
            $file_link = $request->file_link;
            $brand = $request->brand;

            $product = Products::where('product_name', $name)->first();
            if ($product) {
                Log::warning("ProductController@addProduct - ".$name. ' already exists', ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
                return response()->json(['message'=>$name. ' already exists'], 400);
            }
                

            $newProduct = Products::create([
                'product_name' => $name,
                'description' => $description,
                'price' => $price,
                'file_link' => $file_link,
                'brand' => $brand
            ]);

            Log::info("ProductController@addProduct - ".$newProduct->product_id. ' successfully created', ["product" => $newProduct, "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

            event(new JobEvent('product'));
            return response()->json(['message' => 'Product successfully added']);
        } catch (Exception $e) {
            Log::error("ProductController@addProduct - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);
            $isDuplicated =  str_contains($e->getMessage(), 'Duplicate entry');
            return response()->json(['message' => $isDuplicated ? "Product name already exist" : "Something has gone wrong, please try again"], 400);
        }
    }

    public function deleteProduct(Request $request, $product_id)
    {
        try {
            Log::info("ProductController@deleteProduct - Attempt to delete ".$product_id, ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
            $product = Products::find($product_id);
            if (!$product) {
                Log::warning("ProductController@deleteProduct - ".$product_id . ' not found', ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
                return response()->json(['message' => 'Product not found'], 404);
            }

            $product->delete();

            event(new JobEvent('product'));
            Log::info("ProductController@deleteProduct - ".$product->product_id. ' successfully deleted', ["product" => $product, "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
            return response()->json(['message' => 'Product successfully deleted']);
        } catch (Exception $e) {
            Log::error("ProductController@deleteProduct - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }
    }

    public function updateProduct(Request $request, $product_id)
    {

        Log::info("ProductController@updateProduct", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        $request->validate([
            'product_name' => 'required|string',
            'description' => 'required|string',
            'price' => 'required|numeric'
        ]);

        $name = $request->product_name;
        $description = $request->description;
        $price = $request->price;
        $file_link = $request->file_link;
        $brand = $request->brand;

        Log::info("ProductController@updateProduct - Attempt to add ".$request->product_name, ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

        try {

            $product = Products::find($product_id);
            if (!$product) {
                Log::warning("ProductController@updateProduct - ".$product_id . ' not found', ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
                return response()->json(['message' => 'Product not found'], 404);
            }

            $product->update([
                'product_name' => $name,
                'description' => $description,
                'price' => $price,
                'file_link' => $file_link,
                'brand' => $brand
            ]);

            Log::info("ProductController@updateProduct - ".$product->product_id. ' successfully updated', ["product" => $product, "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);

            event(new JobEvent('product'));
            return response()->json(['message' => 'Product successfully updated']);
        } catch (Exception $e) {
            Log::error("ProductController@updateProduct - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);
            $isDuplicated =  str_contains($e->getMessage(), 'Duplicate entry');
            return response()->json(['message' => $isDuplicated ? "Product name already exist" : "Something has gone wrong, please try again"], 400);
        }
    }

    public function importProducts(Request $request) {

        Log::info("ProductController@importProducts", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {
            $validator = Validator::make($request->all(), [
                'file' => ['required', 'mimes:csv'],
            ]);

            if ($validator->fails()) {
                Log::warning("ProductController@importProducts - Validator failed, no file provided or invalid file type", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
                return response()->json(['message'=>'No file provided or invalid filetype'], 400);
            }

            if (!$request->hasFile('file')) {
                Log::warning("ProductController@importProducts - No file provided or invalid file type", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
                return response()->json(['message'=>'No file provided or invalid filetype'], 400);
            }

            $fileName = $request->file('file')->getClientOriginalName();

            $path = $request->file('file')->storeAs('/imports/products', $fileName, 's3');

            ImportProducts::dispatch($path, $request->user());

            return response()->json(['message'=>'File uploaded successfully, you will recieve email confirmation when the import has been completed'], 200);

            
        } catch (Exception $e) {
            $fileName = $request->file('file')->getClientOriginalName();

            $request->user()->sendProductImportFailure($e->getMessage(),true);

            Log::error("ProductController@importProducts - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }
        
    }

    public function exportProducts(Request $request) {

        Log::info("ProductController@exportProducts", ["req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id]]);
        try {

            ExportProducts::dispatch($request->user());

            return response()->json(['message'=>'Your export has been queued, you will recieve an email once the export has been completed'], 200);

            
        } catch (Exception $e) {
            Log::error("ProductController@exportProducts - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }

        
    }

    public function productSearch(Request $request) {
        try {
            $products = Products::search($request->search)->paginate(config('constant.pagination.product'));
            return ResponseHelper::responseMessage(config('constant.status_code.success'), $products, 'Product List');
        } catch (Exception $e) {
            Log::error("ProductController@exportProducts - Something has gone wrong: ".$e->getMessage(), [
                "error" => ['message'=>$e->getMessage(), 'code'=>$e->getCode(), 'file'=>$e->getFile(), 'line'=>$e->getLine()],
                "req"=>['ip' => $request->ip(), 'user'=>$request->user()->user_id],
            ]);
            \Sentry\captureException($e);
            return response()->json(['message' => "Something has gone wrong, please try again"], 400);
        }
    }
}
