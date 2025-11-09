<x-app-layout>
    @section('page_title', 'Generator Link Skrining')
    <x-slot name="title">Generator Link Skrining | Admin</x-slot>

    <div class="container mx-auto px-4 py-6">
        <div class="max-w-6xl mx-auto">
            <!-- Header -->
            <div class="mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">Generator Link Simkeswa</h1>
                <p class="text-gray-600">Generate QR Code dan link pendek untuk formulir skrining berdasarkan Puskesmas</p>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Form Generator -->
                <div class="lg:col-span-1">
                    <div class="bg-white rounded-xl shadow-lg p-6 sticky top-6">
                        <h2 class="text-xl font-semibold mb-6 text-gray-800 flex items-center">
                            <svg class="w-6 h-6 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            Generate Link Baru
                        </h2>

                        <form id="generateForm" class="space-y-6">
                            @csrf
                            <div>
                                <label for="puskesmas_id" class="block text-sm font-semibold text-gray-700 mb-3">
                                    Pilih Puskesmas
                                </label>
                                <select name="puskesmas_id" id="puskesmas_id" required
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                    <option value="">-- Pilih Puskesmas --</option>
                                    @foreach($puskesmas as $p)
                                    <option value="{{ $p->id }}">{{ $p->nama }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="expires_at" class="block text-sm font-semibold text-gray-700 mb-3">
                                    Tanggal Kedaluwarsa
                                </label>
                                <input type="date" name="expires_at" id="expires_at"
                                    min="{{ date('Y-m-d', strtotime('+1 day')) }}"
                                    class="w-full px-4 py-3 border-2 border-gray-200 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition duration-200">
                                <p class="text-xs text-gray-500 mt-2">Opsional - kosongkan untuk tidak ada batas waktu</p>
                            </div>

                            <button type="submit" id="generateBtn"
                                class="w-full bg-gradient-to-r from-blue-600 to-blue-700 hover:from-blue-700 hover:to-blue-800 text-white font-semibold py-3 px-6 rounded-lg transition duration-200 flex items-center justify-center shadow-lg">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                                </svg>
                                Generate Link & QR Code
                            </button>
                        </form>

                        <!-- Quick Stats -->
                        <div class="mt-8 pt-6 border-t border-gray-200">
                            <h3 class="text-sm font-semibold text-gray-700 mb-4">Statistik Cepat</h3>
                            <div class="grid grid-cols-2 gap-3">
                                <div class="bg-blue-50 rounded-lg p-3 text-center">
                                    <div class="text-lg font-bold text-blue-600" id="quickTotalGenerated">-</div>
                                    <div class="text-xs text-blue-800">Total Link</div>
                                </div>
                                <div class="bg-green-50 rounded-lg p-3 text-center">
                                    <div class="text-lg font-bold text-green-600" id="quickTotalAccess">-</div>
                                    <div class="text-xs text-green-800">Total Akses</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Result Display -->
                <div class="lg:col-span-2">
                    <!-- Results -->
                    <div class="bg-white rounded-xl shadow-lg p-6 mb-6">
                        <h2 class="text-xl font-semibold mb-6 text-gray-800 flex items-center">
                            <svg class="w-6 h-6 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Hasil Generate
                        </h2>

                        <div id="resultContainer" class="hidden">
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <!-- QR Code -->
                                <div class="text-center">
                                    <div class="bg-gray-50 rounded-lg p-6 mb-4">
                                        <img id="qrCodeImage" src="" alt="QR Code" class="mx-auto rounded-lg shadow-md">
                                    </div>
                                    <p class="text-sm text-gray-600 mb-4">Scan QR Code untuk akses langsung</p>

                                    <div class="flex space-x-2">
                                        <button onclick="downloadQR()"
                                            class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 text-sm flex items-center justify-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                                            </svg>
                                            Download
                                        </button>
                                        <button onclick="printQR()"
                                            class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200 text-sm flex items-center justify-center">
                                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                                            </svg>
                                            Print
                                        </button>
                                    </div>
                                </div>

                                <!-- Links & Info -->
                                <div class="space-y-4">
                                    <!-- Short URL -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Link Pendek:</label>
                                        <div class="flex">
                                            <input type="text" id="shortUrl" readonly
                                                class="flex-1 px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-l-lg text-sm font-mono">
                                            <button onclick="copyToClipboard('shortUrl')"
                                                class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white border-2 border-blue-600 rounded-r-lg transition duration-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Original URL -->
                                    <div>
                                        <label class="block text-sm font-semibold text-gray-700 mb-2">Link Asli:</label>
                                        <div class="flex">
                                            <input type="text" id="originalUrl" readonly
                                                class="flex-1 px-4 py-3 bg-gray-50 border-2 border-gray-200 rounded-l-lg text-sm font-mono">
                                            <button onclick="copyToClipboard('originalUrl')"
                                                class="px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white border-2 border-blue-600 rounded-r-lg transition duration-200">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                </svg>
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Info Card -->
                                    <div class="bg-gradient-to-r from-blue-50 to-indigo-50 border border-blue-200 rounded-lg p-4">
                                        <div class="flex items-start">
                                            <svg class="w-5 h-5 text-blue-500 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <div class="text-sm">
                                                <p class="font-semibold text-blue-800 mb-1">Informasi Link</p>
                                                <p class="text-blue-700">Puskesmas: <span id="puskesmasName" class="font-medium"></span></p>
                                                <p class="text-blue-700">Dibuat: <span id="createdAt" class="font-medium"></span></p>
                                                <p class="text-blue-700" id="expiresInfo"></p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div id="noResultMessage" class="text-center text-gray-500 py-12">
                            <svg class="w-16 h-16 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                            </svg>
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Belum Ada Link yang Di-generate</h3>
                            <p>Pilih Puskesmas dan klik tombol generate untuk membuat link skrining</p>
                        </div>
                    </div>

                    <!-- Recent Links -->
                    <div class="bg-white rounded-xl shadow-lg p-6">
                        <h2 class="text-xl font-semibold mb-6 text-gray-800 flex items-center">
                            <svg class="w-6 h-6 text-orange-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Link Terbaru
                        </h2>

                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Puskesmas</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Short Code</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Dibuat</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Akses</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody id="recentLinksTable" class="bg-white divide-y divide-gray-200">
                                    <!-- Data will be loaded via JavaScript -->
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
        <div class="bg-white rounded-xl p-8 text-center shadow-2xl">
            <div class="animate-spin rounded-full h-16 w-16 border-4 border-blue-600 border-t-transparent mx-auto mb-4"></div>
            <p class="text-gray-600 font-medium">Sedang memproses...</p>
        </div>
    </div>

    <!-- QR Code Modal -->
    <div id="qrModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 overflow-auto pb-10">
        <div class="bg-white rounded-xl p-8 max-w-md w-full mx-4 shadow-2xl">
            <div class="flex justify-between items-center mb-6">
                <h3 class="text-xl font-bold text-gray-800">QR Code</h3>
                <button onclick="closeQRModal()" class="text-gray-400 hover:text-gray-600">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
            
            <div class="text-center">
                <div class="bg-gray-50 rounded-lg p-6 mb-4">
                    <img id="modalQRImage" src="" alt="QR Code" class="mx-auto rounded-lg shadow-md w-[200px]">
                </div>
                
                <div class="text-left bg-blue-50 rounded-lg p-4 mb-4">
                    <p class="text-sm text-gray-700 mb-1"><strong>Puskesmas:</strong> <span id="modalPuskesmasName"></span></p>
                    <p class="text-sm text-gray-700 mb-1"><strong>Short URL:</strong> <span id="modalShortUrl" class="font-mono text-xs"></span></p>
                    <p class="text-sm text-gray-700 mb-1"><strong>Dibuat:</strong> <span id="modalCreatedAt"></span></p>
                    <p class="text-sm text-gray-700"><strong>Total Akses:</strong> <span id="modalAccessCount"></span> kali</p>
                </div>
                
                <div class="flex space-x-2">
                    <button onclick="downloadModalQR()" class="flex-1 bg-green-600 hover:bg-green-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path>
                        </svg>
                        Download
                    </button>
                    <button onclick="printModalQR()" class="flex-1 bg-purple-600 hover:bg-purple-700 text-white font-medium py-2 px-4 rounded-lg transition duration-200">
                        <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path>
                        </svg>
                        Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="scripts">
        <script>
            let currentModalData = null;
            let isFormInitialized = false;
            let isSubmitting = false;

            function initializePage() {
                loadStatistics();

                // Prevent double initialization
                const generateForm = document.getElementById('generateForm');
                if (generateForm && !isFormInitialized) {
                    // Remove any existing listeners
                    const newForm = generateForm.cloneNode(true);
                    generateForm.parentNode.replaceChild(newForm, generateForm);
                    
                    newForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        if (!isSubmitting) {
                            generateLink();
                        }
                    });
                    
                    isFormInitialized = true;
                }

                if (window.screeningStatsInterval) {
                    clearInterval(window.screeningStatsInterval);
                }
                window.screeningStatsInterval = setInterval(function() {
                    loadStatistics();
                }, 30000);
            }

            // Only initialize once
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializePage, {once: true});
            } else {
                initializePage();
            }

            function generateLink() {
                const form = document.getElementById('generateForm');
                const formData = new FormData(form);

                const loadingOverlay = document.getElementById('loadingOverlay');
                const generateBtn = document.getElementById('generateBtn');

                loadingOverlay.classList.remove('hidden');
                generateBtn.disabled = true;

                fetch('{{ route("skrining.generate") }}', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            displayResult(data.data);
                            loadStatistics();
                            ALERT('Link dan QR Code berhasil di-generate dan disimpan!', 'ok');
                            form.reset();
                        } else {
                            throw new Error(data.message || 'Terjadi kesalahan saat generate link');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        ALERT(error.message || 'Terjadi kesalahan saat generate link', 'bad');
                    })
                    .finally(() => {
                        loadingOverlay.classList.add('hidden');
                        generateBtn.disabled = false;
                    });
            }

            function displayResult(data) {
                const qrCodeImage = document.getElementById('qrCodeImage');
                const shortUrl = document.getElementById('shortUrl');
                const originalUrl = document.getElementById('originalUrl');
                const puskesmasName = document.getElementById('puskesmasName');
                const createdAt = document.getElementById('createdAt');
                const expiresInfo = document.getElementById('expiresInfo');
                const noResultMessage = document.getElementById('noResultMessage');
                const resultContainer = document.getElementById('resultContainer');

                qrCodeImage.src = 'data:image/png;base64,' + data.qrCode;
                shortUrl.value = data.shortUrl;
                originalUrl.value = data.url;
                puskesmasName.textContent = data.puskesmas_name;
                createdAt.textContent = new Date().toLocaleDateString('id-ID');

                if (data.expires_at) {
                    expiresInfo.textContent = 'Kedaluwarsa: ' + new Date(data.expires_at).toLocaleDateString('id-ID');
                    expiresInfo.style.display = 'block';
                } else {
                    expiresInfo.textContent = 'Tidak ada batas waktu';
                    expiresInfo.style.display = 'block';
                }

                noResultMessage.classList.add('hidden');
                resultContainer.classList.remove('hidden');
            }

            function loadStatistics() {
                fetch('{{ route("skrining.statistics") }}', {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        const quickTotalGenerated = document.getElementById('quickTotalGenerated');
                        const quickTotalAccess = document.getElementById('quickTotalAccess');

                        if (quickTotalGenerated) quickTotalGenerated.textContent = data.total_generated;
                        if (quickTotalAccess) quickTotalAccess.textContent = data.total_access;

                        const recentLinksTable = document.getElementById('recentLinksTable');
                        if (recentLinksTable) {
                            let tableHtml = '';
                            if (data.recent_links && data.recent_links.length > 0) {
                                data.recent_links.forEach(function(link) {
                                    const createdAt = new Date(link.created_at).toLocaleDateString('id-ID');
                                    const status = link.is_active ?
                                        '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">Aktif</span>' :
                                        '<span class="inline-flex px-2 py-1 text-xs font-semibold rounded-full bg-red-100 text-red-800">Nonaktif</span>';

                                    tableHtml += `
                                    <tr class="hover:bg-gray-50">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="text-sm font-medium text-gray-900">${link.puskesmas.nama}</div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <code class="text-xs bg-gray-100 px-2 py-1 rounded">${link.short_code}</code>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${createdAt}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                ${link.access_count || 0} kali
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">${status}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                                            <div class="flex space-x-2">
                                                <button onclick="viewQRCode(${link.id})" 
                                                        class="text-purple-600 hover:text-purple-900 font-medium flex items-center"
                                                        title="Lihat QR Code">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    QR
                                                </button>
                                                <button onclick="copyLinkUrl('${link.short_url || link.original_url}')" 
                                                        class="text-blue-600 hover:text-blue-900 font-medium flex items-center"
                                                        title="Salin Link">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"></path>
                                                    </svg>
                                                    Salin
                                                </button>
                                                ${link.is_active ? `
                                                <button onclick="deactivateLink(${link.id})" 
                                                        class="text-orange-600 hover:text-orange-900 font-medium flex items-center"
                                                        title="Nonaktifkan">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                                                    </svg>
                                                    Off
                                                </button>` : ''}
                                                <button onclick="deleteLink(${link.id})" 
                                                        class="text-red-600 hover:text-red-900 font-medium flex items-center"
                                                        title="Hapus">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                                    </svg>
                                                    Hapus
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                `;
                                });
                            } else {
                                tableHtml = `
                                <tr>
                                    <td colspan="6" class="px-6 py-8 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 mb-3 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                                            </svg>
                                            <p>Belum ada link yang dibuat</p>
                                        </div>
                                    </td>
                                </tr>
                            `;
                            }

                            recentLinksTable.innerHTML = tableHtml;
                        }
                    })
                    .catch(error => {
                        console.error('Gagal memuat statistik:', error);
                    });
            }

            function viewQRCode(linkId) {
                const loadingOverlay = document.getElementById('loadingOverlay');
                loadingOverlay.classList.remove('hidden');

                fetch(`/skrining/qrcode/${linkId}`, {
                        method: 'GET',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            currentModalData = data.data;
                            
                            document.getElementById('modalQRImage').src = 'data:image/png;base64,' + data.data.qr_code;
                            document.getElementById('modalPuskesmasName').textContent = data.data.puskesmas_name;
                            document.getElementById('modalShortUrl').textContent = data.data.short_url;
                            document.getElementById('modalCreatedAt').textContent = data.data.created_at;
                            document.getElementById('modalAccessCount').textContent = data.data.access_count || 0;
                            
                            document.getElementById('qrModal').classList.remove('hidden');
                        } else {
                            throw new Error(data.message || 'Gagal memuat QR Code');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        ALERT(error.message || 'Gagal memuat QR Code', 'bad');
                    })
                    .finally(() => {
                        loadingOverlay.classList.add('hidden');
                    });
            }

            function closeQRModal() {
                document.getElementById('qrModal').classList.add('hidden');
                currentModalData = null;
            }

            function downloadModalQR() {
                if (!currentModalData) return;
                
                const modalQRImage = document.getElementById('modalQRImage');
                const puskesmasName = currentModalData.puskesmas_name;

                const link = document.createElement('a');
                link.download = `qr-code-${puskesmasName.toLowerCase().replace(/\s+/g, '-')}-${Date.now()}.png`;
                link.href = modalQRImage.src;
                link.click();

                ALERT('QR Code berhasil didownload!', 'ok');
            }

            function printModalQR() {
                if (!currentModalData) return;

                const modalQRImage = document.getElementById('modalQRImage');
                const data = currentModalData;

                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                    <head>
                        <title>Print QR Code - ${data.puskesmas_name}</title>
                        <style>
                            @page { 
                                size: A4; 
                                margin: 2cm; 
                            }
                            body { 
                                font-family: 'Arial', sans-serif; 
                                text-align: center; 
                                padding: 20px;
                                background: white;
                            }
                            .header {
                                border-bottom: 3px solid #3B82F6;
                                padding-bottom: 20px;
                                margin-bottom: 30px;
                            }
                            .header h1 {
                                color: #1F2937;
                                font-size: 24px;
                                margin: 0 0 10px 0;
                            }
                            .header p {
                                color: #6B7280;
                                font-size: 16px;
                                margin: 0;
                            }
                            .qr-container {
                                background: #F9FAFB;
                                border: 2px solid #E5E7EB;
                                border-radius: 12px;
                                padding: 30px;
                                margin: 30px 0;
                                display: inline-block;
                            }
                            img { 
                                max-width: 250px; 
                                height: auto;
                                border-radius: 8px;
                            }
                            .info {
                                background: #EFF6FF;
                                border: 1px solid #DBEAFE;
                                border-radius: 8px;
                                padding: 20px;
                                margin: 20px 0;
                                text-align: left;
                            }
                            .info-item {
                                margin: 8px 0;
                                font-size: 14px;
                            }
                            .info-item strong {
                                color: #1F2937;
                                display: inline-block;
                                width: 120px;
                            }
                            .footer {
                                margin-top: 40px;
                                padding-top: 20px;
                                border-top: 1px solid #E5E7EB;
                                font-size: 12px;
                                color: #6B7280;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h1>QR Code Simkeswa</h1>
                            <p>Sistem Informasi Kesehatan</p>
                        </div>
                        
                        <div class="qr-container">
                            <img src="${modalQRImage.src}" alt="QR Code">
                            <p style="margin-top: 15px; color: #6B7280; font-size: 14px;">
                                Scan QR Code untuk mengakses formulir skrining
                            </p>
                        </div>
                        
                        <div class="info">
                            <div class="info-item"><strong>Puskesmas:</strong> ${data.puskesmas_name}</div>
                            <div class="info-item"><strong>Link:</strong> ${data.short_url}</div>
                            <div class="info-item"><strong>Dibuat:</strong> ${data.created_at}</div>
                            <div class="info-item"><strong>Total Akses:</strong> ${data.access_count || 0} kali</div>
                            <div class="info-item"><strong>Dicetak:</strong> ${new Date().toLocaleDateString('id-ID')} ${new Date().toLocaleTimeString('id-ID')}</div>
                        </div>
                        
                        <div class="footer">
                            <p>Dokumen ini digenerate secara otomatis oleh sistem</p>
                        </div>
                    </body>
                    </html>
                `);
                
                printWindow.document.close();
                printWindow.focus();
                
                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                }, 250);
            }

            function copyToClipboard(elementId) {
                const element = document.getElementById(elementId);
                element.select();
                element.setSelectionRange(0, 99999);

                if (navigator.clipboard) {
                    navigator.clipboard.writeText(element.value).then(function() {
                        ALERT('Link berhasil disalin ke clipboard!', 'ok');
                    }).catch(function() {
                        document.execCommand('copy');
                        ALERT('Link berhasil disalin ke clipboard!', 'ok');
                    });
                } else {
                    document.execCommand('copy');
                    ALERT('Link berhasil disalin ke clipboard!', 'ok');
                }
            }

            function copyLinkUrl(url) {
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(url).then(function() {
                        ALERT('Link berhasil disalin ke clipboard!', 'ok');
                    }).catch(function() {
                        fallbackCopyTextToClipboard(url);
                    });
                } else {
                    fallbackCopyTextToClipboard(url);
                }
            }

            function fallbackCopyTextToClipboard(text) {
                const tempInput = document.createElement('input');
                tempInput.value = text;
                document.body.appendChild(tempInput);
                tempInput.select();
                document.execCommand('copy');
                document.body.removeChild(tempInput);
                ALERT('Link berhasil disalin ke clipboard!', 'ok');
            }

            function downloadQR() {
                const qrImage = document.getElementById('qrCodeImage');
                const puskesmasName = document.getElementById('puskesmasName').textContent;

                const link = document.createElement('a');
                link.download = `qr-code-skrining-${puskesmasName.toLowerCase().replace(/\s+/g, '-')}.png`;
                link.href = qrImage.src;
                link.click();

                ALERT('QR Code berhasil didownload!', 'ok');
            }

            function printQR() {
                const qrImage = document.getElementById('qrCodeImage');
                const shortUrl = document.getElementById('shortUrl').value;
                const puskesmasName = document.getElementById('puskesmasName').textContent;
                const createdAt = document.getElementById('createdAt').textContent;

                const printWindow = window.open('', '_blank');
                printWindow.document.write(`
                    <html>
                    <head>
                        <title>Print QR Code - ${puskesmasName}</title>
                        <style>
                            @page { 
                                size: A4; 
                                margin: 2cm; 
                            }
                            body { 
                                font-family: 'Arial', sans-serif; 
                                text-align: center; 
                                padding: 20px;
                                background: white;
                            }
                            .header {
                                border-bottom: 3px solid #3B82F6;
                                padding-bottom: 20px;
                                margin-bottom: 30px;
                            }
                            .header h1 {
                                color: #1F2937;
                                font-size: 24px;
                                margin: 0 0 10px 0;
                            }
                            .header p {
                                color: #6B7280;
                                font-size: 16px;
                                margin: 0;
                            }
                            .qr-container {
                                background: #F9FAFB;
                                border: 2px solid #E5E7EB;
                                border-radius: 12px;
                                padding: 30px;
                                margin: 30px 0;
                                display: inline-block;
                            }
                            img { 
                                max-width: 250px; 
                                height: auto;
                                border-radius: 8px;
                            }
                            .info {
                                background: #EFF6FF;
                                border: 1px solid #DBEAFE;
                                border-radius: 8px;
                                padding: 20px;
                                margin: 20px 0;
                                text-align: left;
                            }
                            .info-item {
                                margin: 8px 0;
                                font-size: 14px;
                            }
                            .info-item strong {
                                color: #1F2937;
                                display: inline-block;
                                width: 120px;
                            }
                            .footer {
                                margin-top: 40px;
                                padding-top: 20px;
                                border-top: 1px solid #E5E7EB;
                                font-size: 12px;
                                color: #6B7280;
                            }
                        </style>
                    </head>
                    <body>
                        <div class="header">
                            <h1>QR Code Simkeswa</h1>
                            <p>Sistem Informasi Kesehatan</p>
                        </div>
                        
                        <div class="qr-container">
                            <img src="${qrImage.src}" alt="QR Code">
                            <p style="margin-top: 15px; color: #6B7280; font-size: 14px;">
                                Scan QR Code untuk mengakses formulir skrining
                            </p>
                        </div>
                        
                        <div class="info">
                            <div class="info-item"><strong>Puskesmas:</strong> ${puskesmasName}</div>
                            <div class="info-item"><strong>Link:</strong> ${shortUrl}</div>
                            <div class="info-item"><strong>Dibuat:</strong> ${createdAt}</div>
                            <div class="info-item"><strong>Dicetak:</strong> ${new Date().toLocaleDateString('id-ID')} ${new Date().toLocaleTimeString('id-ID')}</div>
                        </div>
                        
                        <div class="footer">
                            <p>Dokumen ini digenerate secara otomatis oleh sistem</p>
                        </div>
                    </body>
                    </html>
                `);
                
                printWindow.document.close();
                printWindow.focus();
                
                setTimeout(() => {
                    printWindow.print();
                    printWindow.close();
                }, 250);
            }

            function deactivateLink(linkId) {
                if (!confirm('Apakah Anda yakin ingin menonaktifkan link ini?')) {
                    return;
                }

                const loadingOverlay = document.getElementById('loadingOverlay');
                loadingOverlay.classList.remove('hidden');

                fetch(`/link/deactivate/${linkId}`, {
                        method: 'POST',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            ALERT('Link berhasil dinonaktifkan', 'ok');
                            loadStatistics();
                        } else {
                            throw new Error(data.message || 'Gagal menonaktifkan link');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        ALERT(error.message || 'Gagal menonaktifkan link', 'bad');
                    })
                    .finally(() => {
                        loadingOverlay.classList.add('hidden');
                    });
            }

            function deleteLink(linkId) {
                if (!confirm('Apakah Anda yakin ingin menghapus link ini? QR Code juga akan dihapus dan tindakan ini tidak dapat dibatalkan.')) {
                    return;
                }

                const loadingOverlay = document.getElementById('loadingOverlay');
                loadingOverlay.classList.remove('hidden');

                fetch(`/skrining/delete/${linkId}`, {
                        method: 'DELETE',
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            ALERT('Link dan QR Code berhasil dihapus', 'ok');
                            loadStatistics();
                        } else {
                            throw new Error(data.message || 'Gagal menghapus link');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        ALERT(error.message || 'Gagal menghapus link', 'bad');
                    })
                    .finally(() => {
                        loadingOverlay.classList.add('hidden');
                    });
            }

            // Close modal when clicking outside
            document.getElementById('qrModal').addEventListener('click', function(e) {
                if (e.target === this) {
                    closeQRModal();
                }
            });
        </script>
    </x-slot>
</x-app-layout>