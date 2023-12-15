<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Validator;

class CategoryController extends ApiBaseController
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $getCategories = Category::get();
        
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $getCategories);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            "name" => 'required',
        ]);

        if($validator->fails())
        {
            return $this->sendErrorResponse('error', Response::HTTP_UNPROCESSABLE_ENTITY, $validator->errors());
        }

        $createdCategory = new Category();
        $createdCategory->name = $request->name;
        $createdCategory->save();

        return $this->sendSuccessResponse('success', Response::HTTP_CREATED, $createdCategory);
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
        ]);

        if($validator->fails())
        {
            return $this->sendErrorResponse('error', Response::HTTP_UNPROCESSABLE_ENTITY, $validator->errors());
        }

        $createdCategory = Category::findOrFail($id);
        $createdCategory->name = $request->name;
        $createdCategory->save();

        return $this->sendSuccessResponse('success', Response::HTTP_CREATED, $createdCategory);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $category = Category::findOrFail($id);
        $deleteCategory = $category->delete();
        return $this->sendSuccessResponse('success', Response::HTTP_OK, $category);
    }
}
