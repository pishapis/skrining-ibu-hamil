<style>
    .custom-footer {
        background: linear-gradient(90deg,rgba(152, 227, 224, 1) 0%, rgba(167, 237, 236, 1) 71%);
        color: white;
        padding: 10px 20px;
        display: flex;
        justify-content: center; /* Pusat horizontal */
        align-items: center;     /* Pusat vertikal */
        text-align: center;
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 5000;
    }

    .footer-text {
        font-size: 14px;
        color: black;
    }

    .footer-logos img {
        height: 32px;
        width: auto;
        object-fit: contain;
        vertical-align: middle;
        transition: transform 0.2s ease;
    }

    /* Menyembunyikan footer di perangkat dengan ukuran kecil */
    @media (max-width: 767px) {
        .custom-footer {
            display: none !important; /* Pastikan footer disembunyikan di perangkat mobile */
        }
    }
</style>

<footer class="custom-footer relative py-3">
    <div class="footer-text">
        Copyright © 2025 Simkeswa
    </div>
    <div class="footer-logos absolute right-2">
        <img src="{{ asset('/assets/logos/logo simkeswa.png') }}" alt="Logo" class="mx-auto">
    </div>
</footer>
