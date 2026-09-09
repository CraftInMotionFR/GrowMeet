<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Coachs</h1>
            <p class="page-subtitle">Gérez les comptes coachs / instructeurs</p>
        </div>
        <a href="/admin/coaches/create"><button class="cto-orange">Nouveau coach</button></a>
    </div>
    <div class="dashboard-widget">
        <div class="reservations-list">
            <?php if (empty($coaches)): ?>
                <p class="empty-state">Aucun coach pour le moment.</p>
            <?php else: ?>
                <?php foreach ($coaches as $coach): ?>
                <div class="reservation-item">
                    <div class="reservation-item-dot reservation-item-dot-green"></div>
                    <div class="reservation-item-info">
                        <strong><?= htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']) ?></strong>
                        <span><?= htmlspecialchars($coach['email']) ?></span>
                    </div>
                    <form method="POST" action="/admin/coaches/<?= $coach['id_user'] ?>/delete"
                          onsubmit="return confirm('Supprimer ce coach ?');" style="display:inline;">
                        <button type="submit" class="cto-orange btn-sm">Supprimer</button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
