<x-app-layout>
    @section('page_title', 'Profile')
    <x-slot name="title">Profile | Simkeswa</x-slot>

    <x-header-back>Profile</x-header-back>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow rounded-lg">
                <div class="max-w-7xl">
                    @if (Auth::user()->role_id != 3)
                    @include('profile.partials.update-profile-information-form')
                    @else
                    <div x-data="{ isSubmitting:false }">
                        <form method="post" action="{{ route('profile.update.superadmin') }}" x-on:submit="isSubmitting = true">
                            @method('POST')
                            @csrf
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-gray-700 text-sm font-medium mb-2">Username</label>
                                    <input name="username" type="text" class="input-field"
                                        value="{{ $user->username }}" autocomplete="username" readonly>
                                    <x-input-error :messages="$errors->get('username')" class="mt-2" />
                                </div>
                                <div>
                                    <label class="block text-gray-700 text-sm font-medium mb-2">Email</label>
                                    <input name="email" type="email" class="input-field"
                                        value="{{ $user->email }}" autocomplete="email">
                                    <x-input-error :messages="$errors->get('email')" class="mt-2" />
                                </div>
                            </div>
                            <div class="mt-5 flex justify-end">
                                <button type="submit" class="btn-primary w-full sm:w-auto relative"
                                    :disabled="isSubmitting" :class="isSubmitting ? 'opacity-60 cursor-not-allowed' : ''">
                                    <span x-show="!isSubmitting">Simpan</span>
                                    <span x-show="isSubmitting" class="inline-flex items-center gap-2">
                                        <svg class="animate-spin h-4 w-4" viewBox="0 0 24 24" fill="none">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" class="opacity-25" />
                                            <path d="M4 12a8 8 0 018-8" stroke="currentColor" stroke-width="4" class="opacity-75" />
                                        </svg>
                                        Memproses...
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                    @endif
                </div>
            </div>

            <div class="p-4 sm:p-8 bg-white shadow rounded-lg">
                <div class="max-w-7xl">
                    @include('profile.partials.update-password-form')
                </div>
            </div>

            <div class="max-w-7xl">
                <form method="POST" action="{{ route('logout') }}" class="ml-auto" enctype="multipart/form-data">
                    @csrf
                    <x-danger-button class="px-6">Logout</x-danger-button>
                </form>
            </div>
        </div>
    </div>

    <x-slot name="scripts">
        <script data-swup-reload-script>

        </script>
    </x-slot>
</x-app-layout>