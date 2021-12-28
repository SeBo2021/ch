<?php

use Illuminate\Support\Facades\Route;

Route::group([
    'namespace' => 'ChannelApi',
    'middleware'=> [
//        'auth:channel',
        'client:channel-download',
    ],
],function (){
    Route::post('index', 'LandingController@index');
    Route::post('record', 'LandingController@record');
});

Route::group([
    'namespace' => 'ChannelApi',
    'middleware'=> [
        'channel',
    ],
],function (){
    Route::get('statistic/{channel?}', 'StatisticController@index');
});

