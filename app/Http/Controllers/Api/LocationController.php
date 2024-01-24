<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CityRequest;
use App\Models\City;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LocationController extends ApiBaseController
{

    // cities functions
    public function getCities(Request $request)
    {
        $getCities = City::get();

        return $this->sendSuccessResponse('Success', Response::HTTP_OK, $getCities);
    }

    public function createCities(CityRequest $request)
    {
        $validatedData = $request->validated();
        try{
            $createdCity = City::create($validatedData);

            $message = 'City (' . $createdCity->name . ') is created successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
            
        }catch(Exception $e){
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_BAD_REQUEST);
        }
    }

    public function getCityById($id)
    {
        $getCity = City::with('townships')->findOrFail($id);

        return $this->sendSuccessResponse('Success', Response::HTTP_OK, $getCity);
    }

    public function updateCities(Request $request, $id)
    {

    }

    public function deleteCities($id)
    {

    }

    // townships functions

    public function getTownships(Request $request)
    {

    }

    public function createTownships(Request $request)
    {

    }

    public function getTownshipById(Request $request)
    {
        
    }

    public function updateTownships(Request $request, $id)
    {

    }

    public function deleteTownships($id)
    {

    }
}
