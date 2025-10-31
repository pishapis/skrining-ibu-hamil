<x-app-layout>
    @section('page_title','Buat Konten')
    @section('css')
        <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.css" rel="stylesheet">
        <style>
            .upload-progress {
                display: none;
                margin-top: 1rem;
            }
            .upload-progress.active {
                display: block;
            }
            .progress-bar {
                width: 100%;
                height: 24px;
                background: #e5e7eb;
                border-radius: 12px;
                overflow: hidden;
                position: relative;
            }
            .progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #14b8a6 0%, #0d9488 100%);
                transition: width 0.3s ease;
                display: flex;
                align-items: center;
                justify-content: center;
                color: white;
                font-size: 12px;
                font-weight: 600;
            }
            .video-item {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 12px;
                background: #f9fafb;
                border-radius: 8px;
                margin-top: 8px;
            }
            .video-item-thumbnail {
                width: 80px;
                height: 60px;
                border-radius: 6px;
                object-fit: cover;
                background: #e5e7eb;
            }
            .video-item-info {
                flex: 1;
            }
            .video-item-name {
                font-size: 14px;
                color: #374151;
                font-weight: 500;
            }
            .video-item-progress {
                font-size: 12px;
                color: #6b7280;
                margin-top: 4px;
            }
            .video-item-status {
                font-size: 12px;
                padding: 4px 8px;
                border-radius: 4px;
                font-weight: 500;
                white-space: nowrap;
            }
            .status-uploading { background: #dbeafe; color: #1e40af; }
            .status-pending { background: #fef3c7; color: #92400e; }
            .status-processing { background: #e0e7ff; color: #3730a3; }
            .status-completed { background: #d1fae5; color: #065f46; }
            .status-failed { background: #fee2e2; color: #991b1b; }
            
            .video-item-progress-bar {
                width: 100%;
                height: 4px;
                background: #e5e7eb;
                border-radius: 2px;
                overflow: hidden;
                margin-top: 4px;
            }
            .video-item-progress-fill {
                height: 100%;
                background: linear-gradient(90deg, #14b8a6 0%, #0d9488 100%);
                transition: width 0.3s ease;
            }
        </style>
    @endsection
    <x-slot name="title">Buat Konten Edukasi</x-slot>
    <x-header-back>Post Edukasi</x-header-back>

    <a href="{{ route('edukasi.index') }}" class="btn-primary w-1/2 hidden md:block md:w-1/6">lihat Konten Edukasi</a>
    
    <form id="edu-form" action="{{ route('edukasi.store') }}" method="post" enctype="multipart/form-data" class="space-y-5">
        @csrf

        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Judul</label>
            <input name="title" class="mt-1 w-full rounded-lg border px-3 py-2" required>
            <p class="mt-1 text-xs text-gray-500">Slug dibuat otomatis.</p>
        </div>

        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Ringkasan (opsional)</label>
            <textarea name="summary" class="mt-1 w-full rounded-lg border px-3 py-2" rows="3" maxlength="500"></textarea>
        </div>

        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Konten</label>
            <textarea id="body" name="body" class="mt-1 w-full rounded-lg border px-3 py-2 min-h-60" rows="10"></textarea>
        </div>

        {{-- MEDIA --}}
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Galeri Gambar</label>
            <input type="file" name="images[]" multiple accept="image/*" class="mt-2 block w-full">
            <p class="mt-1 text-xs text-gray-500">Bisa banyak. Gambar pertama jadi cover.</p>
        </div>

        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Video (Upload)</label>
            <input type="file" id="video-input" name="videos[]" multiple accept="video/mp4,video/webm,video/quicktime" class="mt-2 block w-full">
            <p class="mt-1 text-xs text-gray-500">MP4/WEBM/MOV, maks ~700MB per file. Video akan dikompres dan thumbnail dibuat otomatis.</p>
            
            <!-- Upload Progress -->
            <div id="upload-progress" class="upload-progress">
                <div class="mb-2">
                    <span class="text-sm font-medium text-gray-700">Mengupload video...</span>
                </div>
                <div class="progress-bar">
                    <div id="upload-progress-fill" class="progress-fill" style="width: 0%">0%</div>
                </div>
            </div>

            <!-- Video Items List -->
            <div id="video-items" class="mt-3"></div>
        </div>

        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Video URL (YouTube/Vimeo)</label>
            <textarea name="video_urls" rows="3" class="mt-2 w-full rounded-lg border px-3 py-2" placeholder="Satu URL per baris"></textarea>
        </div>

        {{-- TAGS & VISIBILITY --}}
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="text-sm font-medium">Tags (pisahkan dengan koma)</label>
            <input name="tags" placeholder="postpartum, kecemasan, tidur" class="mt-1 w-full rounded-lg border px-3 py-2">
        </div>

        <div class="rounded-2xl border bg-white p-4 shadow-sm grid grid-cols-1 sm:grid-cols-3 gap-3">
            <div>
                <label class="text-sm font-medium">Visibility</label>
                <select id="visibility" name="visibility" class="mt-1 w-full rounded-lg border px-3 py-2" required>
                    <option value="public">Public</option>
                    <option value="facility">Facility (berdasarkan Puskesmas)</option>
                    <option value="private">Private</option>
                </select>
            </div>
            <div id="puskesmas-field" class="hidden">
                <label class="text-sm font-medium">Puskesmas ID</label>
                <input name="puskesmas_id" type="number" class="mt-1 w-full rounded-lg border px-3 py-2" placeholder="cth: 123">
            </div>
            <div>
                <label class="text-sm font-medium">Status</label>
                <select name="status" class="mt-1 w-full rounded-lg border px-3 py-2" required>
                    <option value="draft">Draft</option>
                    <option value="published">Published</option>
                </select>
            </div>
            <div>
                <label class="text-sm font-medium">Tanggal Publish (opsional)</label>
                <input name="published_at" type="datetime-local" class="mt-1 w-full rounded-lg border px-3 py-2">
            </div>
        </div>

        {{-- RULES targeting --}}
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <div class="flex items-center justify-between">
                <label class="text-sm font-semibold">Targeting Berdasarkan Hasil Skrining</label>
                <button id="add-rule-btn" type="button" class="px-3 py-1.5 rounded-lg bg-gray-900 text-white text-sm">+ Tambah Rule</button>
            </div>
            <div id="rules-list"></div>
            <p class="mt-2 text-xs text-gray-500">Jika tidak ada rule, konten dianggap umum.</p>
        </div>

        <div class="pb-24">
            <button id="submit-btn" type="submit" class="w-full md:w-auto px-4 py-2 rounded-xl bg-teal-600 text-white text-sm font-medium hover:bg-teal-700">
                Simpan Konten
            </button>
        </div>
    </form>

    {{-- Template item rule --}}
    <template id="rule-template">
        <div class="rule-item mt-3 grid grid-cols-1 sm:grid-cols-5 gap-2 border rounded-xl p-3">
            <div>
                <label class="text-xs text-gray-600">Jenis</label>
                <select class="field-type w-full rounded-lg border px-2 py-1.5">
                    <option value="epds">EPDS</option>
                    <option value="dass">DASS-21</option>
                </select>
            </div>
            <div>
                <label class="text-xs text-gray-600">Dimensi</label>
                <select class="field-dimension w-full rounded-lg border px-2 py-1.5"></select>
            </div>
            <div>
                <label class="text-xs text-gray-600">Min</label>
                <input type="number" class="field-min w-full rounded-lg border px-2 py-1.5">
            </div>
            <div>
                <label class="text-xs text-gray-600">Max</label>
                <input type="number" class="field-max w-full rounded-lg border px-2 py-1.5">
            </div>
            <div>
                <label class="text-xs text-gray-600">Trimester</label>
                <select class="field-tri w-full rounded-lg border px-2 py-1.5">
                    <option value="">(Semua)</option>
                    <option value="trimester_1">Trimester I</option>
                    <option value="trimester_2">Trimester II</option>
                    <option value="trimester_3">Trimester III</option>
                    <option value="pasca_hamil">Pasca Melahirkan</option>
                </select>
            </div>
            <div class="sm:col-span-5 flex justify-end">
                <button type="button" class="btn-remove-rule text-sm text-rose-600">Hapus</button>
            </div>
        </div>
    </template>

    <x-slot name="scripts">
        <script data-swup-reload-script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script data-swup-reload-script src="https://cdn.jsdelivr.net/npm/summernote@0.8.18/dist/summernote-lite.min.js"></script>
        <script data-swup-reload-script>
            (function() {
                const qs  = (s, c = document) => c.querySelector(s);
                const qsa = (s, c = document) => Array.from(c.querySelectorAll(s));
                
                const form = qs('#edu-form');
                const visSel = qs('#visibility');
                const pusk = qs('#puskesmas-field');
                const list = qs('#rules-list');
                const tpl = qs('#rule-template');
                const addBtn = qs('#add-rule-btn');
                const submitBtn = qs('#submit-btn');
                const videoInput = qs('#video-input');
                const uploadProgress = qs('#upload-progress');
                const uploadProgressFill = qs('#upload-progress-fill');
                const videoItems = qs('#video-items');

                let uploadedVideoMediaIds = [];
                let isUploading = false;
                let pollingIntervals = {};

                const DIM = {
                    epds: [{v: 'epds_total', t: 'EPDS Total'}],
                    dass: [{v: 'dass_dep', t: 'Depresi'}, {v: 'dass_anx', t: 'Kecemasan'}, {v: 'dass_str', t: 'Stres'}]
                };

                // Visibility toggle
                function togglePusk() {
                    const show = visSel.value === 'facility';
                    pusk.classList.toggle('hidden', !show);
                    if (!show) {
                        const i = qs('input[name="puskesmas_id"]', pusk);
                        if (i) i.value = '';
                    }
                }
                visSel.addEventListener('change', togglePusk);
                togglePusk();

                // Rules management
                function setDim(el, type, current = null) {
                    const defs = type === 'dass' ? DIM.dass : DIM.epds;
                    el.innerHTML = '';
                    defs.forEach(d => {
                        const o = document.createElement('option');
                        o.value = d.v;
                        o.textContent = d.t;
                        el.appendChild(o);
                    });
                    el.value = (current && defs.find(x => x.v === current)) ? current : defs[0].v;
                }

                function renum() {
                    qsa('.rule-item', list).forEach((it, i) => {
                        const t = qs('.field-type', it),
                            d = qs('.field-dimension', it),
                            mn = qs('.field-min', it),
                            mx = qs('.field-max', it),
                            tr = qs('.field-tri', it);
                        setDim(d, t.value, d.value);
                        t.name = `rules[${i}][screening_type]`;
                        d.name = `rules[${i}][dimension]`;
                        mn.name = `rules[${i}][min_score]`;
                        mx.name = `rules[${i}][max_score]`;
                        tr.name = `rules[${i}][trimester]`;
                    });
                }

                function add(def) {
                    const node = tpl.content.cloneNode(true);
                    const it = qs('.rule-item', node);
                    const t = qs('.field-type', it);
                    const d = qs('.field-dimension', it);
                    const mn = qs('.field-min', it);
                    const mx = qs('.field-max', it);
                    const tr = qs('.field-tri', it);

                    const df = Object.assign({
                        screening_type: 'epds',
                        dimension: 'epds_total',
                        min_score: '',
                        max_score: '',
                        trimester: ''
                    }, def || {});
                    t.value = df.screening_type;
                    setDim(d, df.screening_type, df.dimension);
                    mn.value = df.min_score;
                    mx.value = df.max_score;
                    tr.value = df.trimester;

                    list.appendChild(node);
                    renum();
                }

                addBtn.addEventListener('click', () => add());
                list.addEventListener('change', (e) => {
                    const it = e.target.closest('.rule-item');
                    if (!it) return;
                    if (e.target.classList.contains('field-type')) {
                        setDim(qs('.field-dimension', it), e.target.value, null);
                        renum();
                    }
                });
                list.addEventListener('click', (e) => {
                    if (e.target.classList.contains('btn-remove-rule')) {
                        const it = e.target.closest('.rule-item');
                        if (it) it.remove();
                        renum();
                    }
                });

                // Video preview with thumbnail
                videoInput.addEventListener('change', function(e) {
                    const files = Array.from(e.target.files);
                    if (files.length === 0) return;

                    // Show file info with thumbnail placeholder
                    files.forEach((file, idx) => {
                        const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
                        const item = document.createElement('div');
                        item.className = 'video-item';
                        item.dataset.index = idx;
                        item.innerHTML = `
                            <img class="video-item-thumbnail" src="{{ asset('assets/img/video-placeholder.svg') }}" alt="Thumbnail">
                            <div class="video-item-info">
                                <div class="video-item-name">${file.name} (${sizeInMB} MB)</div>
                                <div class="video-item-progress">Menunggu upload...</div>
                                <div class="video-item-progress-bar">
                                    <div class="video-item-progress-fill" style="width: 0%"></div>
                                </div>
                            </div>
                            <span class="video-item-status status-pending">Menunggu</span>
                        `;
                        videoItems.appendChild(item);
                        
                        // Generate video thumbnail preview
                        generateVideoThumbnail(file, item.querySelector('.video-item-thumbnail'));
                    });
                });

                // Generate thumbnail from video file
                function generateVideoThumbnail(file, imgElement) {
                    const video = document.createElement('video');
                    video.preload = 'metadata';
                    video.muted = true;
                    video.playsInline = true;
                    
                    video.onloadedmetadata = function() {
                        video.currentTime = Math.min(2, video.duration / 2); // Get frame at 2s or middle
                    };
                    
                    video.onseeked = function() {
                        const canvas = document.createElement('canvas');
                        canvas.width = video.videoWidth;
                        canvas.height = video.videoHeight;
                        canvas.getContext('2d').drawImage(video, 0, 0);
                        imgElement.src = canvas.toDataURL();
                        URL.revokeObjectURL(video.src);
                    };
                    
                    video.src = URL.createObjectURL(file);
                }

                // Form submission with AJAX
                form.addEventListener('submit', function(e) {
                    if (isUploading) {
                        e.preventDefault();
                        alert('Mohon tunggu, upload sedang berlangsung...');
                        return;
                    }

                    const videoFiles = videoInput.files;
                    if (videoFiles && videoFiles.length > 0) {
                        e.preventDefault();
                        uploadWithProgress();
                    }
                });

                function uploadWithProgress() {
                    isUploading = true;
                    submitBtn.disabled = true;
                    submitBtn.textContent = 'Mengupload...';
                    uploadProgress.classList.add('active');

                    const formData = new FormData(form);
                    const xhr = new XMLHttpRequest();

                    // Track upload progress
                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            const percentComplete = Math.round((e.loaded / e.total) * 100);
                            uploadProgressFill.style.width = percentComplete + '%';
                            uploadProgressFill.textContent = percentComplete + '%';
                            
                            // Update individual video items
                            qsa('.video-item').forEach(item => {
                                const status = item.querySelector('.video-item-status');
                                const progressText = item.querySelector('.video-item-progress');
                                const progressFill = item.querySelector('.video-item-progress-fill');
                                
                                if (status.classList.contains('status-pending')) {
                                    status.className = 'video-item-status status-uploading';
                                    status.textContent = 'Uploading...';
                                    progressText.textContent = `Uploading: ${percentComplete}%`;
                                    progressFill.style.width = percentComplete + '%';
                                }
                            });
                        }
                    });

                    xhr.addEventListener('load', function() {
                        if (xhr.status === 200) {
                            try {
                                const response = JSON.parse(xhr.responseText);
                                
                                if (response.media_ids && response.media_ids.length > 0) {
                                    uploadedVideoMediaIds = response.media_ids;
                                    
                                    // Start polling for each video
                                    qsa('.video-item').forEach((item, idx) => {
                                        const mediaId = uploadedVideoMediaIds[idx];
                                        if (mediaId) {
                                            item.dataset.mediaId = mediaId;
                                            startPolling(mediaId, item);
                                        }
                                    });
                                    
                                    submitBtn.textContent = 'Video diproses...';
                                    uploadProgress.classList.remove('active');
                                } else {
                                    // No videos uploaded, redirect
                                    window.location.href = response.redirect || "{{ route('edukasi.index') }}";
                                }
                            } catch(err) {
                                console.error('Parse error:', err);
                                window.location.href = "{{ route('edukasi.index') }}";
                            }
                        } else {
                            alert('Upload gagal. Silakan coba lagi.');
                            resetUploadState();
                        }
                    });

                    xhr.addEventListener('error', function() {
                        alert('Terjadi kesalahan saat upload. Silakan coba lagi.');
                        resetUploadState();
                    });

                    xhr.open('POST', form.action);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('input[name="_token"]').value);
                    xhr.send(formData);
                }

                // Poll video processing status
                function startPolling(mediaId, itemElement) {
                    const status = itemElement.querySelector('.video-item-status');
                    const progressText = itemElement.querySelector('.video-item-progress');
                    const progressFill = itemElement.querySelector('.video-item-progress-fill');
                    const thumbnail = itemElement.querySelector('.video-item-thumbnail');
                    
                    status.className = 'video-item-status status-processing';
                    status.textContent = 'Memproses...';
                    progressText.textContent = 'Mengkompresi video...';
                    
                    pollingIntervals[mediaId] = setInterval(async () => {
                        try {
                            const response = await fetch(`/edukasi/video-status/${mediaId}`);
                            const data = await response.json();
                            
                            if (data.status === 'completed') {
                                clearInterval(pollingIntervals[mediaId]);
                                status.className = 'video-item-status status-completed';
                                status.textContent = 'Selesai';
                                progressText.textContent = 'Kompresi selesai!';
                                progressFill.style.width = '100%';
                                
                                // Update thumbnail if available
                                if (data.thumbnail_url) {
                                    thumbnail.src = data.thumbnail_url;
                                }
                                
                                checkAllCompleted();
                            } else if (data.status === 'failed') {
                                clearInterval(pollingIntervals[mediaId]);
                                status.className = 'video-item-status status-failed';
                                status.textContent = 'Gagal';
                                progressText.textContent = data.error || 'Kompresi gagal';
                                progressFill.style.width = '0%';
                            } else if (data.status === 'processing') {
                                const progress = data.progress || 0;
                                progressText.textContent = `Memproses: ${progress}%`;
                                progressFill.style.width = progress + '%';
                                
                                // Update thumbnail when available (around 10% progress)
                                if (progress >= 10 && data.thumbnail_url && thumbnail.src.includes('placeholder')) {
                                    thumbnail.src = data.thumbnail_url;
                                }
                            }
                        } catch (err) {
                            console.error('Polling error:', err);
                        }
                    }, 2000); // Poll every 2 seconds
                }

                function checkAllCompleted() {
                    const allItems = qsa('.video-item');
                    const allCompleted = allItems.every(item => {
                        const status = item.querySelector('.video-item-status');
                        return status.classList.contains('status-completed') || status.classList.contains('status-failed');
                    });
                    
                    if (allCompleted) {
                        submitBtn.textContent = 'Selesai! Mengalihkan...';
                        setTimeout(() => {
                            window.location.href = "{{ route('edukasi.index') }}";
                        }, 1500);
                    }
                }

                function resetUploadState() {
                    isUploading = false;
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Simpan Konten';
                    uploadProgress.classList.remove('active');
                    uploadProgressFill.style.width = '0%';
                    uploadProgressFill.textContent = '0%';
                    
                    Object.values(pollingIntervals).forEach(interval => clearInterval(interval));
                    pollingIntervals = {};
                }

                // Initialize Summernote
                $('#body').summernote({
                    height: 300,
                });

            })();
        </script>
    </x-slot>
</x-app-layout>