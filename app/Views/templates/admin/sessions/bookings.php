<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Inscrits : <?= htmlspecialchars($session['title']) ?></h1>
            <p class="page-subtitle"><?= htmlspecialchars($session['schedule']) ?> • Coach : <?= htmlspecialchars($session['coach']) ?></p>
        </div>
        <a href="/admin/sessions"><button class="cto-white">Retour aux séances</button></a>
    </div>
    <div class="dashboard-widget">
        <div class="reservations-list">
            <?php if (empty($bookings)): ?>
                <p class="empty-state">Aucune inscription pour cette séance.</p>
            <?php else: ?>
                <?php foreach ($bookings as $b): ?>
                <div class="reservation-item">
                    <div class="reservation-item-dot <?= $b['status'] === 'confirmed' ? 'reservation-item-dot-green' : 'reservation-item-dot-orange' ?>"></div>
                    <div class="reservation-item-info">
                        <strong><?= htmlspecialchars($b['dog_name']) ?></strong>
                        <span>Propriétaire : <?= htmlspecialchars($b['first_name'] . ' ' . $b['last_name']) ?></span>
                    </div>
                    <span class="status-badge <?= $b['status'] === 'confirmed' ? 'status-badge-confirmed' : 'status-badge-pending' ?>">
                        <?= match ($b['status']) {
                            'confirmed' => 'Confirmé',
                            'waiting' => "Liste d'attente",
                            default => 'En attente',
                        } ?>
                    </span>
                    <form method="POST" action="/admin/sessions/<?= (int) $session['id'] ?>/bookings/<?= (int) $b['id_dog'] ?>/cancel"
                          onsubmit="return confirm('Annuler l\'inscription de ce chien ?');" style="display:inline;">
                        <button type="submit" class="cto-orange btn-sm">Annuler</button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
