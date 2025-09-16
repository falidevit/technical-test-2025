<?php

use Illuminate\Support\Facades\Route;
use App\Livewire\WeatherSearch;

Route::get('/', WeatherSearch::class)->name('home');
