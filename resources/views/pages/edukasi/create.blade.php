<x-app-layout>
    @section('page_title','Buat Konten')
    @section('css')
    <style>
        .upload-progress {
            display: none;
            margin-top: 1.5rem;
            padding: 1.25rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 16px;
            box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
        }

        .upload-progress.active {
            display: block;
            animation: slideInDown 0.4s ease-out;
        }

        .upload-progress .text-sm {
            color: white;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 12px;
        }

        .upload-progress .text-sm::before {
            content: "";
            width: 8px;
            height: 8px;
            background: #10b981;
            border-radius: 50%;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        .progress-bar {
            width: 100%;
            height: 32px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            overflow: hidden;
            position: relative;
            backdrop-filter: blur(10px);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #10b981 0%, #059669 100%);
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 13px;
            font-weight: 700;
            position: relative;
            overflow: hidden;
        }

        .progress-fill::before {
            content: "";
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.3), transparent);
            animation: shimmer 2s infinite;
        }

        /* ========== VIDEO ITEMS LIST ========== */
        #video-items {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .video-item {
            display: flex;
            align-items: center;
            gap: 16px;
            padding: 16px;
            background: linear-gradient(135deg, #ffffff 0%, #f8fafc 100%);
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
        }

        .video-item::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(180deg, #14b8a6 0%, #0d9488 100%);
            transform: scaleY(0);
            transition: transform 0.3s ease;
        }

        .video-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.08);
            border-color: #cbd5e1;
        }

        .video-item:hover::before {
            transform: scaleY(1);
        }

        /* ========== VIDEO THUMBNAIL ========== */
        .video-item-thumbnail {
            width: 120px;
            height: 90px;
            border-radius: 12px;
            object-fit: cover;
            background: linear-gradient(135deg, #e2e8f0 0%, #cbd5e1 100%);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            flex-shrink: 0;
        }

        .video-item:hover .video-item-thumbnail {
            transform: scale(1.05);
        }

        /* ========== VIDEO INFO ========== */
        .video-item-info {
            flex: 1;
            min-width: 0;
        }

        .video-item-name {
            font-size: 14px;
            font-weight: 600;
            color: #1e293b;
            margin-bottom: 6px;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .video-item-size {
            font-size: 12px;
            color: #64748b;
            font-weight: 500;
        }

        .video-item-separator {
            color: #cbd5e1;
            font-size: 12px;
        }

        .video-item-progress {
            font-size: 12px;
            color: #475569;
            font-weight: 500;
        }

        /* ========== VIDEO PROGRESS BAR ========== */
        .video-item-progress-bar {
            width: 100%;
            height: 6px;
            background: #e2e8f0;
            border-radius: 3px;
            overflow: hidden;
            margin-top: 8px;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.1);
        }

        .video-item-progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #14b8a6 0%, #06b6d4 100%);
            transition: width 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            border-radius: 3px;
            position: relative;
            overflow: hidden;
        }

        .video-item-progress-fill::after {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            bottom: 0;
            right: 0;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.4), transparent);
            animation: progressShine 1.5s infinite;
        }

        /* ========== VIDEO STATUS BADGES ========== */
        .video-item-status {
            font-size: 12px;
            padding: 6px 12px;
            border-radius: 8px;
            font-weight: 600;
            white-space: nowrap;
            display: inline-flex;
            align-items: center;
            transition: all 0.3s ease;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .status-pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
            border: 1px solid #fcd34d;
        }

        .status-uploading {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
            border: 1px solid #93c5fd;
            animation: pulse 2s cubic-bezier(0.4, 0, 0.6, 1) infinite;
        }

        .status-processing {
            background: linear-gradient(135deg, #e0e7ff 0%, #c7d2fe 100%);
            color: #3730a3;
            border: 1px solid #a5b4fc;
        }

        .status-completed {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
            border: 1px solid #6ee7b7;
        }

        .status-failed {
            background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%);
            color: #991b1b;
            border: 1px solid #fca5a5;
        }

        /* ========== REMOVE VIDEO BUTTON ========== */
        .btn-remove-video {
            padding: 8px;
            background: #fee2e2;
            color: #dc2626;
            border-radius: 10px;
            transition: all 0.2s ease;
            border: none;
            cursor: pointer;
            flex-shrink: 0;
        }

        .btn-remove-video:hover {
            background: #fecaca;
            color: #991b1b;
            transform: rotate(90deg) scale(1.1);
        }

        .btn-remove-video:active {
            transform: rotate(90deg) scale(0.95);
        }

        /* ========== VIDEO INPUT STYLING ========== */
        #video-input {
            padding: 12px 16px;
            border: 2px dashed #cbd5e1;
            border-radius: 12px;
            background: #f8fafc;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        #video-input:hover {
            border-color: #14b8a6;
            background: #f0fdfa;
        }

        #video-input:focus {
            outline: none;
            border-color: #14b8a6;
            box-shadow: 0 0 0 3px rgba(20, 184, 166, 0.1);
        }

        /* ========== ANIMATIONS ========== */
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes shimmer {
            0% {
                left: -100%;
            }

            100% {
                left: 100%;
            }
        }

        @keyframes progressShine {
            0% {
                transform: translateX(-100%);
            }

            100% {
                transform: translateX(100%);
            }
        }

        @keyframes pulse {

            0%,
            100% {
                opacity: 1;
            }

            50% {
                opacity: 0.8;
            }
        }

        /* ========== RESPONSIVE ========== */
        @media (max-width: 640px) {
            .video-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }

            .video-item-thumbnail {
                width: 100%;
                height: 160px;
            }

            .video-item-info {
                width: 100%;
            }

            .video-item-status {
                align-self: flex-start;
            }

            .btn-remove-video {
                position: absolute;
                top: 12px;
                right: 12px;
            }
        }

        /* ========== QUILL EDITOR ========== */
        .ql-editor {
            min-height: 300px;
            font-size: 15px;
            line-height: 1.6;
        }

        .ql-editor:focus {
            outline: none;
        }

        /* ========== EMPTY STATE ========== */
        #video-items:empty::before {
            content: "Belum ada video yang dipilih";
            display: block;
            text-align: center;
            padding: 24px;
            color: #94a3b8;
            font-size: 14px;
            background: #f8fafc;
            border: 2px dashed #e2e8f0;
            border-radius: 12px;
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
            <div id="editor" class="mt-2 bg-white"></div>
            <textarea name="body" id="body" style="display: none;"></textarea>
        </div>

        {{-- MEDIA --}}
        <div class="rounded-2xl border bg-white p-4 shadow-sm">
            <label class="block text-sm font-medium mb-2 text-gray-700">Galeri Gambar</label>
            <div
                id="dropzone"
                class="relative flex flex-col items-center justify-center w-full p-6 border-2 border-dashed border-gray-300 rounded-xl cursor-pointer hover:border-indigo-400 transition">
                <svg class="w-10 h-10 text-gray-400 mb-2" xmlns="http://www.w3.org/2000/svg" fill="none"
                    viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M7 16V4m0 0L3 8m4-4l4 4M21 16v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4m18 0l-4 4m4-4l-4-4" />
                </svg>
                <p class="text-sm text-gray-600 text-center">
                    <span class="font-semibold text-indigo-600">Klik untuk unggah</span> atau seret gambar ke sini
                </p>
                <input
                    id="image-input"
                    type="file"
                    name="images[]"
                    multiple
                    accept="image/*"
                    onchange="fChangeGambar(this, event)"
                    class="absolute inset-0 opacity-0 cursor-pointer" />
            </div>
            <p class="mt-2 text-xs text-gray-500">Bisa banyak. Gambar pertama jadi cover.</p>
            <div id="preview" class="mt-4 grid grid-cols-3 gap-3"></div>
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
        <script>
            (function() {
                const qs = (s, c = document) => c.querySelector(s);
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
                let selectedFiles = [];

                const DIM = {
                    epds: [{
                        v: 'epds_total',
                        t: 'EPDS Total'
                    }],
                    dass: [{
                            v: 'dass_dep',
                            t: 'Depresi'
                        },
                        {
                            v: 'dass_anx',
                            t: 'Kecemasan'
                        },
                        {
                            v: 'dass_str',
                            t: 'Stres'
                        }
                    ]
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

                // ========== VIDEO UPLOAD IMPROVEMENTS ==========

                // Video file validation
                function validateVideoFile(file) {
                    const maxSize = 700 * 1024 * 1024; // 700MB
                    const allowedTypes = ['video/mp4', 'video/webm', 'video/quicktime'];

                    if (!allowedTypes.includes(file.type)) {
                        return {
                            valid: false,
                            error: 'Format video tidak didukung. Gunakan MP4, WEBM, atau MOV.'
                        };
                    }

                    if (file.size > maxSize) {
                        return {
                            valid: false,
                            error: `Ukuran file terlalu besar (${(file.size / (1024 * 1024)).toFixed(2)} MB). Maksimal 700MB.`
                        };
                    }

                    return {
                        valid: true
                    };
                }

                // Generate thumbnail from video file
                function generateVideoThumbnail(file, imgElement) {
                    return new Promise((resolve, reject) => {
                        const video = document.createElement('video');
                        const blobUrl = URL.createObjectURL(file);

                        video.preload = 'metadata';
                        video.muted = true;
                        video.playsInline = true;
                        video.crossOrigin = 'anonymous';

                        video.onloadedmetadata = function() {
                            // Set time to 1 second or 10% of duration, whichever is smaller
                            const targetTime = Math.min(1, video.duration * 0.1);
                            video.currentTime = targetTime;
                        };

                        video.onseeked = function() {
                            try {
                                const canvas = document.createElement('canvas');
                                const aspectRatio = video.videoWidth / video.videoHeight;

                                // Set canvas size
                                canvas.width = 640;
                                canvas.height = Math.round(640 / aspectRatio);

                                const ctx = canvas.getContext('2d');
                                ctx.drawImage(video, 0, 0, canvas.width, canvas.height);

                                // Convert to data URL
                                canvas.toBlob(function(blob) {
                                    const dataUrl = URL.createObjectURL(blob);
                                    imgElement.src = dataUrl;

                                    // Store blob URL to revoke later
                                    imgElement.dataset.blobUrl = dataUrl;

                                    // Clean up video blob URL
                                    URL.revokeObjectURL(blobUrl);
                                    video.remove();

                                    resolve(dataUrl);
                                }, 'image/jpeg', 0.8);

                            } catch (err) {
                                URL.revokeObjectURL(blobUrl);
                                video.remove();
                                reject(err);
                            }
                        };

                        video.onerror = function(err) {
                            URL.revokeObjectURL(blobUrl);
                            video.remove();
                            reject(err);
                        };

                        // Add timeout to prevent hanging
                        setTimeout(() => {
                            if (video.readyState === 0) {
                                URL.revokeObjectURL(blobUrl);
                                video.remove();
                                reject(new Error('Video load timeout'));
                            }
                        }, 10000);

                        video.src = blobUrl;
                        video.load();
                    });
                }

                // Create video item element with beautiful design
                function createVideoItem(file, index) {
                    const sizeInMB = (file.size / (1024 * 1024)).toFixed(2);
                    const item = document.createElement('div');
                    item.className = 'video-item';
                    item.dataset.index = index;
                    item.innerHTML = `
            <div class="relative">
                <img class="video-item-thumbnail" src="/assets/img/video-placeholder.svg" alt="Thumbnail">
                <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent rounded-lg flex items-center justify-center opacity-0 hover:opacity-100 transition-opacity duration-200">
                    <svg class="w-8 h-8 text-white" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M8 5v14l11-7z"/>
                    </svg>
                </div>
            </div>
            <div class="video-item-info">
                <div class="video-item-name">${file.name}</div>
                <div class="flex items-center gap-2 mt-1">
                    <span class="video-item-size">${sizeInMB} MB</span>
                    <span class="video-item-separator">•</span>
                    <span class="video-item-progress">Menunggu upload...</span>
                </div>
                <div class="video-item-progress-bar">
                    <div class="video-item-progress-fill" style="width: 0%"></div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="video-item-status status-pending">
                    <svg class="w-3.5 h-3.5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Menunggu
                </span>
                <button type="button" class="btn-remove-video" data-index="${index}">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                    </svg>
                </button>
            </div>
        `;
                    return item;
                }

                // Handle video input change
                videoInput.addEventListener('change', async function(e) {
                    const files = Array.from(e.target.files);
                    if (files.length === 0) return;

                    // Clear previous items
                    videoItems.innerHTML = '';
                    selectedFiles = [];

                    // Validate and create items for each file
                    for (let i = 0; i < files.length; i++) {
                        const file = files[i];
                        const validation = validateVideoFile(file);

                        if (!validation.valid) {
                            alert(`${file.name}: ${validation.error}`);
                            continue;
                        }

                        selectedFiles.push(file);
                        const item = createVideoItem(file, i);
                        videoItems.appendChild(item);

                        // Generate thumbnail asynchronously
                        const thumbnail = item.querySelector('.video-item-thumbnail');
                        try {
                            await generateVideoThumbnail(file, thumbnail);
                        } catch (err) {
                            console.warn('Failed to generate thumbnail for', file.name, err);
                            // Keep placeholder on error - don't set src to avoid blob error
                        }
                    }

                    // Reset input if no valid files
                    if (selectedFiles.length === 0) {
                        videoInput.value = '';
                        return;
                    }

                    // Add remove button listeners
                    qsa('.btn-remove-video', videoItems).forEach(btn => {
                        btn.addEventListener('click', handleRemoveVideo);
                    });
                });

                // Handle remove video
                function handleRemoveVideo(e) {
                    const index = parseInt(e.currentTarget.dataset.index);
                    const item = e.currentTarget.closest('.video-item');

                    if (item) {
                        // Revoke blob URL if exists
                        const thumbnail = item.querySelector('.video-item-thumbnail');
                        if (thumbnail && thumbnail.dataset.blobUrl) {
                            URL.revokeObjectURL(thumbnail.dataset.blobUrl);
                        }

                        item.remove();
                        selectedFiles.splice(index, 1);

                        // Re-index remaining items
                        qsa('.video-item', videoItems).forEach((item, newIndex) => {
                            item.dataset.index = newIndex;
                            const removeBtn = item.querySelector('.btn-remove-video');
                            if (removeBtn) removeBtn.dataset.index = newIndex;
                        });

                        // Update file input
                        if (selectedFiles.length > 0) {
                            const dt = new DataTransfer();
                            selectedFiles.forEach(file => dt.items.add(file));
                            videoInput.files = dt.files;
                        } else {
                            videoInput.value = '';
                        }
                    }
                }

                // Form submission with improved error handling
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

                // Upload with progress tracking
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
                                    status.innerHTML = `
                            <svg class="w-3.5 h-3.5 inline-block mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Uploading
                        `;
                                    progressText.textContent = `${percentComplete}%`;
                                    progressFill.style.width = percentComplete + '%';
                                }
                            });
                        }
                    });

                    xhr.addEventListener('load', function() {
                        if (xhr.status === 200 || xhr.status === 201) {
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
                                    // No videos or all processed, redirect
                                    window.location.href = response.redirect || '/edukasi';
                                }
                            } catch (err) {
                                console.error('Parse error:', err);
                                alert('Terjadi kesalahan saat memproses response. Halaman akan dimuat ulang.');
                                window.location.reload();
                            }
                        } else {
                            let errorMessage = 'Upload gagal. Silakan coba lagi.';
                            try {
                                const errorResponse = JSON.parse(xhr.responseText);
                                if (errorResponse.message) {
                                    errorMessage = errorResponse.message;
                                }
                            } catch (e) {
                                // Use default error message
                            }
                            alert(errorMessage);
                            resetUploadState();
                        }
                    });

                    xhr.addEventListener('error', function() {
                        alert('Terjadi kesalahan jaringan saat upload. Silakan cek koneksi internet Anda.');
                        resetUploadState();
                    });

                    xhr.addEventListener('abort', function() {
                        alert('Upload dibatalkan.');
                        resetUploadState();
                    });

                    xhr.addEventListener('timeout', function() {
                        alert('Upload timeout. File mungkin terlalu besar atau koneksi terlalu lambat.');
                        resetUploadState();
                    });

                    xhr.timeout = 600000; // 10 minutes timeout

                    xhr.open('POST', form.action);
                    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
                    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('input[name="_token"]').value);
                    xhr.send(formData);
                }

                // Poll video processing status with improved error handling
                function startPolling(mediaId, itemElement) {
                    const status = itemElement.querySelector('.video-item-status');
                    const progressText = itemElement.querySelector('.video-item-progress');
                    const progressFill = itemElement.querySelector('.video-item-progress-fill');
                    const thumbnail = itemElement.querySelector('.video-item-thumbnail');

                    status.className = 'video-item-status status-processing';
                    status.innerHTML = `
            <svg class="w-3.5 h-3.5 inline-block mr-1 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            </svg>
            Memproses
        `;
                    progressText.textContent = 'Mengkompresi video...';

                    let pollAttempts = 0;
                    const maxPollAttempts = 300; // 10 minutes max (300 * 2 seconds)

                    pollingIntervals[mediaId] = setInterval(async () => {
                        pollAttempts++;

                        if (pollAttempts > maxPollAttempts) {
                            clearInterval(pollingIntervals[mediaId]);
                            status.className = 'video-item-status status-failed';
                            status.textContent = 'Timeout';
                            progressText.textContent = 'Pemrosesan timeout. Silakan refresh halaman.';
                            return;
                        }

                        try {
                            const response = await fetch(`/edukasi/video-status/${mediaId}`, {
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            });

                            if (!response.ok) {
                                throw new Error(`HTTP ${response.status}`);
                            }

                            const data = await response.json();

                            if (data.status === 'completed') {
                                clearInterval(pollingIntervals[mediaId]);
                                status.className = 'video-item-status status-completed';
                                status.innerHTML = `
                        <svg class="w-3.5 h-3.5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Selesai
                    `;
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
                                status.innerHTML = `
                        <svg class="w-3.5 h-3.5 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                        Gagal
                    `;
                                progressText.textContent = data.error || 'Kompresi gagal';
                                progressFill.style.width = '0%';

                                checkAllCompleted(); // Still check if others are done
                            } else if (data.status === 'processing') {
                                const progress = Math.min(data.progress || 0, 99); // Cap at 99% until completed
                                progressText.textContent = `${progress}%`;
                                progressFill.style.width = progress + '%';

                                // Update thumbnail when available
                                if (data.thumbnail_url && thumbnail.src.includes('placeholder')) {
                                    thumbnail.src = data.thumbnail_url;
                                }
                            }
                        } catch (err) {
                            console.error('Polling error:', err);
                            pollAttempts += 5; // Penalize failed attempts
                        }
                    }, 2000); // Poll every 2 seconds
                }

                // Check if all videos completed processing
                function checkAllCompleted() {
                    const allItems = qsa('.video-item');
                    const allCompleted = allItems.every(item => {
                        const status = item.querySelector('.video-item-status');
                        return status.classList.contains('status-completed') ||
                            status.classList.contains('status-failed');
                    });

                    if (allCompleted) {
                        // Stop all polling
                        Object.values(pollingIntervals).forEach(interval => clearInterval(interval));
                        pollingIntervals = {};

                        const hasFailures = qsa('.status-failed', videoItems).length > 0;

                        if (hasFailures) {
                            submitBtn.textContent = 'Selesai dengan error';
                            submitBtn.disabled = false;
                            alert('Beberapa video gagal diproses. Anda dapat melanjutkan atau mencoba upload ulang video yang gagal.');
                        } else {
                            submitBtn.textContent = 'Selesai! Mengalihkan...';
                            setTimeout(() => {
                                window.location.href = '/edukasi';
                            }, 1500);
                        }
                    }
                }

                // Reset upload state
                function resetUploadState() {
                    isUploading = false;
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'Simpan Konten';
                    uploadProgress.classList.remove('active');
                    uploadProgressFill.style.width = '0%';
                    uploadProgressFill.textContent = '0%';

                    // Clear all polling intervals
                    Object.values(pollingIntervals).forEach(interval => clearInterval(interval));
                    pollingIntervals = {};

                    // Reset video items status
                    qsa('.video-item').forEach(item => {
                        const status = item.querySelector('.video-item-status');
                        const progressText = item.querySelector('.video-item-progress');
                        const progressFill = item.querySelector('.video-item-progress-fill');

                        status.className = 'video-item-status status-pending';
                        status.textContent = 'Menunggu';
                        progressText.textContent = 'Menunggu upload...';
                        progressFill.style.width = '0%';
                    });
                }

                // Image upload handler
                window.fChangeGambar = function(elm, evt) {
                    const input = document.querySelector('#image-input');
                    const preview = document.querySelector('#preview');
                    const dropzone = document.querySelector('#dropzone');

                    preview.innerHTML = '';
                    const files = Array.from(evt.target.files);

                    files.forEach((file, index) => {
                        const reader = new FileReader();
                        reader.onload = (event) => {
                            const img = document.createElement('img');
                            img.src = event.target.result;
                            img.className = 'w-full h-32 object-cover rounded-lg shadow-sm border border-gray-200 ' +
                                (index === 0 ? 'ring-2 ring-indigo-400' : '');
                            preview.appendChild(img);
                        };
                        reader.readAsDataURL(file);
                    });

                    // Drag & drop effects
                    dropzone.addEventListener('dragover', (e) => {
                        e.preventDefault();
                        dropzone.classList.add('border-indigo-400', 'bg-indigo-50');
                    });
                    dropzone.addEventListener('dragleave', () => {
                        dropzone.classList.remove('border-indigo-400', 'bg-indigo-50');
                    });
                    dropzone.addEventListener('drop', (e) => {
                        e.preventDefault();
                        dropzone.classList.remove('border-indigo-400', 'bg-indigo-50');
                        input.files = e.dataTransfer.files;
                        input.dispatchEvent(new Event('change'));
                    });
                };

                // Quill editor initialization
                if (!window._quillTurboBound) {
                    window._quillTurboBound = true;

                    document.addEventListener('turbo:before-render', () => {
                        const oldEditor = document.querySelector('#editor');
                        if (oldEditor) oldEditor.innerHTML = '';
                        window.quillInstance = null;
                    });

                    document.addEventListener('turbo:load', () => {
                        const editorEl = document.querySelector('#editor');
                        const textarea = document.querySelector('#body');

                        if (!editorEl || !textarea) return;
                        if (window.quillInstance) return;

                        const quill = new Quill(editorEl, {
                            theme: 'snow',
                            modules: {
                                toolbar: [
                                    [{
                                        header: [1, 2, 3, false]
                                    }],
                                    ['bold', 'italic', 'underline', 'strike'],
                                    [{
                                        color: []
                                    }, {
                                        background: []
                                    }],
                                    [{
                                        list: 'ordered'
                                    }, {
                                        list: 'bullet'
                                    }],
                                    [{
                                        align: []
                                    }],
                                    ['link', 'image', 'video'],
                                    ['clean']
                                ]
                            },
                            placeholder: 'Tulis konten edukasi di sini...'
                        });

                        quill.on('text-change', () => {
                            textarea.value = quill.root.innerHTML;
                        });

                        if (textarea.value) {
                            quill.root.innerHTML = textarea.value;
                        }

                        window.quillInstance = quill;
                    });
                }

                // Cleanup before cache
                document.addEventListener('turbo:before-cache', function() {
                    const editorContainer = document.querySelector('#editor');
                    if (editorContainer && window.quillInstance) {
                        editorContainer.innerHTML = '';
                        window.quillInstance = null;
                    }

                    // Cleanup video blob URLs
                    qsa('.video-item-thumbnail').forEach(img => {
                        if (img.dataset.blobUrl) {
                            URL.revokeObjectURL(img.dataset.blobUrl);
                        }
                    });
                });

                // Cleanup on page unload
                window.addEventListener('beforeunload', function() {
                    qsa('.video-item-thumbnail').forEach(img => {
                        if (img.dataset.blobUrl) {
                            URL.revokeObjectURL(img.dataset.blobUrl);
                        }
                    });
                });
            })();
        </script>
    </x-slot>
</x-app-layout>