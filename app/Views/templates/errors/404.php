<?php // app/Views/templates/errors/404.php ?>

<section class="auth-section">
    <div class="error-404">
        <i class="bx bx-search-alt bx-remove-padding error-404-icon"></i>
        <h2 class="error-404-title"><?= htmlspecialchars($title ?? 'Page non trouvée') ?></h2>
        <p><?= htmlspecialchars($message ?? "Cette page n'existe pas ou a été déplacée.") ?></p>
        <a href="<?= htmlspecialchars($backUrl ?? '/') ?>">
            <button class="cto-orange error-404-btn"><?= htmlspecialchars($backLabel ?? "Retour à l'accueil") ?></button>
        </a>
    </div>
</section>
