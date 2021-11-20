<?php

use App\Http\Controllers\MrTestController;
use Illuminate\Support\Facades\Artisan;
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
// Очистка кеша
Route::get('/clear', function() {
  Artisan::call('cache:clear');
  Artisan::call('view:clear');
  Artisan::call('route:clear');

  //composer dump-autoload --optimize
  return back();
})->name('clear');

Route::match(['get', 'post'], '/test', [MrTestController::class, 'index'])->name('admin_test');