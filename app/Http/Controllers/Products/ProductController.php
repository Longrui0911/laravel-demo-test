<?php

namespace App\Http\Controllers\Products;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RoleAdmin;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Models\Project;
use App\Traits\ApiService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ProductController extends Controller
{

    use ApiService;

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['index', 'detail', 'show']]);
        $this->middleware(RoleAdmin::class, ['except' => ['index', 'detail', 'show']]);
    }

    public function getProjectDefault(Request $request) {
        return Project::where('name', $request->header('project-name'))->first();
    }

    /**
     * Display a listing of the resource.
     *
//     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $products = Product::where('project_id', $this->getProjectDefault($request)->id);
        if ($request->title) {
            $products = $products->where('title', 'like', '%'.$request->title.'%');
        }
        if ($request->description) {
            $products = $products->where('description', 'like', '%'.$request->description.'%');
        }
        if ($request->category_id) {
            $categoryId = $request->category_id;
            $products = $products->whereHas('category', function ($query) use ($categoryId) {
                $query->where('id', $categoryId);
            });
        }
        $products = $products->paginate($request->limit ?: 10);
        return new ProductCollection($products);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request)
    {
        $project = $this->getProjectDefault($request);
        $this->validate($request, [
            'title' => ['required', 'string', Rule::unique('products')->where(function ($query) use ($project) {
                return $query->where('project_id', '=', $project->id);
            })],
            'description' => ['string'],
            'image' => ['mimes:jpg,jpeg,gif,bmp,png', 'max:300']
        ]);

        $user = auth()->user();
        if ($project->user_id !== $user->id) {
            return $this->responseJson(['message' => 'Your actions are not allowed!'], 401);
        }

        $productsCount = $project->products->count();
        if ($productsCount > 50) {
            return $this->responseJson(['message' => 'You have created up to 50 products!'], 400);
        }

        DB::beginTransaction();
        try {
            $requestData = $request->all();

            if ($request->hasFile('image')) {
                // Get the image
                $image = $request->file('image');
                $imagePath = $image->getPathname();
                //Get the original file name and replace any spaces with _
                $filename = time()."_".preg_replace('/\s+/', '_', strtolower($image->getClientOriginalName()));
                // Move the image to folder
                $image->storeAs('uploads/original', $filename, 'public');
                $requestData['image'] = $filename;
                $requestData['disk'] = config('site.upload_disk');
            }

            $requestData['slug'] = Str::slug($requestData['title']);
            $requestData['project_id'] = $project->id;

            $product = Product::create($requestData);
            DB::commit();
            return new ProductResource($product);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->responseJson(['message' => 'Something wrong, please try again later!'], 400);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  string  $slug
     */
    public function detail(Request $request, $slug)
    {
        $project = $this->getProjectDefault($request);
        $product = Product::where('slug', '=', $slug)->where('project_id', '=', $project->id)->first();
        if ($product) {
            return new ProductResource($product);
        }
        return response()->json(['message' => 'Product not found!'], 400);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     */
    public function show($id)
    {
        $product = Product::findOrFail($id);
        if ($product) {
            return new ProductResource($product);
        }
        return response()->json(['message' => 'Product not found!'], 400);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     */
    public function update(Request $request, $id)
    {
        $project = $this->getProjectDefault($request);
        $this->validate($request, [
            'title' => ['required', 'string', Rule::unique('products')->ignore($id)->where(function ($query) use ($project) {
                return $query->where('project_id', '=', $project->id);
            })],
            'description' => ['string'],
            'image' => ['mimes:jpg,jpeg,gif,bmp,png', 'max:300']
        ]);

        $product = Product::find($id);

        if (!$product) {
            return $this->responseJson(['message' => 'Product not found'], 404);
        }
        if ($project->id !== $product->project_id) {
            return $this->responseJson(['message' => 'Your actions are not allowed!'], 401);
        }

        $user = auth()->user();
        if ($project->user_id !== $user->id) {
            return $this->responseJson(['message' => 'Your actions are not allowed!'], 401);
        }

        DB::beginTransaction();
        try {
            $requestData = $request->all();

            // Get the image
            $image = $request->file('image');
            if ($image) {
                // Delete old image
                Storage::disk($product->disk)->delete('/uploads/original/'.$product->image);

                //Get the original file name and replace any spaces with _
                $filename = time()."_".preg_replace('/\s+/', '_', strtolower($image->getClientOriginalName()));
                // Move the image to folder
                $image->storeAs('uploads/original', $filename, 'public');
                $requestData['image'] = $filename;
            }

            $requestData['slug'] = Str::slug($requestData['title']);
            $product->update($requestData);
            DB::commit();
            return new ProductResource($product);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->responseJson(['message' => 'Something wrong, please try again later!'], 400);
        }
    }

    public function customDestroy(Request $request, $id) {
        $project = $this->getProjectDefault($request);
        $product = Product::find($id);

        if (!$product) {
            return $this->responseJson(['message' => 'Product not found'], 404);
        }
        $user = auth()->user();
        if ($project->user_id !== $user->id) {
            return $this->responseJson(['message' => 'Your actions are not allowed!'], 401);
        }
        if ($project->id !== $product->project_id) {
            return $this->responseJson(['message' => 'Your actions are not allowed!'], 404);
        }

        // Delete old image
        Storage::disk($product->disk)->delete('/uploads/original/'.$product->image);
        $product->delete();
        return $this->responseJson(['message' => 'Delete successfully!'], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
//     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        $product = Product::find($id);
        // Delete old image
        Storage::disk($product->disk)->delete('/uploads/original/'.$product->image);
        $product->delete();
        return $this->responseJson(['message' => 'Delete successfully!'], 200);
    }
}
