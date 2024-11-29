<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

// Basic pages
Route::get('/', 'PageController@home');
Route::get('/about', 'PageController@view');


// User authentication routes
Route::get('/register', 'PageController@view');
Route::get('/login', 'PageController@view');
Route::get('/logout', 'UserController@logout');

Route::post('/register', 'UserController@create');
Route::post('/login', 'UserController@login');

Route::get('/forgot', 'PageController@view');
Route::post('/forgot', 'UserController@forgotPassword');
Route::get('/forgot/{token}', 'UserController@verifyToken');
Route::post('/forgot/{token}', 'UserController@changePassword');



// Event routes
Route::get('/event', 'EventController@createForm');
Route::post('/event', 'EventController@create');

Route::get('/event/{event}/edit', 'EventController@editForm');
Route::post('/event/{event}/edit', 'EventController@edit');

Route::get('/event/{event}/delete', 'EventController@deleteForm');
Route::post('/event/{event}/delete', 'EventController@delete');

Route::get('/event/{event}/clone', 'EventController@cloneForm');
Route::post('/event/{event}/clone', 'EventController@cloneEvent');

Route::get('/event/{event}', 'EventController@view');

Route::get('/event/{event}/review','EventController@review');


// Department routes
Route::get('/event/{event}/departments', 'DepartmentController@listDepartments');
Route::get('/event/{event}/department/create', 'DepartmentController@createForm');
Route::post('/department', 'DepartmentController@create');

Route::get('/department/{department}/edit', 'DepartmentController@editForm');
Route::post('/department/{department}/edit', 'DepartmentController@edit');

Route::get('/department/{department}/delete', 'DepartmentController@deleteForm');
Route::post('/department/{department}/delete', 'DepartmentController@delete');


// Shift routes
Route::get('/event/{event}/shifts', 'ShiftController@listShifts');
Route::get('/event/{event}/shift/create', 'ShiftController@createForm');
Route::post('/shift', 'ShiftController@create');

Route::get('/shift/{shift}/edit', 'ShiftController@editForm');
Route::post('/shift/{shift}/edit', 'ShiftController@edit');

Route::get('/shift/{shift}/delete', 'ShiftController@deleteForm');
Route::post('/shift/{shift}/delete', 'ShiftController@delete');


// Schedule routes
Route::get('/event/{event}/schedule/create', 'ScheduleController@createForm');
Route::post('/schedule', 'ScheduleController@create');

Route::get('/schedule/{schedule}/edit', 'ScheduleController@editForm');
Route::post('/schedule/{schedule}/edit', 'ScheduleController@edit');

Route::get('/schedule/{schedule}/delete', 'ScheduleController@deleteForm');
Route::post('/schedule/{schedule}/delete', 'ScheduleController@delete');

Route::get('/slot/{id}/view', 'SlotController@view');
Route::post('/slot/{slot}/take', 'SlotController@take');
Route::post('/slot/{id}/release', 'SlotController@release');

// Routes for Admins / Deparment Leads
Route::group(['middleware' => ['auth', 'lead']], function()
{
    Route::post('/slot/{slot}/edit','SlotController@edit');
    Route::post('/slot/{id}/adminRelease', 'SlotController@adminRelease');
    Route::post('/slot/{slot}/adminAssign', 'SlotController@adminAssign');
});

// User profile routes
Route::get('/profile', 'ProfileController@view');
Route::get('/profile/shifts', 'ProfileController@shifts');

Route::get('/profile/edit', 'ProfileController@editForm');
Route::get('/profile/data/edit', 'ProfileController@dataForm');
Route::get('/profile/password/edit', 'ProfileController@passwordForm');
Route::post('/profile/edit', 'ProfileController@edit');

Route::get('/profile/upload', 'ProfileController@uploadForm');
Route::post('/profile/upload', 'ProfileController@upload');


// Admin routes
Route::get('/user/{user}/edit', 'AdminController@userProfileEdit');
Route::get('/users', 'AdminController@userList');
Route::get('/user/{user}', 'AdminController@userProfile');
Route::post('/user/{user}/update', 'AdminController@userProfileUpdate');

Route::get('/uploads', 'AdminController@uploadList');
Route::post('/upload/{upload}/edit', 'AdminController@uploadEdit');

Route::get('/reports', 'ReportController@reportList');
Route::post('/report/users', 'ReportController@searchUsers');
Route::post('/report/departments', 'ReportController@getDepartments');
Route::post('/report/days', 'ReportController@getDays');
Route::post('/report/generate', 'ReportController@generateReport');

// API routes
Route::group(['middleware' => ['auth', 'lead']], function()
{
    Route::get('/v1/profile', 'APIController@profile');
    Route::get('/v1/events', 'APIController@events');
    Route::get('/v1/event/{id}/departments', 'APIController@departments');
    Route::get('/v1/event/{id}/roles', 'APIController@roles');
    Route::get('/v1/event/{id}/shifts', 'APIController@shifts');
    Route::post('/v1/shift/{id}', 'APIController@updateShift');
});