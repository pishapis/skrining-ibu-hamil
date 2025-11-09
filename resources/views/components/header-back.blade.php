<div class="md:hidden relative flex items-center justify-center mt-5 py-2">
  <!-- Tombol Back (kiri) -->
  <button
    type="button"
    class="absolute left-0 top-1/2 -translate-y-1/2 p-2 rounded-lg active:scale-95"
    aria-label="Kembali"
    onclick="document.referrer ? history.back() : location.assign('{{ url('/') }}')"
  >
    <i class="fa-solid fa-arrow-left fa-lg"></i>
  </button>

  <!-- Judul (center) -->
  <span class="text-base font-semibold max-w-[70%] truncate text-center">
    {{ $slot }}
  </span>
</div>
