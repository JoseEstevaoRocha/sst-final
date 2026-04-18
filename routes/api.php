<?php
// API routes (if needed in future)
use Illuminate\Support\Facades\Route;
Route::get('/health', fn() => response()->json(['status'=>'ok','version'=>'2.0.0']));
