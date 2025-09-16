<div>
    @if($currentWeather && $selectedCity)
        <div class="max-w-2xl mx-auto mt-8">
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
        </div>
    @endif
</div>
