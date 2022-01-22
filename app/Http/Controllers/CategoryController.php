<?php

namespace App\Http\Controllers;

use App\Http\Middleware\RoleAdmin;
use App\Http\Resources\CategoryResource;
use App\Http\Resources\ProductCollection;
use App\Http\Resources\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\Project;
use App\Traits\ApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class CategoryController extends Controller
{

    use ApiService;

    public function __construct()
    {
        $this->middleware('auth:api', ['except' => ['index', 'show', 'detail']]);
        $this->middleware(RoleAdmin::class, ['except' => ['index', 'show', 'detail']]);
    }


    public function getProjectDefault(Request $request) {
        return Project::where('name', $request->header('project-name'))->first();
    }

    /**
     * Display a listing of the resource.
     *
//     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $categories = Category::where('project_id', $this->getProjectDefault($request)->id);
        if ($request->title) {
            $categories = $categories->where('title', 'like', '%'.$request->title.'%');
        }
        if ($request->description) {
            $categories = $categories->where('description', 'like', '%'.$request->description.'%');
        }
        $categories = $categories->paginate($request->limit ?: 10);
        return new ProductCollection($categories);
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
     * @param  \Illuminate\Http\Request  $request
     */
    public function store(Request $request)
    {
        $project = $this->getProjectDefault($request);
        $this->validate($request, [
            'title' => ['required', 'string', Rule::unique('categories')->where(function ($query) use ($project) {
                return $query->where('project_id', '=', $project->id);
            })],
            'description' => ['string'],
            'image' => ['mimes:jpg,jpeg,gif,bmp,png', 'max:300']
        ]);

        $categoriesCount = $project->categories->count();
        if ($categoriesCount > 30) {
            return $this->responseJson(['message' => 'You have created up to 30 categories!'], 400);
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
            }

            $requestData['slug'] = Str::slug($requestData['title']);
            $requestData['project_id'] = $project->id;

            $category = Category::create($requestData);
            DB::commit();
            return new CategoryResource($category);
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
        $category = Product::where('slug', '=', $slug)->where('project_id', '=', $project->id)->first();
        if ($category) {
            return new CategoryResource($category);
        }
        return response()->json(['message' => 'Category not found!'], 400);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     */
    public function show(Request $request, $id)
    {
        $project = $this->getProjectDefault($request);
        $category = Product::where('id', '=', $id)->where('project_id', '=', $project->id)->first();
        if ($category) {
            return new CategoryResource($category);
        }
        return response()->json(['message' => 'Category not found!'], 400);
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
            'title' => ['required', 'string', Rule::unique('categories')->ignore($id)->where(function ($query) use ($project) {
                return $query->where('project_id', '=', $project->id);
            })],
            'description' => ['string'],
            'image' => ['mimes:jpg,jpeg,gif,bmp,png', 'max:300']
        ]);

        $category = Category::find($id);

        if (!$category) {
            return $this->responseJson(['message' => 'Category not found'], 404);
        }
        if ($project->id !== $category->project_id) {
            return $this->responseJson(['message' => 'Your actions are not allowed!'], 404);
        }

        DB::beginTransaction();
        try {
            $requestData = $request->all();

            // Get the image
            $image = $request->file('image');
            if ($image) {
                // Delete old image
                Storage::disk($category->disk)->delete('/uploads/original/'.$category->image);

                //Get the original file name and replace any spaces with _
                $filename = time()."_".preg_replace('/\s+/', '_', strtolower($image->getClientOriginalName()));
                // Move the image to folder
                $image->storeAs('uploads/original', $filename, 'public');
                $requestData['image'] = $filename;
            }

            $requestData['slug'] = Str::slug($requestData['title']);
            $category->update($requestData);
            DB::commit();
            return new CategoryResource($category);
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->responseJson(['message' => 'Something wrong, please try again later!'], 400);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
