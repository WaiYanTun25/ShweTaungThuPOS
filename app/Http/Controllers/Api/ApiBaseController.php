<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use stdClass;

class ApiBaseController extends Controller
{
    public function sendSuccessResponse($message = "Success", $status_code = Response::HTTP_OK, $data = new stdClass)
    {
        return Response([
            "result" => 1,
            "message" => $message,
            "data" => $data
        ], $status_code);
    }

    public function sendErrorResponse($message = "Error", $status_code = Response::HTTP_INTERNAL_SERVER_ERROR, $data = new stdClass)
    {
        return Response([
            "result" => 0,
            "message" => $message,
            "data" => $data
        ], $status_code);
    }

    public function sendCustomResponse($result_code = 0, $message = "Ok", $status_code = Response::HTTP_OK, $data = new stdClass)
    {
        return Response([
            "result" => $result_code,
            "message" => $message,
            "data" => $data
        ], $status_code);
    }
}
