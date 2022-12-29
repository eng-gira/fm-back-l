<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\FundController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

// Auth
Route::post('auth/register', [AuthController::class, 'register']);
Route::post('auth/login', [AuthController::class, 'login']);
Route::post('auth/refresh', [AuthController::class, 'refresh']);
Route::post('auth/logout', [AuthController::class, 'logout']);

// Fund 
Route::get('fund/index', [FundController::class, 'index']);
Route::get('fund/readSingle/{id}', [FundController::class, 'readSingle']);
Route::post('fund/store', [FundController::class, 'store']);
Route::post('fund/deposit', [FundController::class, 'deposit']);
Route::post('fund/withdraw', [FundController::class, 'withdraw']);
Route::get('fund/getDepositsHistory/{for}', [FundController::class, 'getDepositsHistory']);
Route::get('fund/getWithdrawalsHistory/{for}', [FundController::class, 'getWithdrawalsHistory']);
Route::post('fund/setFundName', [FundController::class, 'setFundName']);
Route::post('fund/setFundPercentage', [FundController::class, 'setFundPercentage']);
Route::post('fund/setSize', [FundController::class, 'setSize']);
Route::post('fund/setNotes', [FundController::class, 'setNotes']);
