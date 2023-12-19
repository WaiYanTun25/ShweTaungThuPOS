<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryRequest;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class CategoryController extends ApiBaseController
{
    // public function __construct()
    // {
    //     $this->middleware('permission:category:get')->only('index');
    //     $this->middleware('permission:category:create')->only('store');
    //     $this->middleware('permission:category:detail')->only('show');
    //     $this->middleware('permission:category:edit')->only('update');
    //     $this->middleware('permission:category:delete')->only('delete');
    // }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $getCategories = Category::select('id', 'name', 'prefix','created_at');

        $search = $request->query('search');
        if ($search) {
            $getCategories->where('name', 'like', "%$search%");
        }
         // Handle order and column
         $order = $request->query('order', 'asc'); // default to asc if not provided
         $column = $request->query('column', 'created_at'); // default to created_at if not provided
     
         $getCategories->orderBy($column, $order);
     
         // Get the final result
         $getCategories = $getCategories->latest('created_at')->get();
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $getCategories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(CategoryRequest $request)
    {
        $createdCategory = new Category();
        $createdCategory->name = $request->name;
        $createdCategory->prefix = $request->prefix;
        $createdCategory->save();

        $message = 'Category ('. $createdCategory->name .') is created successfully';

        return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validator = Validator::make($request->all(), [
            "name" => 'required',
            "prefix" => 'required'
        ]);

        if($validator->fails())
        {
            return $this->sendErrorResponse('error', Response::HTTP_UNPROCESSABLE_ENTITY, $validator->errors());
        }

        $createdCategory = Category::findOrFail($id);
        $createdCategory->name = $request->name;
        $createdCategory->prefix = $request->prefix;
        $createdCategory->save();

        
        $message = 'Category ('. $createdCategory->name .') is updated successfully';

        return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        $deleteCategory = $category->delete();

        $message = 'Category ('. $category->name .') is deleted successfully';

        return $this->sendSuccessResponse($message, Response::HTTP_OK);
    }
}
