<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Séances de cours</h1>
            <p class="page-subtitle">Gérez les séances proposées aux membres</p>
        </div>
        <a href="/admin/sessions/create"><button class="cto-orange">Nouvelle séance</button></a>
    </div>
    <div class="dashboard-widget">
        <div class="reservations-list">
            <?php if (empty($sessions)): ?>
                <p class="empty-state">Aucune séance pour le moment.</p>
            <?php else: ?>
                <?php foreach ($sessions as $s): ?>
                <div class="reservation-item">
                    <div class="reservation-item-dot <?= $s['status'] === 'open' ? 'reservation-item-dot-green' : 'reservation-item-dot-orange' ?>"></div>
                    <div class="reservation-item-info">
                        <strong><?= htmlspecialchars($s['title']) ?></strong>
                        <span><?= htmlspecialchars($s['schedule']) ?> • Coach : <?= htmlspecialchars($s['coach']) ?> • <?= htmlspecialchars($s['places']) ?> places</span>
                    </div>
                    <span class="status-badge <?= $s['status'] === 'open' ? 'status-badge-confirmed' : 'status-badge-pending' ?>"><?= htmlspecialchars($s['status']) ?></span>
                    <?php if (($_SESSION['user']['role'] ?? null) === 'administrator' || $s['coach_id'] === (int) $_SESSION['user']['id']): ?>
                        <a href="/admin/sessions/<?= $s['id'] ?>/bookings"><button class="cto-white btn-sm">Inscrits</button></a>
                        <a href="/admin/sessions/<?= $s['id'] ?>/edit"><button class="cto-green btn-sm">Modifier</button></a>
                        <form method="POST" action="/admin/sessions/<?= $s['id'] ?>/delete"
                              onsubmit="return confirm('Supprimer cette séance ? Les inscriptions liées seront aussi supprimées.');" style="display:inline;">
                            <button type="submit" class="cto-orange btn-sm">Supprimer</button>
                        </form>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
