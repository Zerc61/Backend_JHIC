<?php
// app/Http/Controllers/Api/Manager/ManagerGalleryController.php

namespace App\Http\Controllers\Api\Manager;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class ManagerGalleryController extends Controller
{
    public function index(): JsonResponse { return response()->json(['message' => 'Coming soon.']); }
}