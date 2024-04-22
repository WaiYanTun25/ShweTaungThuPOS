<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\CityRequest;
use App\Http\Requests\TownshipRequest;
use App\Models\City;
use App\Models\Township;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class LocationController extends ApiBaseController
{

    public function __construct()
    {
        // Check if the 'permission' query parameter is present and set to 'true'
        $checkPermission = request()->query('permission') === 'True';
        // Conditionally apply permission middleware
        if ($checkPermission) {
            $this->middleware('permission:city:read')->only('getCities', 'getCityById');
            $this->middleware('permission:city:create')->only('createCities');
            $this->middleware('permission:city:edit')->only('updateCities');
            $this->middleware('permission:city:delete')->only('deleteCity'); // this api is still remain
        }

        if ($checkPermission) {
            $this->middleware('permission:township:read')->only('getTownships', 'getTownshipById');
            $this->middleware('permission:township:create')->only('createTownships');
            $this->middleware('permission:township:edit')->only('updateTownships');
            $this->middleware('permission:township:delete')->only('deleteTownships'); // this api is still remain
        }
    }
    // cities functions
    public function getCities(Request $request)
    {
        $getCities = City::with('townships')->get();

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
        $getCity = City::findOrFail($id);
        $getCity->update($request->all());

        return $this->sendSuccessResponse('Success', Response::HTTP_CREATED);
    }

    public function deleteCity($id)
    {
        $getCity = City::findOrFail($id);

        // Check if there are related records in customer, township, or supplier tables
        $hasRelatedRecords = $getCity->customers()->exists() ||
                            $getCity->townships()->exists() ||
                            $getCity->suppliers()->exists();

        // If there are related records, do not delete the city
        if ($hasRelatedRecords) {
            return $this->sendErrorResponse(
                'City has related records in customer, township, or supplier tables. Cannot delete.',
                Response::HTTP_CONFLICT
            );
        }
        $getCity->delete();
        $message = 'City (' . $getCity->name . ') is Deleted successfully';
        return $this->sendSuccessResponse($message, Response::HTTP_OK);
    }

    // townships functions

    public function getTownships(Request $request)
    {
        $getTownships = Township::get();

        return $this->sendSuccessResponse('Success', Response::HTTP_OK, $getTownships);
    }

    public function createTownships(TownshipRequest $request)
    {
        $validatedData = $request->validated();
        try{
            $createdTownship = Township::create($validatedData);

            $message = 'Township (' . $createdTownship->name . ') is created successfully';
            return $this->sendSuccessResponse($message, Response::HTTP_CREATED);
        }catch(Exception $e) {
            return $this->sendErrorResponse($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getTownshipById(Request $request, $id)
    {
        $getTownship = Township::findOrFail($id);

        return $this->sendSuccessResponse('Success', Response::HTTP_OK, $getTownship);
    }

    public function updateTownships(Request $request, $id)
    {
        $getTownship = Township::findOrFail($id);
        $getTownship->update($request->all());

        $message = 'Township (' . $getTownship->name . ') is updated successfully';
        return $this->sendSuccessResponse($message, Response::HTTP_CREATED, $getTownship);
    }

    public function deleteTownships($id)
    {
        $getTownship = Township::findOrFail($id);

        // Check if there are related records in the customer or supplier tables
        $hasRelatedCustomerRecords = $getTownship->customers()->exists();
        $hasRelatedSupplierRecords = $getTownship->suppliers()->exists();
        
        // If there are related records in either table, do not delete the township
        if ($hasRelatedCustomerRecords || $hasRelatedSupplierRecords) {
            return $this->sendErrorResponse(
                'Township has related records in customer or supplier. Cannot delete.',
                Response::HTTP_CONFLICT
            );
        }
        $getTownship->delete();
        $message = 'Township (' . $getTownship->name . ') is Deleted successfully';
        return $this->sendSuccessResponse($message, Response::HTTP_OK);
    }
}
