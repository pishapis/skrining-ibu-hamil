<x-app-layout>
    @section('page_title', 'Profile')
    <x-slot name="title">Profile | Skrining Ibu Hamil</x-slot>

    <x-header-back>Profile</x-header-back>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
            <div class="p-4 sm:p-8 bg-white shadow rounded-lg">
                <div class="max-w-7xl">
                    @if (Auth::user()->role_id != 3)
                    @include('profile.partials.update-profile-information-form')
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
            async function filterKota(provId) {
                try {
                    const requestData = {
                        provId: provId
                    };
                    const routeUrl = "{{ route('kota.filter') }}";
                    const fetchKota = new Fetch(routeUrl);
                    fetchKota.method = 'GET';
                    fetchKota.bodyObject = requestData;
                    const hasil = await fetchKota.run();
                    if (hasil.ack === "ok") {

                        const kotaSelect = document.querySelector('select[name="kota_id"]');
                        kotaSelect.innerHTML = '<option value="" selected disabled>Pilih Kabupaten/Kota</option>';
                        hasil.data.forEach(kota => {
                            kotaSelect.innerHTML += `<option value="${kota.code}">${kota.name}</option>`;
                        });
                    } else {
                        ALERT(hasil.message, hasil.ack);
                    }
                } catch (error) {
                    console.log("🚀 ~ filterKota ~ error:", error);
                }
            }

            async function filterKec(kotaId) {
                try {
                    const requestData = {
                        kotaId: kotaId
                    };
                    const routeUrl = "{{ route('kecamatan.filter') }}";
                    const fetchKec = new Fetch(routeUrl);
                    fetchKec.method = 'GET';
                    fetchKec.bodyObject = requestData;
                    const hasil = await fetchKec.run();
                    if (hasil.ack === "ok") {

                        const kecSelect = document.querySelector('select[name="kec_id"]');
                        kecSelect.innerHTML = '<option value="" selected disabled>Pilih Kecamatan</option>';
                        hasil.data.forEach(kec => {
                            kecSelect.innerHTML += `<option value="${kec.code}">${kec.name}</option>`;
                        });

                    } else {
                        ALERT(hasil.message, hasil.ack);
                    }
                } catch (error) {
                    console.log("🚀 ~ filterKec ~ error:", error);
                }
            }

            async function filterKel(kecId) {
                try {
                    const requestData = {
                        kecId: kecId
                    };
                    const routeUrl = "{{ route('desa.filter') }}";
                    const fetchKel = new Fetch(routeUrl);
                    fetchKel.method = 'GET';
                    fetchKel.bodyObject = requestData;
                    const hasil = await fetchKel.run();
                    if (hasil.ack === "ok") {
                        const kelurahan = hasil.data.kelurahan;
                        const puskesmas = hasil.data.puskesmas;

                        const kelSelect = document.querySelector('select[name="kelurahan_id"]');
                        kelSelect.innerHTML = '<option value="" selected disabled>Pilih Kelurahan</option>';
                        kelurahan.forEach(kel => {
                            kelSelect.innerHTML += `<option value="${kel.code}">${kel.name}</option>`;
                        });

                        const puskesmasSelect = document.querySelector('select[name="puskesmas_id"]');
                        puskesmasSelect.innerHTML = '<option value="" selected disabled>Pilih Puskesmas</option>';
                        puskesmas.forEach(puskesmas => {
                            puskesmasSelect.innerHTML += `<option value="${puskesmas.id}">${puskesmas.nama}</option>`;
                        });
                    } else {
                        ALERT(hasil.message, hasil.ack);
                    }
                } catch (error) {
                    console.log("🚀 ~ filterKel ~ error:", error);
                }
            }

            async function filterFaskesRujukan() {
                try {
                    const kota = document.querySelector('select[name="kota_id"]').value;
                    const requestData = {
                        kota_id: kota
                    };

                    const routeUrl = "{{ route('faskes.filter') }}";
                    const fetchKel = new Fetch(routeUrl);
                    fetchKel.method = 'GET';
                    fetchKel.bodyObject = requestData;

                    const hasil = await fetchKel.run();

                    if (hasil.ack === "ok") {
                        const faskesSelect = document.querySelector('select[name="faskes_rujukan_id"]');

                        // reset isi select
                        faskesSelect.innerHTML = '';
                        const defaultOpt = new Option('Pilih Rujukan', '', true, false);
                        defaultOpt.disabled = true;
                        faskesSelect.add(defaultOpt);

                        const seen = new Set(); // untuk mendeteksi duplikat
                        const frag = document.createDocumentFragment();

                        (hasil.data || []).forEach(item => {
                            const f = item;
                            if (!f?.id || !f?.nama) return;

                            // kunci deduplikasi: id (paling aman)
                            const key = String(f.id);
                            if (seen.has(key)) return;

                            seen.add(key);
                            frag.appendChild(new Option(f.nama, f.id));
                        });

                        faskesSelect.appendChild(frag);
                    } else {
                        ALERT(hasil.message, hasil.ack);
                    }
                } catch (error) {
                    console.log("🚀 ~ filterFaskesRujukan ~ error:", error);
                }
            }
        </script>
    </x-slot>
</x-app-layout>