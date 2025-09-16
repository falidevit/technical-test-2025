<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\AccuWeatherService;

class WeatherSearch extends Component
{
    public string $search = '';
    public array $cities = [];
    public ?array $selectedCity = null;
    public ?array $currentWeather = null;
    public bool $isLoading = false;
    public ?string $error = null;
    private bool $skipSearchOnUpdate = false;

    protected AccuWeatherService $weatherService;

    public function boot(AccuWeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    public function mount()
    {
        // Load Montreal by default
        $this->loadDefaultCity();
    }

    private function loadDefaultCity()
    {
        try {
            $cities = $this->weatherService->searchCities('Montreal');
            if (!empty($cities)) {
                // Find Montreal, Canada specifically
                $montreal = collect($cities)->first(function ($city) {
                    return $city['Country']['ID'] === 'CA' && 
                           stripos($city['LocalizedName'], 'Montreal') !== false;
                });
                
                if ($montreal) {
                    $this->selectCity($montreal);
                }
            }
        } catch (\Exception $e) {
            // Silently fail, user can search manually
            \Log::info('Failed to load default city', ['error' => $e->getMessage()]);
        }
    }

    public function updated($property)
    {
        if ($property === 'search' && !$this->skipSearchOnUpdate) {
            $this->searchCities();
        }
        
        // Reset the flag after any update
        $this->skipSearchOnUpdate = false;
    }

    public function searchCities()
    {
        if (strlen($this->search) < 2) {
            $this->cities = [];
            return;
        }

        // Reset error state
        $this->error = null;
        
        // Don't show loading for very fast cached responses
        $this->isLoading = true;

        try {
            $this->cities = $this->weatherService->searchCities($this->search);
            
            // Debug log for troubleshooting
            if (empty($this->cities)) {
                \Log::info('No cities found for search', ['search' => $this->search]);
            }
        } catch (\Exception $e) {
            \Log::error('City search failed', ['search' => $this->search, 'error' => $e->getMessage()]);
            $this->error = 'Failed to search cities. Please try again.';
            $this->cities = [];
        } finally {
            $this->isLoading = false;
        }
    }

    public function selectCity($cityData)
    {
        $this->selectedCity = $cityData;
        $this->cities = [];
        $this->error = null;
        
        // Set flag to skip search on next update
        $this->skipSearchOnUpdate = true;
        
        // Update search display text without triggering searchCities()
        $this->search = $cityData['LocalizedName'] . ', ' . $cityData['Country']['LocalizedName'];
        
        $this->loadCurrentWeather($cityData['Key']);
    }

    public function loadCurrentWeather($locationKey)
    {
        $this->isLoading = true;
        $this->error = null;

        try {
            $this->currentWeather = $this->weatherService->getCurrentWeather($locationKey);
            
            if (!$this->currentWeather) {
                $this->error = 'Weather data not available for this location.';
            }
        } catch (\Exception $e) {
            $this->error = 'Failed to load weather data. Please try again.';
            $this->currentWeather = null;
        } finally {
            $this->isLoading = false;
        }
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->cities = [];
        $this->error = null;
        
        // Reload Montreal as default city
        $this->loadDefaultCity();
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

    public function getWindSpeed()
    {
        if (!isset($this->currentWeather['Wind']['Speed']['Metric']['Value'])) {
            return '--';
        }

        $kmh = $this->currentWeather['Wind']['Speed']['Metric']['Value'];
        $mph = $kmh * 0.621371;

        return round($kmh) . ' km/h / ' . round($mph) . ' mph';
    }

    public function getHumidity()
    {
        return $this->currentWeather['RelativeHumidity'] ?? '--';
    }

    public function getWeatherText()
    {
        return $this->currentWeather['WeatherText'] ?? 'Unknown';
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
        return view('livewire.weather-search')
            ->layout('layouts.app');
    }
}