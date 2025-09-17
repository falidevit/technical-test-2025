<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AccuWeatherService
{
    private string $apiKey;
    private string $baseUrl = 'http://dataservice.accuweather.com';

    public function __construct()
    {
        $this->apiKey = config('services.accuweather.api_key');
    }

    public function searchCities(string $query): array
    {
        $cacheKey = "accuweather_cities_" . md5(strtolower(trim($query)));
        
        return Cache::remember($cacheKey, 3600, function () use ($query) {
            try {
                Log::info('Making AccuWeather API call', ['query' => $query, 'api_key_present' => !empty($this->apiKey)]);
                
                $response = Http::timeout(10)->get("{$this->baseUrl}/locations/v1/cities/search", [
                    'apikey' => $this->apiKey,
                    'q' => $query,
                    'language' => 'en-us',
                    'details' => 'false'
                ]);

                Log::info('AccuWeather API response', [
                    'status' => $response->status(),
                    'query' => $query,
                    'response_size' => strlen($response->body())
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    Log::info('Cities found', ['count' => count($data), 'query' => $query]);
                    return $data;
                }

                Log::error('AccuWeather city search failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'query' => $query,
                    'url' => "{$this->baseUrl}/locations/v1/cities/search"
                ]);

                return [];
            } catch (\Exception $e) {
                Log::error('AccuWeather city search exception', [
                    'message' => $e->getMessage(),
                    'query' => $query,
                    'trace' => $e->getTraceAsString()
                ]);

                return [];
            }
        });
    }

    public function getCurrentWeather(string $locationKey): ?array
    {
        $cacheKey = "accuweather_current_" . $locationKey;
        
        return Cache::remember($cacheKey, 600, function () use ($locationKey) {
            try {
                $response = Http::get("{$this->baseUrl}/currentconditions/v1/{$locationKey}", [
                    'apikey' => $this->apiKey,
                    'language' => 'en-us',
                    'details' => 'true'
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    return $data[0] ?? null;
                }

                Log::error('AccuWeather current weather failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'location_key' => $locationKey
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('AccuWeather current weather exception', [
                    'message' => $e->getMessage(),
                    'location_key' => $locationKey
                ]);

                return null;
            }
        });
    }

    public function formatTemperature(array $temperature): string
    {
        $metric = $temperature['Metric'] ?? null;
        $imperial = $temperature['Imperial'] ?? null;

        if ($metric) {
            return round($metric['Value']) . '°C';
        }

        if ($imperial) {
            return round($imperial['Value']) . '°F';
        }

        return 'N/A';
    }

    public function formatWindSpeed(array $wind): string
    {
        $speed = $wind['Speed']['Metric'] ?? null;
        
        if ($speed) {
            return round($speed['Value']) . ' ' . $speed['Unit'];
        }

        return 'N/A';
    }

    public function getDailyForecast(string $locationKey): ?array
    {
        $cacheKey = "accuweather_daily_" . $locationKey;
        
        return Cache::remember($cacheKey, 3600, function () use ($locationKey) {
            try {
                Log::info('Making AccuWeather daily forecast API call', ['location_key' => $locationKey]);
                
                $response = Http::timeout(10)->get("{$this->baseUrl}/forecasts/v1/daily/5day/{$locationKey}", [
                    'apikey' => $this->apiKey,
                    'language' => 'en-us',
                    'details' => 'true',
                    'metric' => 'true'
                ]);

                Log::info('AccuWeather daily forecast response', [
                    'status' => $response->status(),
                    'location_key' => $locationKey,
                    'response_size' => strlen($response->body())
                ]);

                if ($response->successful()) {
                    $data = $response->json();
                    Log::info('Daily forecast found', ['location_key' => $locationKey]);
                    return $data;
                }

                Log::error('AccuWeather daily forecast failed', [
                    'status' => $response->status(),
                    'response' => $response->body(),
                    'location_key' => $locationKey
                ]);

                return null;
            } catch (\Exception $e) {
                Log::error('AccuWeather daily forecast exception', [
                    'message' => $e->getMessage(),
                    'location_key' => $locationKey,
                    'trace' => $e->getTraceAsString()
                ]);

                return null;
            }
        });
    }
}