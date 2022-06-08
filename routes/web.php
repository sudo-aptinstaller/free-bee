<?php

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

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');
Route::get('/search', [App\Http\Controllers\ChatController::class, 'search'])->name('search');
Route::get('/intiate-chat', [App\Http\Controllers\ChatController::class, 'singlePersonChat'])->name('singlePersonChat');
Route::get('/fetch-user-chats', [App\Http\Controllers\ChatController::class, 'fetchUserChats'])->name('fetchUserChats');
Route::get('/who-is', [App\Http\Controllers\ChatController::class, 'whoIs'])->name('userFinder');
/**
 * Chat And Actions
 */

Route::get('chat/{pcode}', [App\Http\Controllers\ChatController::class, 'fetchChatMessages'])->name('fetchChatMessages');
Route::post('/chat/{pcode}/change-name', [App\Http\Controllers\ChatController::class, 'changeName'])->name('changeName');
Route::post('/chat/{pcode}/send-message', [App\Http\Controllers\ChatController::class, 'sendMessage'])->name('sendMessage');