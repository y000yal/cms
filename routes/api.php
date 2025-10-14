<?php

use Illuminate\Support\Facades\Route;

Route::get( '/', function ( Request $request ) {
    return env( 'APP_NAME', 'cms' ) . '@' . app()->version();
} );
