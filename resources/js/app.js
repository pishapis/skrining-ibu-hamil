import "./bootstrap";

// --- Alpine ---
import Alpine from "alpinejs";
window.Alpine = Alpine;

window.registerWizard = function () {
    return {
        step: 0,
        steps: ["Akun", "Data Ibu", "Riwayat", "Suami & Anak"],
        ibu: { tanggal_lahir: "" },
        jumlahAnak: 0,
        anak: [
            {
                nama: "",
                nik: "",
                tanggal_lahir: "",
                jenis_kelamin_id: "",
                no_jkn: "",
            },
        ],
        next() {
            const sec = this.$refs["s" + this.step];
            const inputs = [...sec.querySelectorAll("input, select, textarea")];
            for (const el of inputs) {
                if (el.required && !el.value) {
                    el.reportValidity();
                    el.focus();
                    return;
                }
            }
            if (this.step < this.steps.length - 1) this.step++;
            if (this.step === 3) this.syncAnakArray();
            window.scrollTo({ top: 0, behavior: "smooth" });
        },
        prev() {
            if (this.step > 0) {
                this.step--;
                window.scrollTo({ top: 0, behavior: "smooth" });
            }
        },
        syncAnakArray() {
            const n = Number(this.jumlahAnak || 0);
            if (n < 0) return;
            while (this.anak.length < n)
                this.anak.push({
                    nama: "",
                    nik: "",
                    tanggal_lahir: "",
                    jenis_kelamin_id: "",
                    no_jkn: "",
                });
            while (this.anak.length > n) this.anak.pop();
        },
        addAnak() {
            this.anak.push({
                nama: "",
                nik: "",
                tanggal_lahir: "",
                jenis_kelamin_id: "",
                no_jkn: "",
            });
            this.jumlahAnak = this.anak.length;
        },
        removeAnak(i) {
            this.anak.splice(i, 1);
            this.jumlahAnak = this.anak.length;
        },
        hitungUmur(d) {
            if (!d) return "";
            const t = new Date(d),
                n = new Date();
            let u = n.getFullYear() - t.getFullYear();
            const m = n.getMonth() - t.getMonth();
            if (m < 0 || (m === 0 && n.getDate() < t.getDate())) u--;
            return u >= 0 ? u : "";
        },
    };
};

window.registerWizardPuskesmas = function () {
    return {
        step: 0,
        steps: ["Akun", "Data Puskesmas"],
        ibu: { tanggal_lahir: "" },
        next() {
            const sec = this.$refs["s" + this.step];
            const inputs = [...sec.querySelectorAll("input, select, textarea")];
            for (const el of inputs) {
                if (el.required && !el.value) {
                    el.reportValidity();
                    el.focus();
                    return;
                }
            }
            if (this.step < this.steps.length - 1) this.step++;
            if (this.step === 3) this.syncAnakArray();
            window.scrollTo({ top: 0, behavior: "smooth" });
        },
        prev() {
            if (this.step > 0) {
                this.step--;
                window.scrollTo({ top: 0, behavior: "smooth" });
            }
        }
    };
};

window.updateWizard = function () {
    return {
        step: 0,
        steps: ["Akun", "Data Ibu"],
        ibu: { tanggal_lahir: "" },
        jumlahAnak: 0,
        next() {
            const sec = this.$refs["s" + this.step];
            const inputs = [...sec.querySelectorAll("input, select, textarea")];
            for (const el of inputs) {
                if (el.required && !el.value) {
                    el.reportValidity();
                    el.focus();
                    return;
                }
            }
            if (this.step < this.steps.length - 1) this.step++;
            window.scrollTo({ top: 0, behavior: "smooth" });
        },
        prev() {
            if (this.step > 0) {
                this.step--;
                window.scrollTo({ top: 0, behavior: "smooth" });
            }
        },
    };
};

Alpine.start();

// --- Swup + Plugins ---
import Swup from "swup";
import FormsPlugin from "@swup/forms-plugin";
import ScrollPlugin from "@swup/scroll-plugin";
import HeadPlugin from "@swup/head-plugin";
import PreloadPlugin from "@swup/preload-plugin";
import ScriptsPlugin from "@swup/scripts-plugin";
import ApexCharts from "apexcharts";

window.ApexCharts = ApexCharts;

const ORIGIN = window.location.origin;

if (window.__swup) {
    try {
        window.__swup.destroy();
    } catch {}
    window.__swup = null;
}

const INTERNAL = (sel) =>
    `${sel}:not([target]):not([download]):not([data-no-swup]):not([rel="external"])`;

window.swup = new Swup({
    animateHistoryBrowsing: true, // animasi juga saat back/forward
    containers: ["#app-frame"],
    plugins: [
        new FormsPlugin({
            formSelector: [
                "form:not([target])",
                ":not([download])",
                ":not([data-no-swup])",
                ':not([enctype="multipart/form-data"])',
            ].join(""),
        }),
        new ScrollPlugin({ animateScroll: true }),
        new HeadPlugin({
            persistTags: (tag) => {
                const src = tag.getAttribute?.("src") || "";
                const href = tag.getAttribute?.("href") || "";
                return (
                    src.includes("/@vite/client") ||
                    href.includes("/@vite/client") ||
                    src.includes("/build/") ||
                    href.includes("/build/") ||
                    tag.getAttribute?.("rel") === "modulepreload"
                );
            },
            awaitAssets: true, // biar CSS/JS siap sebelum animasi masuk
        }),
        new PreloadPlugin({ preloadVisibleLinks: true, throttle: 5 }),
        new ScriptsPlugin({ head: false }),
    ],
    linkSelector: [
        INTERNAL(`a[href^="${ORIGIN}"]`), // absolute internal
        INTERNAL('a[href^="/"]'), // relative internal
    ].join(", "),
});

window.__swup = swup;

// Re-init Alpine setelah konten baru dirender
const reinit = () => {
    const el = document.getElementById("app-frame");
    if (!el || !window.Alpine) return;
    requestAnimationFrame(() => window.Alpine.initTree(el));
};

// ---- Progress bar sederhana (mirip Livewire) ----
const bar = document.getElementById("swup-progress");

const progressStart = () => {
    if (!bar) return;
    bar.style.transition = "none";
    bar.style.width = "0%";
    bar.style.opacity = "1";
    // biar animasi width berjalan
    requestAnimationFrame(() => {
        bar.style.transition = "width 1.2s ease, opacity .2s ease";
        bar.style.width = "80%";
    });
};

const progressEnd = () => {
    if (!bar) return;
    bar.style.transition = "width .2s ease, opacity .2s ease";
    bar.style.width = "100%";
    setTimeout(() => {
        bar.style.opacity = "0";
    }, 200);
};

function readSeed(id = "dashboard-seed") {
    const el = document.getElementById(id);
    if (!el) return null;
    try {
        return JSON.parse(el.textContent || "{}");
    } catch {
        return null;
    }
}

function cleanupScrollLocks() {
  const unlockClasses = [
    'overflow-hidden', 'overflow-y-hidden', 'overflow-x-hidden',
    'fixed', 'is-changing', 'is-animating' // kalau CSS kamu pakai ini untuk lock
  ];
  unlockClasses.forEach(c => {
    document.documentElement.classList.remove(c);
    document.body.classList.remove(c);
  });

  // Pastikan tidak ada style inline yang mengunci
  document.documentElement.style.overflow = '';
  document.body.style.overflow = '';
}

function hydrateDashboard() {
    const seed = readSeed();
    if (!seed) return;
    if (typeof window.initAll === "function") {
        try {
            window.initAll(seed.epdsTrend || []);
        } catch {}
    } 
}
hydrateDashboard();

window.filterKota = async function (provId) {
    try {
        const requestData = {
            provId: provId,
        };
        const routeUrl = "/get-kota"; 
        const fetchKota = new Fetch(routeUrl);
        fetchKota.method = "GET";
        fetchKota.bodyObject = requestData;
        let optionValue = "";
        const hasil = await fetchKota.run();
        if (hasil.ack === "ok") { 
            const kotaSelect = document.querySelector('select[name="kota_id"]');
            const kotaSelectRujukan = document.querySelector('select[name="kota_id_rujukan"]');
            const kotaSelectPuskesmas = document.querySelector('select[name="kota_id_puskesmas"]');

            let option = `<option value="" selected disabled>Pilih Kabupaten/Kota</option>`;

            if(kotaSelect)kotaSelect.innerHTML = option;
            if(kotaSelectRujukan)kotaSelectRujukan.innerHTML = option;
            if(kotaSelectPuskesmas)kotaSelectPuskesmas.innerHTML = option;

            hasil.data.forEach((kota) => {
              optionValue = optionValue + `<option value="${kota.code}">${kota.name}</option>`;

              if(kotaSelect)kotaSelect.innerHTML = optionValue
              if(kotaSelectRujukan)kotaSelectRujukan.innerHTML = optionValue
              if(kotaSelectPuskesmas)kotaSelectPuskesmas.innerHTML = optionValue
            });
        } else {
            ALERT(hasil.message, hasil.ack);
        }
    } catch (error) {
        console.log("🚀 ~ filterKota ~ error:", error);
    }
}

window.filterKec = async function (kotaId) {
    try {
        const requestData = {
            kotaId: kotaId,
        };
        const routeUrl = "/get-kecamatan";
        const fetchKec = new Fetch(routeUrl);
        fetchKec.method = "GET";
        fetchKec.bodyObject = requestData;
        let optionValue = "";
        const hasil = await fetchKec.run();
        if (hasil.ack === "ok") {
            const kecSelect = document.querySelector('select[name="kec_id"]');
            const kecSelectRujukan = document.querySelector('select[name="kec_id_rujukan"]');
            const kecSelectPuskesmas = document.querySelector('select[name="kec_id_puskesmas"]');

            let option = `<option value="" selected disabled>Pilih Kecamatan</option>`;

            if(kecSelect)kecSelect.innerHTML = option;
            if(kecSelectRujukan)kecSelectRujukan.innerHTML = option;
            if(kecSelectPuskesmas)kecSelectPuskesmas.innerHTML = option;

            hasil.data.forEach((kec) => {
              optionValue = optionValue + `<option value="${kec.code}">${kec.name}</option>`;

              if(kecSelect)kecSelect.innerHTML = optionValue
              if(kecSelectRujukan)kecSelectRujukan.innerHTML = optionValue
              if(kecSelectPuskesmas)kecSelectPuskesmas.innerHTML = optionValue
            });
        } else {
            ALERT(hasil.message, hasil.ack);
        }
    } catch (error) {
        console.log("🚀 ~ filterKec ~ error:", error);
    }
}

window.filterKel = async function (kecId) {
    try {
        const requestData = {
            kecId: kecId,
        };
        const routeUrl = "/get-desa";
        const fetchKel = new Fetch(routeUrl);
        fetchKel.method = "GET";
        fetchKel.bodyObject = requestData;
        const hasil = await fetchKel.run();
        if (hasil.ack === "ok") {
            const kelurahan = hasil.data.kelurahan;
            const puskesmas = hasil.data.puskesmas;

            const kelSelect = document.querySelector(
                'select[name="kelurahan_id"]'
            );
            kelSelect.innerHTML =
                '<option value="" selected disabled>Pilih Kelurahan</option>';
            kelurahan.forEach((kel) => {
                kelSelect.innerHTML += `<option value="${kel.code}">${kel.name}</option>`;
            });

            const puskesmasSelect = document.querySelector(
                'select[name="puskesmas_id"]'
            );
            puskesmasSelect.innerHTML =
                '<option value="" selected disabled>Pilih Puskesmas</option>';
            puskesmas.forEach((puskesmas) => {
                puskesmasSelect.innerHTML += `<option value="${puskesmas.id}">${puskesmas.nama}</option>`;
            });
        } else {
            ALERT(hasil.message, hasil.ack);
        }
    } catch (error) {
        console.log("🚀 ~ filterKel ~ error:", error);
    }
}

window.filterFaskesRujukan = async function () {
    try {
        const kota = document.querySelector('select[name="kota_id"]').value;
        const requestData = {
            kota_id: kota,
        };

        const routeUrl = "/get-faskes";
        const fetchKel = new Fetch(routeUrl);
        fetchKel.method = "GET";
        fetchKel.bodyObject = requestData;

        const hasil = await fetchKel.run();

        if (hasil.ack === "ok") {
            const faskesSelect = document.querySelector(
                'select[name="faskes_rujukan_id"]'
            );

            // reset isi select
            faskesSelect.innerHTML = "";
            const defaultOpt = new Option("Pilih Rujukan", "", true, false);
            defaultOpt.disabled = true;
            faskesSelect.add(defaultOpt);

            const seen = new Set(); // untuk mendeteksi duplikat
            const frag = document.createDocumentFragment();

            (hasil.data || []).forEach((item) => {
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

swup.hooks.on("page:view", () => {
    hydrateDashboard();
});
swup.hooks.on('page:view', () => {
  cleanupScrollLocks();
});
// swup.hooks.on('visit:start', () => { try { swup.cache.clear() } catch {} })
swup.hooks.on("page:view", reinit);
swup.hooks.on("visit:start", progressStart); // klik/link/submit dimulai
swup.hooks.on("page:view", progressEnd); // halaman baru siap terlihat
swup.hooks.on("visit:end", progressEnd); // fallback selesai
