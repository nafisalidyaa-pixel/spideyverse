    </main>
</div>
<script>
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form[method="post"]').forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (event.defaultPrevented) {
                return;
            }

            var tombol = form.querySelector('button[type="submit"]');
            if (!tombol) {
                return;
            }

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
});
</script>
</body>
</html>
