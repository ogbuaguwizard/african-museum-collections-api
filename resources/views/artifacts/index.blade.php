<x-layout title="Artifact Collection">
    <div class="flex justify-between items-center mb-6">
        <h1 class="text-2xl font-semibold text-gray-800">Artifact Collection</h1>
        <span class="text-sm text-gray-500">{{ $artifacts->total() }} records</span>
    </div>

    <!-- Search & Filter -->
    <form method="GET" class="mb-8 flex flex-col sm:flex-row gap-4">
        <input type="text" name="search" placeholder="Search artifacts, dynasties, cultures..." value="{{ request('search') }}"
               class="flex-1 rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
        <select name="source" class="rounded-lg border-gray-300 shadow-sm focus:border-blue-500 focus:ring-blue-500">
            <option value="">All Sources</option>
            <option value="met" @selected(request('source') == 'met')>Metropolitan Museum</option>
            <option value="smithsonian" @selected(request('source') == 'smithsonian')>Smithsonian</option>
        </select>
        <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition">
            Search
        </button>
    </form>

    <!-- Grid -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        @forelse ($artifacts as $artifact)
            <a href="{{ route('artifacts.show', $artifact) }}" class="bg-white rounded-xl shadow-sm hover:shadow-md transition overflow-hidden border border-gray-100 group">
                @if ($artifact->thumbnail)
                    <img src="{{ $artifact->thumbnail }}" alt="{{ $artifact->displayTitle }}" 
                         class="w-full h-48 object-cover bg-gray-100 group-hover:scale-105 transition duration-300">
                @else
                    <div class="w-full h-48 bg-gray-200 flex items-center justify-center text-gray-400 text-sm">
                        No image available
                    </div>
                @endif
                <div class="p-4">
                    <h3 class="font-semibold text-gray-800 truncate">{{ $artifact->displayTitle }}</h3>
                    <p class="text-sm text-gray-600 truncate">{{ $artifact->displayArtist }}</p>
                    
                    <div class="mt-2 flex items-center justify-between text-xs">
                        <span class="bg-amber-50 text-amber-800 px-2 py-1 rounded border border-amber-200 truncate max-w-[60%]">
                            {{ $artifact->heritageContext }}
                        </span>
                        <span class="text-gray-400 uppercase tracking-wider">{{ $artifact->sourceLabel }}</span>
                    </div>
                    
                    @if($artifact->country)
                        <p class="text-xs text-gray-400 mt-1">📍 {{ $artifact->country }}</p>
                    @endif
                </div>
            </a>
        @empty
            <div class="col-span-full text-center py-12 text-gray-400">
                No artifacts found. Import some data first!
            </div>
        @endforelse
    </div>

    <div class="mt-8">
        {{ $artifacts->appends(request()->query())->links() }}
    </div>
</x-layout>