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
    public ?array $dailyForecast = null;
    public bool $isLoading = false;
    public ?string $error = null;
    public bool $useCelsius = true;
    private bool $skipSearchOnUpdate = false;

    protected AccuWeatherService $weatherService;

    public function boot(AccuWeatherService $weatherService)
    {
        $this->weatherService = $weatherService;
    }

    public function mount()
    {
        // Wait for automatic geolocation or fallback event
        // Don't load Montreal immediately
    }

    private function loadDefaultCity()
    {
        try {
            // Try different search terms for Montreal
            $searchTerms = ['Montreal', 'Montréal', 'Montreal Canada'];
            $montreal = null;
            
            foreach ($searchTerms as $term) {
                $cities = $this->weatherService->searchCities($term);
                if (!empty($cities)) {
                    // Find Montreal, Canada specifically
                    $montreal = collect($cities)->first(function ($city) {
                        return $city['Country']['ID'] === 'CA' &&
                               (stripos($city['LocalizedName'], 'Montreal') !== false || 
                                stripos($city['LocalizedName'], 'Montréal') !== false);
                    });
                    
                    if ($montreal) {
                        break;
                    }
                }
            }

            if ($montreal) {
                $this->selectCity($montreal);
            } else {
                // Fallback to a generic major city if Montreal is not found
                $cities = $this->weatherService->searchCities('Toronto');
                if (!empty($cities)) {
                    $toronto = collect($cities)->first(function ($city) {
                        return $city['Country']['ID'] === 'CA';
                    });
                    if ($toronto) {
                        $this->selectCity($toronto);
                    }
                }
            }
        } catch (\Exception $e) {
            // Silently fail, user can search manually
            \Illuminate\Support\Facades\Log::info('Failed to load default city', ['error' => $e->getMessage()]);
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
                \Illuminate\Support\Facades\Log::info('No cities found for search', ['search' => $this->search]);
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('City search failed', ['search' => $this->search, 'error' => $e->getMessage()]);
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
            // Load both current weather and daily forecast
            $this->currentWeather = $this->weatherService->getCurrentWeather($locationKey);
            $this->dailyForecast = $this->weatherService->getDailyForecast($locationKey);

            if (!$this->currentWeather) {
                $this->error = 'Weather data not available for this location.';
            }
        } catch (\Exception $e) {
            $this->error = 'Failed to load weather data. Please try again.';
            $this->currentWeather = null;
            $this->dailyForecast = null;
        } finally {
            $this->isLoading = false;
        }
    }

    public function clearSearch()
    {
        $this->search = '';
        $this->cities = [];
        $this->error = null;
        $this->dailyForecast = null;

        // Reload Montreal as default city
        $this->loadDefaultCity();
    }

    public function getUserLocation()
    {
        $this->dispatch('get-user-location');
    }

    #[\Livewire\Attributes\On('searchByCoordinates')]
    public function searchByCoordinates($latitude, $longitude)
    {
        try {
            $this->isLoading = true;
            $this->error = null;

            // Use AccuWeather's reverse geocoding
            $response = \Illuminate\Support\Facades\Http::get("http://dataservice.accuweather.com/locations/v1/cities/geoposition/search", [
                'apikey' => config('services.accuweather.api_key'),
                'q' => "{$latitude},{$longitude}",
                'language' => 'en-us'
            ]);

            if ($response->successful()) {
                $cityData = $response->json();
                if ($cityData) {
                    $this->selectCity($cityData);
                } else {
                    $this->error = 'Could not find your location. Please search manually.';
                }
            } else {
                $this->error = 'Failed to get your location. Please search manually.';
            }
        } catch (\Exception $e) {
            $this->error = 'Geolocation error. Please search manually.';
            \Illuminate\Support\Facades\Log::error('Geolocation error', ['error' => $e->getMessage()]);
        } finally {
            $this->isLoading = false;
        }
    }

    #[\Livewire\Attributes\On('geolocation-error')]
    public function handleGeolocationError($error)
    {
        $this->error = $error;
    }

    #[\Livewire\Attributes\On('fallback-to-montreal')]
    public function fallbackToMontreal()
    {
        $this->loadDefaultCity();
    }

    public function toggleTemperatureUnit()
    {
        $this->useCelsius = !$this->useCelsius;
    }

    public function getTemperature()
    {
        if (!$this->currentWeather || !isset($this->currentWeather['Temperature']['Metric']['Value'])) {
            return '--';
        }

        $celsius = $this->currentWeather['Temperature']['Metric']['Value'];
        
        if ($this->useCelsius) {
            return round($celsius) . '°C';
        } else {
            $fahrenheit = ($celsius * 9/5) + 32;
            return round($fahrenheit) . '°F';
        }
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

    public function getCurrentWeatherIcon()
    {
        return $this->currentWeather['WeatherIcon'] ?? null;
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

    public function getFourPeriods()
    {
        if (!$this->dailyForecast || !isset($this->dailyForecast['DailyForecasts'][0])) {
            return [
                'morning' => ['temp' => '--', 'condition' => '--', 'rainProb' => null, 'icon' => null],
                'afternoon' => ['temp' => '--', 'condition' => '--', 'rainProb' => null, 'icon' => null],
                'evening' => ['temp' => '--', 'condition' => '--', 'rainProb' => null, 'icon' => null],
                'night' => ['temp' => '--', 'condition' => '--', 'rainProb' => null, 'icon' => null]
            ];
        }

        $forecast = $this->dailyForecast['DailyForecasts'][0];
        $minTemp = $forecast['Temperature']['Minimum']['Value'] ?? 0;
        $maxTemp = $forecast['Temperature']['Maximum']['Value'] ?? 0;
        
        $unit = $this->useCelsius ? '°C' : '°F';
        if (!$this->useCelsius) {
            $minTemp = ($minTemp * 9/5) + 32;
            $maxTemp = ($maxTemp * 9/5) + 32;
        }

        return [
            'morning' => [
                'temp' => round(($minTemp + $maxTemp) / 2 - 3) . $unit,
                'condition' => $forecast['Day']['IconPhrase'] ?? '--',
                'rainProb' => $forecast['Day']['RainProbability'] ?? null,
                'icon' => $forecast['Day']['Icon'] ?? null
            ],
            'afternoon' => [
                'temp' => round($maxTemp) . $unit,
                'condition' => $forecast['Day']['IconPhrase'] ?? '--',
                'rainProb' => $forecast['Day']['RainProbability'] ?? null,
                'icon' => $forecast['Day']['Icon'] ?? null
            ],
            'evening' => [
                'temp' => round(($minTemp + $maxTemp) / 2) . $unit,
                'condition' => $forecast['Night']['IconPhrase'] ?? '--',
                'rainProb' => $forecast['Night']['RainProbability'] ?? null,
                'icon' => $forecast['Night']['Icon'] ?? null
            ],
            'night' => [
                'temp' => round($minTemp) . $unit,
                'condition' => $forecast['Night']['IconPhrase'] ?? '--',
                'rainProb' => $forecast['Night']['RainProbability'] ?? null,
                'icon' => $forecast['Night']['Icon'] ?? null
            ]
        ];
    }

    public function getFiveDayForecast()
    {
        if (!$this->dailyForecast || !isset($this->dailyForecast['DailyForecasts'])) {
            return [];
        }

        $forecasts = [];
        foreach ($this->dailyForecast['DailyForecasts'] as $index => $forecast) {
            $minTemp = $forecast['Temperature']['Minimum']['Value'] ?? 0;
            $maxTemp = $forecast['Temperature']['Maximum']['Value'] ?? 0;
            
            if (!$this->useCelsius) {
                $minTemp = ($minTemp * 9/5) + 32;
                $maxTemp = ($maxTemp * 9/5) + 32;
            }
            
            $unit = $this->useCelsius ? '°C' : '°F';
            
            $date = $forecast['Date'] ?? null;
            $dayName = $date ? \Carbon\Carbon::parse($date)->format('D') : 'Day ' . ($index + 1);
            $dateFormatted = $date ? \Carbon\Carbon::parse($date)->format('M j') : 'Day ' . ($index + 1);
            
            $forecasts[] = [
                'date' => $dateFormatted,
                'dayName' => $dayName,
                'minTemp' => round($minTemp) . $unit,
                'maxTemp' => round($maxTemp) . $unit,
                'dayCondition' => $forecast['Day']['IconPhrase'] ?? '--',
                'nightCondition' => $forecast['Night']['IconPhrase'] ?? '--',
                'dayIcon' => $forecast['Day']['Icon'] ?? null,
                'nightIcon' => $forecast['Night']['Icon'] ?? null,
                'dayRainProb' => $forecast['Day']['RainProbability'] ?? null,
                'nightRainProb' => $forecast['Night']['RainProbability'] ?? null
            ];
        }

        return $forecasts;
    }

    public function getWeatherIconUrl($iconNumber)
    {
        if (!$iconNumber) return null;

        // Map AccuWeather icon numbers to OpenWeatherMap icons (more reliable)
        $iconMap = [
            1 => '01d', 2 => '02d', 3 => '02d', 4 => '02d', 5 => '01d', 6 => '03d',
            7 => '04d', 8 => '04d', 11 => '50d', 12 => '09d', 13 => '10d', 14 => '10d',
            15 => '11d', 16 => '11d', 17 => '11d', 18 => '09d', 19 => '13d', 20 => '13d',
            21 => '13d', 22 => '13d', 23 => '13d', 24 => '50d', 25 => '13d', 26 => '09d',
            29 => '13d', 30 => '01d', 31 => '01d', 32 => '01d', 33 => '02n', 34 => '02n',
            35 => '03n', 36 => '04n', 37 => '50n', 38 => '04n', 39 => '10n', 40 => '10n',
            41 => '11n', 42 => '11n', 43 => '13n', 44 => '13n'
        ];

        $openWeatherIcon = $iconMap[$iconNumber] ?? '01d';
        return "https://openweathermap.org/img/wn/{$openWeatherIcon}@2x.png";
    }

    public function render()
    {
        return view('livewire.weather-search')
            ->layout('layouts.app');
    }
}
