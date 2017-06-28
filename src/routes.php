<?php


Route::group(['middleware' => ['web']], function () {
    Route::get('payment', 'Itacs\ScholarChip\ScholarChipController@index');
});