<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ForwardController extends Controller
{
    public function kfAgent(Request $request)
    {
        Log::debug('===kfAgent===',$request->all());
    }
}