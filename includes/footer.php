<?php
// includes/footer.php
?>
        </div><!-- /p-6 lg:p-8 -->
    </main>
</div><!-- /flex min-h-screen -->

<script>
    // Fallback toggle sidebar (biar tetap jalan walau main.js gagal load)
    function toggleSidebar() {
        const sb = document.getElementById('sidebar');
        const ov = document.getElementById('overlay');
        if (!sb || !ov) return;
        sb.classList.toggle('-translate-x-full');
        ov.classList.toggle('hidden');
    }
</script>
<script src="<?= base_url('assets/js/main.js') ?>"></script>
</body>
</html>