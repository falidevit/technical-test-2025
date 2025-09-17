 @if($currentWeather && $selectedCity)
 <div class="max-w-4xl mx-auto mt-8 space-y-6">
     <!-- Current Weather -->
     <div class="bg-white/20 backdrop-blur-md rounded-2xl shadow-xl p-8">
         <!-- City Name and Temperature Toggle -->
         <div class="text-center mb-6">
             <div class="flex justify-between items-start mb-4">
                 <div></div>
                 <div>
                     <h2 class="text-3xl font-bold text-white mb-2">
                         {{ $selectedCity['LocalizedName'] }}
                     </h2>
                     <p class="text-white/80">
                         {{ $selectedCity['AdministrativeArea']['LocalizedName'] ?? '' }}, {{ $selectedCity['Country']['LocalizedName'] }}
                     </p>
                 </div>
                 <button
                     wire:click="toggleTemperatureUnit"
                     class="bg-white/20 hover:bg-white/30 text-white px-3 py-1 rounded-lg text-sm transition-colors"
                 >
                     {{ $useCelsius ? '°F' : '°C' }}
                 </button>
             </div>
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
                 <div class="flex justify-center mb-3">
                     <img src="https://openweathermap.org/img/wn/09d@2x.png"
                          alt="Humidity"
                          class="w-12 h-12 opacity-80">
                 </div>
                 <div class="text-white/70 text-sm mb-1">Humidity</div>
                 <div class="text-white font-semibold text-xl">{{ $this->getHumidity() }}%</div>
             </div>

             <div class="bg-white/10 rounded-xl p-4 text-center">
                 <div class="flex justify-center mb-3">
                     <img src="https://openweathermap.org/img/wn/03d@2x.png"
                          alt="Wind Speed"
                          class="w-12 h-12 opacity-80">
                 </div>
                 <div class="text-white/70 text-sm mb-1">Wind Speed</div>
                 <div class="text-white font-semibold text-lg leading-tight">{{ $this->getWindSpeed() }}</div>
             </div>

             <div class="bg-white/10 rounded-xl p-4 text-center">
                 <div class="flex justify-center mb-3">
                     <img src="https://openweathermap.org/img/wn/02d@2x.png"
                          alt="UV Index"
                          class="w-12 h-12 opacity-80">
                 </div>
                 <div class="text-white/70 text-sm mb-1">UV Index</div>
                 <div class="text-white font-semibold text-xl">{{ $this->getUVIndex() }}</div>
             </div>

             <div class="bg-white/10 rounded-xl p-4 text-center">
                 <div class="flex justify-center mb-3">
                     <img src="https://openweathermap.org/img/wn/04d@2x.png"
                          alt="Visibility"
                          class="w-12 h-12 opacity-80">
                 </div>
                 <div class="text-white/70 text-sm mb-1">Visibility</div>
                 <div class="text-white font-semibold text-lg leading-tight">{{ $this->getVisibility() }}</div>
             </div>
         </div>

         <!-- Last Updated -->
         @if(isset($currentWeather['LocalObservationDateTime']))
             <div class="text-center mt-6 text-white/60 text-sm">
                 Last updated: {{ \Carbon\Carbon::parse($currentWeather['LocalObservationDateTime'])->format('M j, Y g:i A') }}
             </div>
         @endif
     </div>

     <!-- Today's Forecast - Horizontal Timeline Design -->
     <div class="bg-gradient-to-r from-blue-500/30 via-purple-500/30 to-pink-500/30 backdrop-blur-md rounded-2xl shadow-xl p-6 border border-white/20">
         <div class="flex items-center justify-between mb-6">
             <h3 class="text-xl font-bold text-white">Today's Forecast</h3>
             <div class="text-white/60 text-sm">4 periods</div>
         </div>

         @php
             $periods = $this->getFourPeriods();
             $periodLabels = [
                 'morning' => 'Matin',
                 'afternoon' => 'Après-midi',
                 'evening' => 'Soir',
                 'night' => 'Pendant la nuit'
             ];
             $periodIcons = [
                 'morning' => '☀️',
                 'afternoon' => '🌤️',
                 'evening' => '🌅',
                 'night' => '🌙'
             ];
         @endphp

         <!-- Mobile -->
         <div class="block md:hidden space-y-3">
             @foreach(['morning', 'afternoon', 'evening', 'night'] as $periodKey)
                 @php $period = $periods[$periodKey]; @endphp
                 <div class="bg-white/15 rounded-xl p-4 flex items-center space-x-4">
                     <div class="text-2xl">{{ $periodIcons[$periodKey] }}</div>
                     <div class="flex-1">
                         <div class="text-white font-medium text-sm">{{ $periodLabels[$periodKey] }}</div>
                         <div class="text-white/70 text-xs">{{ $period['condition'] }}</div>
                     </div>
                     <div class="text-right">
                         <div class="text-white font-bold text-lg">{{ $period['temp'] }}</div>
                         @if($period['rainProb'] !== null)
                             <div class="text-blue-200 text-xs">{{ $period['rainProb'] }}%</div>
                         @endif
                     </div>
                 </div>
             @endforeach
         </div>

         <!-- Desktop -->
         <div class="hidden md:block">
             <div class="relative">
                 <!-- Timeline line -->
                 <div class="absolute top-1/2 left-0 right-0 h-px bg-gradient-to-r from-white/30 via-white/60 to-white/30 transform -translate-y-1/2"></div>

                 <div class="grid grid-cols-4 gap-4">
                     @foreach(['morning', 'afternoon', 'evening', 'night'] as $index => $periodKey)
                         @php $period = $periods[$periodKey]; @endphp
                         <div class="relative">
                             <!-- Timeline dot -->
                             <div class="absolute top-1/2 left-1/2 w-3 h-3 bg-white rounded-full transform -translate-x-1/2 -translate-y-1/2 z-10"></div>

                             <!-- Period card -->
                             <div class="bg-white/10 rounded-xl p-4 text-center mt-8 hover:bg-white/20 transition-all duration-300">
                                 <div class="text-2xl mb-2">{{ $periodIcons[$periodKey] }}</div>
                                 <div class="text-white/80 text-xs font-medium mb-2">{{ $periodLabels[$periodKey] }}</div>

                                 @if($period['icon'])
                                     <div class="flex justify-center mb-2">
                                         <img src="{{ $this->getWeatherIconUrl($period['icon']) }}"
                                              alt="{{ $period['condition'] }}"
                                              class="w-8 h-8 opacity-80">
                                     </div>
                                 @endif

                                 <div class="text-xl font-bold text-white mb-1">{{ $period['temp'] }}</div>
                                 <div class="text-white/70 text-xs mb-2 h-8 flex items-center justify-center">{{ $period['condition'] }}</div>
                                 @if($period['rainProb'] !== null)
                                     <div class="text-blue-200 text-xs">💧 {{ $period['rainProb'] }}%</div>
                                 @endif
                             </div>
                         </div>
                     @endforeach
                 </div>
             </div>
         </div>
     </div>

     <!-- 5-Day Forecast - Card List Design -->
     @php $fiveDayForecast = $this->getFiveDayForecast(); @endphp
     @if(!empty($fiveDayForecast))
         <div class="bg-black/10 backdrop-blur-md rounded-2xl shadow-xl overflow-hidden border border-white/10">
             <div class="bg-white/20 bg-white/20 backdrop-blur-md p-6 border-b border-white/10">
                 <h3 class="text-xl font-bold text-white flex items-center">
                     <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                         <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path>
                     </svg>
                     5-Day Forecast
                 </h3>
                 <p class="text-white/70 text-sm mt-1">Weekly weather outlook</p>
             </div>

             <!-- Mobile: Vertical list -->
             <div class="block md:hidden">
                 @foreach($fiveDayForecast as $index => $day)
                     <div class="flex items-center p-4 {{ $index !== count($fiveDayForecast) - 1 ? 'border-b border-white/10' : '' }} hover:bg-white/5 transition-colors">
                         <div class="flex-shrink-0 w-16 text-center">
                             <div class="text-white font-medium text-sm">
                                 {{ $index === 0 ? 'Today' : $day['dayName'] }}
                             </div>
                             <div class="text-white/50 text-xs">{{ $day['date'] }}</div>
                         </div>

                         @if($day['dayIcon'])
                             <div class="flex-shrink-0 mx-4">
                                 <img src="{{ $this->getWeatherIconUrl($day['dayIcon']) }}"
                                      alt="{{ $day['dayCondition'] }}"
                                      class="w-10 h-10">
                             </div>
                         @endif

                         <div class="flex-1 min-w-0">
                             <div class="text-white/90 text-sm font-medium truncate">{{ $day['dayCondition'] }}</div>
                             @if($day['dayRainProb'] !== null)
                                 <div class="text-blue-300 text-xs">💧 {{ $day['dayRainProb'] }}%</div>
                             @endif
                         </div>

                         <div class="flex-shrink-0 text-right">
                             <div class="text-white font-bold text-lg">{{ $day['maxTemp'] }}</div>
                             <div class="text-white/60 text-sm">{{ $day['minTemp'] }}</div>
                         </div>
                     </div>
                 @endforeach
             </div>

             <!-- Desktop: Enhanced cards -->
             <div class="hidden md:block p-6">
                 <div class="grid grid-cols-5 gap-4">
                     @foreach($fiveDayForecast as $index => $day)
                         <div class="group relative">
                             <!-- Today special styling -->
                             @if($index === 0)
                                 <div class="absolute inset-0 bg-gradient-to-br from-orange-400/20 to-red-500/20 rounded-xl blur-sm"></div>
                                 <div class="relative bg-white/15 rounded-xl p-4 text-center border-2 border-orange-400/30 hover:border-orange-400/50 transition-all duration-300">
                                     <div class="absolute -top-2 left-1/2 transform -translate-x-1/2">
                                         <span class="bg-orange-500 text-white text-xs px-2 py-1 rounded-full font-medium">Today</span>
                                     </div>
                             @else
                                 <div class="bg-white/8 rounded-xl p-4 text-center hover:bg-white/15 transition-all duration-300 border border-white/10 hover:border-white/20 group-hover:scale-105">
                             @endif

                                 @if($index > 0)
                                     <div class="text-white/80 text-sm font-medium mb-1">{{ $day['dayName'] }}</div>
                                 @endif
                                 <div class="text-white/60 text-xs mb-3 {{ $index === 0 ? 'mt-4' : '' }}">{{ $day['date'] }}</div>

                                 @if($day['dayIcon'])
                                     <div class="flex justify-center mb-3">
                                         <img src="{{ $this->getWeatherIconUrl($day['dayIcon']) }}"
                                              alt="{{ $day['dayCondition'] }}"
                                              class="w-12 h-12 group-hover:scale-110 transition-transform duration-300">
                                     </div>
                                 @endif

                                 <div class="space-y-1">
                                     <div class="flex justify-between items-center">
                                         <span class="text-white/60 text-xs">Max</span>
                                         <span class="text-white font-bold text-lg">{{ $day['maxTemp'] }}</span>
                                     </div>
                                     <div class="flex justify-between items-center">
                                         <span class="text-white/60 text-xs">Min</span>
                                         <span class="text-white/70 text-sm">{{ $day['minTemp'] }}</span>
                                     </div>
                                 </div>

                                 <div class="mt-3 pt-3 border-t border-white/10">
                                     <div class="text-white/90 text-xs mb-2 h-8 flex items-center justify-center leading-tight">{{ $day['dayCondition'] }}</div>
                                     @if($day['dayRainProb'] !== null)
                                         <div class="text-blue-300 text-xs flex items-center justify-center">
                                             <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                                 <path d="M10 2.5C5.858 2.5 2.5 5.858 2.5 10s3.358 7.5 7.5 7.5 7.5-3.358 7.5-7.5S14.142 2.5 10 2.5zM8.75 13.75L6.25 11.25l1.06-1.06L8.75 11.627l4.44-4.44 1.06 1.06L8.75 13.75z"/>
                                             </svg>
                                             {{ $day['dayRainProb'] }}%
                                         </div>
                                     @endif
                                 </div>
                             </div>
                         </div>
                     @endforeach
                 </div>
             </div>
         </div>
     @endif
 </div>
@endif
