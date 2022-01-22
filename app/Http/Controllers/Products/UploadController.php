<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Jobs\UploadImage;
use App\Models\Product;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function upload(Request $request) {
        dump($request->header('project-name'));
        dd($request->header(''));
        // Validate the request
//        $this->validate($request, [
//            'image' => ['required', 'mimes:jpg,jpeg,gif,bmp,png', 'max:512']
//        ]);
//
        // Get the image
        $image = $request->file('image');
        $imagePath = $image->getPathname();

        //Get the original file name and replace any spaces with _
        $filename = time()."_".preg_replace('/\s+/', '_', strtolower($image->getClientOriginalName()));

        // Move the image to folder
        $image->storeAs('uploads/original', $filename, 'public');

        // Create database record for the product
        $product = Product::create([
            'image' => $filename,
            'disk' => config('site.upload_disk')
        ]);
//
//        // Dispatch a job to handle the image manipulation
//        $this->dispatch(new UploadImage($product));
//        return response()->json($product, 200);
    }
}
