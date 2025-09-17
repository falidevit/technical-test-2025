<div>
    @section('title', 'Weather App - Current Weather Conditions')

    <div class="min-h-screen bg-gradient-to-br from-blue-400 via-blue-500 to-blue-600">
        <!-- Header -->
        <div class="container mx-auto px-4 py-8">
            <h1 class="text-4xl font-bold text-white text-center mb-8">Weather App</h1>

            <!-- Search Section -->
            <div class="max-w-md mx-auto relative">
                <div class="relative">
                    <input
                        type="text"
                        wire:model.live.debounce.500ms="search"
                        placeholder="Search for a city..."
                        class="w-full px-4 py-3 pl-12 pr-10 text-gray-900 bg-white/90 backdrop-blur-sm rounded-xl border-0 shadow-lg focus:ring-2 focus:ring-white/50 focus:outline-none transition-all"
                    >
                    <svg class="absolute left-4 top-1/2 transform -translate-y-1/2 w-5 h-5 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                    </svg>

                    @if($search)
                        <button
                            wire:click="clearSearch"
                            class="absolute right-3 top-1/2 transform -translate-y-1/2 w-6 h-6 text-gray-500 hover:text-gray-700 transition-colors"
                        >
                            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    @endif
                </div>

                <!-- Loading Indicator -->
                @if($isLoading)
                    <div class="absolute top-full left-0 right-0 mt-1 bg-white/90 backdrop-blur-sm rounded-lg shadow-lg p-4 text-center">
                        <div class="inline-flex items-center">
                            <svg class="animate-spin -ml-1 mr-3 h-5 w-5 text-blue-500" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                            Loading...
                        </div>
                    </div>
                @endif

                <!-- City Suggestions -->
                @if(!empty($cities) && !$isLoading)
                    <div class="absolute top-full left-0 right-0 mt-1 bg-white/95 backdrop-blur-sm rounded-lg shadow-lg max-h-60 overflow-y-auto z-10">
                        @foreach($cities as $city)
                            <button
                                wire:click="selectCity({{ json_encode($city) }})"
                                class="w-full px-4 py-3 text-left hover:bg-blue-50 transition-colors border-b border-gray-100 last:border-b-0"
                            >
                                <div class="font-medium text-gray-900">{{ $city['LocalizedName'] }}</div>
                                <div class="text-sm text-gray-600">
                                    {{ $city['AdministrativeArea']['LocalizedName'] ?? '' }}, {{ $city['Country']['LocalizedName'] }}
                                </div>
                            </button>
                        @endforeach
                    </div>
                @endif

                <!-- No Results -->
                @if(empty($cities) && strlen($search) >= 2 && !$isLoading && !$selectedCity)
                    <div class="absolute top-full left-0 right-0 mt-1 bg-white/90 backdrop-blur-sm rounded-lg shadow-lg p-4 text-center text-gray-600">
                        No cities found for "{{ $search }}"
                    </div>
                @endif
            </div>

            <!-- Error Message -->
            @if($error)
                <div class="max-w-md mx-auto mt-4 bg-red-100 border-l-4 border-red-500 text-red-700 p-4 rounded">
                    <div class="flex">
                        <div class="flex-shrink-0">
                            <svg class="h-5 w-5 text-red-400" viewBox="0 0 20 20" fill="currentColor">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                            </svg>
                        </div>
                        <div class="ml-3">
                            <p class="text-sm">{{ $error }}</p>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Weather Display -->
            @if($currentWeather && $selectedCity)
                <div class="max-w-4xl mx-auto mt-8 space-y-6">
                    <!-- Current Weather -->
                    <div class="bg-white/20 backdrop-blur-md rounded-2xl shadow-xl p-8">
                        <!-- City Name -->
                        <div class="text-center mb-6">
                            <h2 class="text-3xl font-bold text-white mb-2">
                                {{ $selectedCity['LocalizedName'] }}
                            </h2>
                            <p class="text-white/80">
                                {{ $selectedCity['AdministrativeArea']['LocalizedName'] ?? '' }}, {{ $selectedCity['Country']['LocalizedName'] }}
                            </p>
                        </div>

                        <!-- Main Weather Info -->
                        <div class="text-center mb-8">
                            @if($this->getCurrentWeatherIcon())
                                <div class="flex justify-center mb-4">
                                    <img src="{{ $this->getWeatherIconUrl($this->getCurrentWeatherIcon()) }}" 
                                         alt="{{ $this->getWeatherText() }}"
                                         class="w-20 h-20">
                                </div>
                            @endif
                            
                            <div class="text-6xl font-light text-white mb-2">
                                {{ $this->getTemperature() }}
                            </div>
                            <div class="text-xl text-white/90 capitalize">
                                {{ $this->getWeatherText() }}
                            </div>
                        </div>

                        <!-- Weather Details Grid -->
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                            <div class="bg-white/10 rounded-xl p-4 text-center">
                                <div class="text-white/70 text-sm mb-1">Humidity</div>
                                <div class="text-white font-semibold">{{ $this->getHumidity() }}%</div>
                            </div>

                            <div class="bg-white/10 rounded-xl p-4 text-center">
                                <div class="text-white/70 text-sm mb-1">Wind Speed</div>
                                <div class="text-white font-semibold">{{ $this->getWindSpeed() }}</div>
                            </div>

                            <div class="bg-white/10 rounded-xl p-4 text-center">
                                <div class="text-white/70 text-sm mb-1">UV Index</div>
                                <div class="text-white font-semibold">{{ $this->getUVIndex() }}</div>
                            </div>

                            <div class="bg-white/10 rounded-xl p-4 text-center">
                                <div class="text-white/70 text-sm mb-1">Visibility</div>
                                <div class="text-white font-semibold">{{ $this->getVisibility() }}</div>
                            </div>
                        </div>

                        <!-- Last Updated -->
                        @if(isset($currentWeather['LocalObservationDateTime']))
                            <div class="text-center mt-6 text-white/60 text-sm">
                                Last updated: {{ \Carbon\Carbon::parse($currentWeather['LocalObservationDateTime'])->format('M j, Y g:i A') }}
                            </div>
                        @endif
                    </div>

                    <!-- Daily Forecast -->
                    <div class="bg-white/20 backdrop-blur-md rounded-2xl shadow-xl p-8">
                        <h3 class="text-2xl font-bold text-white mb-6 text-center">Today's Forecast</h3>
                        
                        @php
                            $dayData = $this->getDayData();
                        @endphp

                        <div class="grid grid-cols-2 gap-4 md:gap-6">
                            <!-- Day -->
                            <div class="bg-white/10 rounded-xl p-6 text-center">
                                <div class="text-white/80 text-sm font-medium mb-3">Day</div>
                                
                                @if($dayData['dayIcon'])
                                    <div class="flex justify-center mb-3">
                                        <img src="{{ $this->getWeatherIconUrl($dayData['dayIcon']) }}" 
                                             alt="{{ $dayData['dayCondition'] }}"
                                             class="w-12 h-12">
                                    </div>
                                @endif
                                
                                <div class="text-3xl font-light text-white mb-2">{{ $dayData['maxTemp'] }}</div>
                                <div class="text-white/90 text-sm mb-3">{{ $dayData['dayCondition'] }}</div>
                                <div class="w-full h-px bg-white/20 my-3"></div>
                                <div class="text-white/60 text-xs">
                                    @if($dayData['dayRainProbability'] !== null)
                                        Rain probability {{ $dayData['dayRainProbability'] }}%
                                    @else
                                        --
                                    @endif
                                </div>
                            </div>

                            <!-- Night -->
                            <div class="bg-white/10 rounded-xl p-6 text-center">
                                <div class="text-white/80 text-sm font-medium mb-3">Night</div>
                                
                                @if($dayData['nightIcon'])
                                    <div class="flex justify-center mb-3">
                                        <img src="{{ $this->getWeatherIconUrl($dayData['nightIcon']) }}" 
                                             alt="{{ $dayData['nightCondition'] }}"
                                             class="w-12 h-12">
                                    </div>
                                @endif
                                
                                <div class="text-3xl font-light text-white mb-2">{{ $dayData['minTemp'] }}</div>
                                <div class="text-white/90 text-sm mb-3">{{ $dayData['nightCondition'] }}</div>
                                <div class="w-full h-px bg-white/20 my-3"></div>
                                <div class="text-white/60 text-xs">
                                    @if($dayData['nightRainProbability'] !== null)
                                        Rain probability {{ $dayData['nightRainProbability'] }}%
                                    @else
                                        --
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Temperature Range -->
                        <div class="mt-6 text-center">
                            <div class="text-white/70 text-sm">Temperature Range</div>
                            <div class="text-white text-lg font-medium">
                                {{ $dayData['minTemp'] }} - {{ $dayData['maxTemp'] }}
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Welcome Message -->
            @if(!$selectedCity && !$search)
                <div class="max-w-md mx-auto mt-16 text-center">
                    <div class="bg-white/20 backdrop-blur-md rounded-2xl shadow-xl p-8">
                        <svg class="mx-auto h-16 w-16 text-white/80 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 15a4 4 0 004 4h9a5 5 0 10-.1-9.999 5.002 5.002 0 10-9.78 2.096A4.001 4.001 0 003 15z"></path>
                        </svg>
                        <h3 class="text-xl font-semibold text-white mb-2">Welcome to Weather App</h3>
                        <p class="text-white/80">Search for any city to get current weather conditions and forecasts.</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>