<footer class="py-5 mt-5 border-top bg-white">
    <div class="container text-center">
        <div class="brand-heart mx-auto mb-3"><i class="bi bi-heart-fill"></i></div>
        <p class="fw-semibold mb-1">Obrigado por fazer parte dessa luta conosco. 💙</p>
        <p class="text-secondary small mb-0">Cada participação e cada compartilhamento aproximam o Pedro do seu respirador portátil.</p>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
<?php $appJsVersion = @filemtime(__DIR__ . '/../assets/js/app.js') ?: '1'; ?>
<script src="<?= e(url('/assets/js/app.js')) ?>?v=<?= e((string) $appJsVersion) ?>"></script>
</body>
</html>