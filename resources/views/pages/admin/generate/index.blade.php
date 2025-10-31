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
            <p class="text-gray-600 font-medium">Sedang generate link dan QR code...</p>
        </div>
    </div>

    <x-slot name="scripts">
        <script>
            function initializePage() {
                // Load statistics on page load
                loadStatistics();

                // Form submission
                const generateForm = document.getElementById('generateForm');
                if (generateForm) {
                    generateForm.addEventListener('submit', function(e) {
                        e.preventDefault();
                        generateLink();
                    });
                }

                // Auto refresh statistics every 30 seconds
                if (window.screeningStatsInterval) {
                    clearInterval(window.screeningStatsInterval);
                }
                window.screeningStatsInterval = setInterval(function() {
                    loadStatistics();
                }, 30000);
            }

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initializePage);
            } else {
                initializePage();
            }

            document.addEventListener('turbo:load', initializePage);

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
                            ALERT('Link dan QR Code berhasil di-generate!', 'ok');
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
                        console.log("🚀 ~ loadStatistics ~ data:", data)
                        const quickTotalGenerated = document.getElementById('quickTotalGenerated');
                        const quickTotalAccess = document.getElementById('quickTotalAccess');

                        if (quickTotalGenerated) quickTotalGenerated.textContent = data.total_generated;
                        if (quickTotalAccess) quickTotalAccess.textContent = data.total_access;

                        // Load recent links table
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
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">${createdAt}</td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                ${link.access_count || 0} kali
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">${status}</td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                            <div class="flex space-x-2">
                                                <button onclick="copyLinkUrl('${link.short_url || link.original_url}')" 
                                                        class="text-blue-600 hover:text-blue-900 font-medium">
                                                    Salin
                                                </button>
                                                <button onclick="deactivateLink(${link.id})" 
                                                        class="text-red-600 hover:text-red-900 font-medium" 
                                                        ${!link.is_active ? 'disabled' : ''}>
                                                    ${link.is_active ? 'Nonaktifkan' : 'Nonaktif'}
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                `;
                                });
                            } else {
                                tableHtml = `
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-gray-500">
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

            function copyToClipboard(elementId) {
                const element = document.getElementById(elementId);
                element.select();
                element.setSelectionRange(0, 99999);

                // Try modern clipboard API first
                if (navigator.clipboard) {
                    navigator.clipboard.writeText(element.value).then(function() {
                        ALERT('Link berhasil disalin ke clipboard!', 'ok');
                    }).catch(function() {
                        // Fallback to old method
                        document.execCommand('copy');
                        ALERT('Link berhasil disalin ke clipboard!', 'ok');
                    });
                } else {
                    // Fallback for older browsers
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
            }
        </script>
    </x-slot>
</x-app-layout>