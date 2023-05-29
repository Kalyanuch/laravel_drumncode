<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * API users controller.
 */
class UsersController extends Controller
{
    /**
     * Gets list of users.
     *
     * @return \Illuminate\Http\JsonResponse
     *   Json response.
     */
    public function __invoke()
    {
        return response()->json(User::all(), 200);
    }
}
