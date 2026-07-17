<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Mon profil</h1>
            <p class="page-subtitle">Vos informations de compte</p>
        </div>
    </div>
    <div class="dashboard-widget">
        <div class="widget-header">
            <h2 class="widget-title">Informations du compte</h2>
        </div>
        <div class="summary-card">
            <div class="summary-section">
                <span class="summary-label">Identité</span>
                <div class="summary-row">
                    <i class="bx bx-user bx-remove-padding"></i>
                    <span><?= htmlspecialchars($user['firstname'] . ' ' . $user['lastname']) ?></span>
                </div>
                <div class="summary-row">
                    <i class="bx bx-envelope bx-remove-padding"></i>
                    <span><?= htmlspecialchars($user['email']) ?></span>
                </div>
                <div class="summary-row">
                    <i class="bx bx-id-card bx-remove-padding"></i>
                    <span><?= htmlspecialchars($role_label) ?></span>
                </div>
            </div>
        </div>
        <div class="form-actions">
            <a href="/logout"><button class="cto-orange">Déconnexion</button></a>
        </div>
    </div>
</div>
