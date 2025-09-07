@if (session('success'))
<div x-data="{ showMessage: @if (session('success')) true @else false @endif, message: '{{ session('success') }}' }" x-init="if (showMessage) { setTimeout(() => { showMessage = false }, 2000) }">

    <!-- Pesan Success -->
    <div x-show="showMessage" x-transition:enter="animate__animated animate__fadeInRight"
        x-transition:leave="animate__animated animate__fadeOutRight"
        class="bg-green-600 text-white p-2 px-4 rounded-lg animate__animated animate__fadeInRight fixed top-20 right-2">
        {{ session('success') }}
    </div>
</div>
@endif
@if (session('error'))
<div x-data="{ showMessage: @if (session('error')) true @else false @endif, message: '{{ session('error') }}' }" x-init="if (showMessage) { setTimeout(() => { showMessage = false }, 2000) }">

    <!-- Pesan error -->
    <div x-show="showMessage" x-transition:enter="animate__animated animate__fadeInRight"
        x-transition:leave="animate__animated animate__fadeOutRight"
        class="bg-red-600 text-white p-2 px-4 rounded-lg animate__animated animate__fadeInRight fixed top-20 right-2">
        {{ session('error') }}
    </div>
</div>
@endif
@if (session('warning'))
<div x-data="{ showMessage: @if (session('warning')) true @else false @endif, message: '{{ session('warning') }}' }" x-init="if (showMessage) { setTimeout(() => { showMessage = false }, 2000) }">

    <!-- Pesan warning -->
    <div x-show="showMessage" x-transition:enter="animate__animated animate__fadeInRight"
        x-transition:leave="animate__animated animate__fadeOutRight"
        class="bg-yellow-600 text-white p-2 px-4 rounded-lg animate__animated animate__fadeInRight fixed top-20 right-2">
        {{ session('warning') }}
    </div>
</div>
@endif