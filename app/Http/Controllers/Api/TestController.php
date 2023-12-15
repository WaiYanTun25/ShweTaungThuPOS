<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class TestController extends Controller
{
    public function testing()
    {
        $user = User::with('roles')->where('id', 1)->get();
        return $user;
        return response()->json(['message' => 'Testing API route']);
    }

}
