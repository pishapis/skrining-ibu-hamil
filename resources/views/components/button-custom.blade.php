@props([
    'type' => 'submit',
    'id'   => $id,
    'onclick' => null
])

<button id="{{ $id }}" onclick="{{ $onclick }}" {{ $attributes->merge(['type' => $type, 'class' => 'w-full items-center px-4 py-2 bg-[#81e6d9] hover:bg-[#6ed1c1] border border-transparent rounded-xl font-semibold text-sm text-slate-800 hover:text-slate-50 uppercase tracking-widest focus:bg-[#6ed1c1] active:bg-[#6ed1c1] focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150 bg-[#81e6d9] text-white']) }}>
    {{ $slot }}
</button>