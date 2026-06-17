// =====================================================
// assets/js/app.js — Vanilla JS
// Fitur: Flatpickr Date Picker + Preview Foto Nota
//        + Jenis Radio Card Visual
// =====================================================

document.addEventListener('DOMContentLoaded', function () {

    // --------------------------------------------------
    // 1. Flatpickr — Date Picker (halaman tambah & edit)
    // --------------------------------------------------
    const tanggalInput = document.getElementById('tanggal');
    if (tanggalInput && typeof flatpickr !== 'undefined') {
        flatpickr(tanggalInput, {
            dateFormat : 'Y-m-d',   // format disimpan ke DB
            locale     : 'id',      // locale Indonesia (nama bulan, hari)
            allowInput : false,
            disableMobile: true,    // gunakan flatpickr di mobile juga
            maxDate    : 'today',   // tidak bisa pilih tanggal masa depan
            defaultDate: tanggalInput.value || 'today',
        });
    }

    // --------------------------------------------------
    // 2. Preview Foto Nota
    // --------------------------------------------------
    const fileInput       = document.getElementById('foto_nota');
    const previewContainer = document.getElementById('preview-container');
    const previewImg      = document.getElementById('preview-img');
    const previewName     = document.getElementById('preview-name');
    const uploadPlaceholder = document.getElementById('upload-placeholder');
    const uploadArea      = document.getElementById('upload-area');

    if (fileInput) {
        // Klik / pilih file
        fileInput.addEventListener('change', function () {
            handleFilePreview(this.files[0]);
        });

        // Drag & Drop
        if (uploadArea) {
            uploadArea.addEventListener('dragover', function (e) {
                e.preventDefault();
                this.classList.add('drag-over');
            });
            uploadArea.addEventListener('dragleave', function () {
                this.classList.remove('drag-over');
            });
            uploadArea.addEventListener('drop', function (e) {
                e.preventDefault();
                this.classList.remove('drag-over');
                const file = e.dataTransfer.files[0];
                if (file) {
                    // Transfer ke input file agar ikut ter-submit form
                    const dt = new DataTransfer();
                    dt.items.add(file);
                    fileInput.files = dt.files;
                    handleFilePreview(file);
                }
            });
        }
    }

    /**
     * Tampilkan preview gambar menggunakan FileReader
     * @param {File} file
     */
    function handleFilePreview(file) {
        if (!file) return;

        const allowed = ['image/jpeg', 'image/jpg', 'image/png'];
        const maxSize = 2 * 1024 * 1024; // 2 MB

        // Validasi tipe (client-side, bukan pengganti validasi server)
        if (!allowed.includes(file.type)) {
            alert('File harus berformat JPG, JPEG, atau PNG.');
            fileInput.value = '';
            resetPreview();
            return;
        }

        if (file.size > maxSize) {
            alert('Ukuran file maksimal 2 MB.');
            fileInput.value = '';
            resetPreview();
            return;
        }

        const reader = new FileReader();
        reader.onload = function (e) {
            if (previewImg)   previewImg.src = e.target.result;
            if (previewName)  previewName.textContent = file.name + ' (' + formatBytes(file.size) + ')';
            if (previewContainer)  previewContainer.classList.remove('hidden');
            if (uploadPlaceholder) uploadPlaceholder.classList.add('hidden');
        };
        reader.readAsDataURL(file);
    }

    function resetPreview() {
        if (previewContainer)  previewContainer.classList.add('hidden');
        if (uploadPlaceholder) uploadPlaceholder.classList.remove('hidden');
        if (previewImg)  previewImg.src = '';
        if (previewName) previewName.textContent = '';
    }

    function formatBytes(bytes) {
        if (bytes < 1024)        return bytes + ' B';
        if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(1) + ' KB';
        return (bytes / (1024 * 1024)).toFixed(1) + ' MB';
    }

    // --------------------------------------------------
    // 3. Jenis Radio Card — Visual Active State
    // --------------------------------------------------
    const jenisOptions = document.querySelectorAll('.jenis-option');
    if (jenisOptions.length > 0) {
        // Set state awal berdasarkan checked
        jenisOptions.forEach(function (label) {
            const radio = label.querySelector('input[type="radio"]');
            if (radio && radio.checked) {
                label.querySelector('.jenis-card').classList.add('ring-2', 'ring-offset-2', 'ring-offset-slate-800');
            }

            radio && radio.addEventListener('change', function () {
                // Reset semua
                jenisOptions.forEach(function (l) {
                    l.querySelector('.jenis-card').classList.remove('ring-2', 'ring-offset-2', 'ring-offset-slate-800');
                });
                // Aktifkan yang dipilih
                if (this.checked) {
                    label.querySelector('.jenis-card').classList.add('ring-2', 'ring-offset-2', 'ring-offset-slate-800');
                }
            });
        });
    }

    // --------------------------------------------------
    // 4. Flash Message — Auto-dismiss setelah 4 detik
    // --------------------------------------------------
    const flash = document.querySelector('.flash-msg');
    if (flash) {
        setTimeout(function () {
            flash.style.transition = 'opacity 0.5s ease';
            flash.style.opacity = '0';
            setTimeout(function () { flash.remove(); }, 500);
        }, 4000);
    }

});
