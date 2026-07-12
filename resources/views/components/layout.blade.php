<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'African Museum Collections API' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    {{ $head ?? '' }}
</head>
<body class="bg-gray-50 font-sans antialiased">
    <nav class="bg-white shadow-sm border-b border-gray-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <a href="{{ route('artifacts.index') }}" class="text-xl font-bold text-gray-800">
                        🌍 African Museum Collections
                    </a>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-sm text-gray-500">API v1</span>
                </div>
            </div>
        </div>
    </nav>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {{ $slot }}
    </main>

    <footer class="bg-white border-t border-gray-200 mt-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-6 text-center text-sm text-gray-500">
            Built with Laravel • Aggregating African cultural heritage
        </div>
    </footer>
</body>
</html>