@props(['label', 'value', 'icon' => null, 'color' => 'indigo'])

<div class="bg-white overflow-hidden shadow-sm sm:rounded-lg hover:shadow-md transition-shadow duration-200">
    <div class="p-4">
        <div class="flex items-center space-x-4">
            @if($icon)
                <div class="flex-shrink-0 bg-{{ $color }}-500 rounded-md w-8 h-8 flex items-center justify-center">
                    <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        {!! $icon !!}
                    </svg>
                </div>
            @endif
            <div>
                <div class="text-xs font-medium text-gray-500 uppercase tracking-wide">{{ $label }}</div>
                <div class="mt-1 text-lg font-semibold text-gray-900">{{ $value }}</div>
            </div>
        </div>
    </div>
</div>
