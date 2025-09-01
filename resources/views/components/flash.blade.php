@if (session('success'))
  <div class="mb-4 rounded-lg bg-green-100 p-3 text-green-900">{{ session('success') }}</div>
@endif
@if (session('error'))
  <div class="mb-4 rounded-lg bg-red-100 p-3 text-red-900">{{ session('error') }}</div>
@endif