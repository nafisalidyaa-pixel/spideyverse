    </main>
</div>
<script>
// Semua script jalan setelah halaman selesai dimuat.
document.addEventListener('DOMContentLoaded', function () {
    // Kasih efek loading ke tombol submit biar user tahu proses jalan.
    document.querySelectorAll('form[method="post"]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (event.defaultPrevented) {
                return;
            }

            var tombol = form.querySelector('button[type="submit"]');
            if (!tombol) {
                return;
            }

            // Kasih jeda sedikit supaya perubahan tombol kelihatan.
            window.setTimeout(function () {
                if (tombol.disabled) {
                    return;
                }
                tombol.classList.add('btn-loading');
                tombol.disabled = true;
                tombol.textContent = 'Memproses...';
            }, 20);
        });
    });

    // Efek ripple waktu tombol/menu diklik.
    document.addEventListener('click', function (event) {
        var target = event.target.closest('.btn, .notif-ok, .menu a');
        if (!target) {
            return;
        }

        // Bikin lingkaran ripple sesuai posisi klik.
        var kotak = target.getBoundingClientRect();
        var ukuran = Math.max(kotak.width, kotak.height);
        var riak = document.createElement('span');
        riak.className = 'riak';
        riak.style.width = ukuran + 'px';
        riak.style.height = ukuran + 'px';
        riak.style.left = (event.clientX - kotak.left - ukuran / 2) + 'px';
        riak.style.top = (event.clientY - kotak.top - ukuran / 2) + 'px';

        target.appendChild(riak);
        // Hapus ripple setelah animasinya selesai.
        window.setTimeout(function () {
            riak.remove();
        }, 620);
    });
});
</script>
</body>
</html>
