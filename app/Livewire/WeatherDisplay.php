<?php

namespace App\Livewire;

use Livewire\Component;

class WeatherDisplay extends Component
{
    public $currentWeather;
    public $selectedCity;

    public function mount($currentWeather = null, $selectedCity = null)
    {
        $this->currentWeather = $currentWeather;
        $this->selectedCity = $selectedCity;
    }

    public function getTemperature()
    {
        if (!$this->currentWeather || !isset($this->currentWeather['Temperature']['Metric']['Value'])) {
            return '--';
        }

        $celsius = $this->currentWeather['Temperature']['Metric']['Value'];
        $fahrenheit = ($celsius * 9/5) + 32;

        return round($celsius) . '°C / ' . round($fahrenheit) . '°F';
    }

    public function getWeatherText()
    {
        return $this->currentWeather['WeatherText'] ?? 'Unknown';
    }

    public function getHumidity()
    {
        return $this->currentWeather['RelativeHumidity'] ?? '--';
    }

    public function getWindSpeed()
    {
        if (!isset($this->currentWeather['Wind']['Speed']['Metric']['Value'])) {
            return '--';
        }

        $kmh = $this->currentWeather['Wind']['Speed']['Metric']['Value'];
        $mph = $kmh * 0.621371;

        return round($kmh) . ' km/h / ' . round($mph) . ' mph';
    }

    public function getUVIndex()
    {
        return $this->currentWeather['UVIndex'] ?? '--';
    }

    public function getVisibility()
    {
        if (!isset($this->currentWeather['Visibility']['Metric']['Value'])) {
            return '--';
        }

        $km = $this->currentWeather['Visibility']['Metric']['Value'];
        $miles = $km * 0.621371;

        return round($km) . ' km / ' . round($miles) . ' mi';
    }

    public function render()
    {
        return view('livewire.weather-display');
    }
}