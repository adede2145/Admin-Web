<div {{ $attributes->merge(['class' => 'bg-white overflow-hidden shadow-sm sm:rounded-lg']) }}>
    @if(isset($header))
        <div class="border-b border-gray-200 bg-gray-50 px-4 py-3">
            <h3 class="text-base font-semibold leading-6 text-gray-900">
                {{ $header }}
            </h3>
        </div>
    @endif
    <div class="p-4 sm:p-6">
        {{ $slot }}
    </div>
</div>
