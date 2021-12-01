<?php

use App\Http\Controllers\MrTestController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function() {
  return view('welcome');
});

Route::get('/trade', [MrTestController::class, 'tradeResult']);

// Очистка кеша
Route::get('/clear', function() {
  Artisan::call('cache:clear');
  Artisan::call('view:clear');
  Artisan::call('route:clear');
  DB::table('trade_logs')->truncate();

  //composer dump-autoload --optimize
  return back();
})->name('clear');

Route::match(['get', 'post'], '/test', [MrTestController::class, 'index'])->name('admin_test');
Route::match(['get', 'post'], '/test2', [MrTestController::class, 'index2']);
Route::match(['get', 'post'], '/yobit', [MrTestController::class, 'testYobit']);
Route::match(['get', 'post'], '/trading', [MrTestController::class, 'trading']);