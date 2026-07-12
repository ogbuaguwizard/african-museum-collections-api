<x-layout :title="$artifact->title ?? 'Untitled'">
    <div class="mb-4">
        <a href="{{ route('artifacts.index') }}" class="text-blue-600 hover:underline">&larr; Back to collection</a>
    </div>

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8 p-6">
            <!-- Image -->
            <div>
                @if ($artifact->primary_image_url)
                    <img src="{{ $artifact->primary_image_url }}" alt="{{ $artifact->title ?? 'Untitled' }}" 
                         class="w-full rounded-lg object-cover border border-gray-200">
                @else
                    <div class="w-full h-64 bg-gray-200 rounded-lg flex items-center justify-center text-gray-400">
                        No image available
                    </div>
                @endif
                
                @if ($artifact->additional_images)
                    <div class="grid grid-cols-4 gap-2 mt-4">
                        @foreach (array_slice($artifact->additional_images, 0, 4) as $img)
                            <img src="{{ $img }}" class="rounded border border-gray-200 h-16 w-full object-cover">
                        @endforeach
                    </div>
                @endif
            </div>

            <!-- Metadata -->
            <div>
                <h1 class="text-2xl font-bold text-gray-800">{{ $artifact->title ?? 'Untitled' }}</h1>
                <p class="text-lg text-gray-600">{{ $artifact->artist ?? 'Unknown artist' }}</p>

                <div class="mt-6 space-y-3">
                    <!-- Heritage Fields -->
                    <div class="flex border-b border-gray-100 py-2">
                        <span class="w-32 text-sm font-medium text-gray-500">Dynasty</span>
                        <span class="text-sm text-gray-800 font-medium">{{ $artifact->dynasty ?? 'Not specified' }}</span>
                    </div>
                    <div class="flex border-b border-gray-100 py-2">
                        <span class="w-32 text-sm font-medium text-gray-500">Period</span>
                        <span class="text-sm text-gray-800">{{ $artifact->period ?? 'Not specified' }}</span>
                    </div>
                    <div class="flex border-b border-gray-100 py-2">
                        <span class="w-32 text-sm font-medium text-gray-500">Reign</span>
                        <span class="text-sm text-gray-800">{{ $artifact->reign ?? 'Not specified' }}</span>
                    </div>
                    <div class="flex border-b border-gray-100 py-2">
                        <span class="w-32 text-sm font-medium text-gray-500">Culture</span>
                        <span class="text-sm text-gray-800">{{ $artifact->culture ?? 'Not specified' }}</span>
                    </div>
                    <div class="flex border-b border-gray-100 py-2">
                        <span class="w-32 text-sm font-medium text-gray-500">Geography</span>
                        <span class="text-sm text-gray-800">
                            {{ trim(($artifact->country ?? '') . ($artifact->country && $artifact->region ? ', ' : '') . ($artifact->region ?? '')) ?: 'Not recorded' }}
                        </span>
                    </div>
                    
                    <!-- Object Details -->
                    <div class="flex border-b border-gray-100 py-2">
                        <span class="w-32 text-sm font-medium text-gray-500">Date</span>
                        <span class="text-sm text-gray-800">
                            @php
                                $date = $artifact->date_display ?? 'Unknown';
                                if ($artifact->object_begin_date) {
                                    $end = ($artifact->object_end_date && $artifact->object_end_date != $artifact->object_begin_date) 
                                        ? ' - ' . $artifact->object_end_date 
                                        : '';
                                    $date .= " ({$artifact->object_begin_date}{$end})";
                                }
                            @endphp
                            {{ $date }}
                        </span>
                    </div>
                    <div class="flex border-b border-gray-100 py-2">
                        <span class="w-32 text-sm font-medium text-gray-500">Classification</span>
                        <span class="text-sm text-gray-800">{{ $artifact->classification ?? 'Not recorded' }}</span>
                    </div>
                    <div class="flex border-b border-gray-100 py-2">
                        <span class="w-32 text-sm font-medium text-gray-500">Medium</span>
                        <span class="text-sm text-gray-800">{{ $artifact->medium ?? 'Not recorded' }}</span>
                    </div>
                    <div class="flex border-b border-gray-100 py-2">
                        <span class="w-32 text-sm font-medium text-gray-500">Dimensions</span>
                        <span class="text-sm text-gray-800">{{ $artifact->dimensions ?? 'Not recorded' }}</span>
                    </div>
                    
                    <!-- Source -->
                    <div class="flex border-b border-gray-100 py-2">
                        <span class="w-32 text-sm font-medium text-gray-500">Source</span>
                        <span class="text-sm text-gray-800">{{ strtoupper($artifact->source) }}</span>
                    </div>
                </div>

                @if ($artifact->description)
                    <div class="mt-6">
                        <h3 class="font-medium text-gray-700">Description</h3>
                        <p class="text-sm text-gray-600 mt-1 leading-relaxed">{{ $artifact->description }}</p>
                    </div>
                @endif

                @if ($artifact->source_url)
                    <div class="mt-6">
                        <a href="{{ $artifact->source_url }}" target="_blank" 
                           class="inline-block bg-gray-100 hover:bg-gray-200 text-gray-700 px-4 py-2 rounded-lg text-sm transition">
                            View original record &rarr;
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-layout>