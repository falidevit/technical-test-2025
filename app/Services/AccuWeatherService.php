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
        $cacheKey = "accuweather_cities_" . md5($query);
        
        return Cache::remember($cacheKey, 3600, function () use ($query) {
            try {
                $response = Http::get("{$this->baseUrl}/locations/v1/cities/search", [
                    'apikey' => $this->apiKey,
                    'q' => $query,
                    'language' => 'en-us',
                    'details' => 'false'
                ]);

                if ($response->successful()) {
                    return $response->json();
                }

                Log::error('AccuWeather city search failed', [
                    'status' => $response->status(),
                    'response' => $response->body()
                ]);

                return [];
            } catch (\Exception $e) {
                Log::error('AccuWeather city search exception', [
                    'message' => $e->getMessage(),
                    'query' => $query
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
}