<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Types de cours</h1>
            <p class="page-subtitle">Gérez les catégories de cours proposées</p>
        </div>
        <a href="/admin/course-types/create"><button class="cto-orange">Nouveau type</button></a>
    </div>
    <div class="dashboard-widget">
        <div class="reservations-list">
            <?php if (empty($courseTypes)): ?>
                <p class="empty-state">Aucun type de cours pour le moment.</p>
            <?php else: ?>
                <?php foreach ($courseTypes as $ct): ?>
                <div class="reservation-item">
                    <div class="reservation-item-dot reservation-item-dot-orange"></div>
                    <div class="reservation-item-info">
                        <strong><?= htmlspecialchars($ct['name']) ?></strong>
                        <span><?= htmlspecialchars($ct['age_label']) ?></span>
                    </div>
                    <a href="/admin/course-types/<?= $ct['id_course_type'] ?>/edit"><button class="cto-green btn-sm">Modifier</button></a>
                    <form method="POST" action="/admin/course-types/<?= $ct['id_course_type'] ?>/delete"
                          onsubmit="return confirm('Supprimer ce type de cours ?');" style="display:inline;">
                        <button type="submit" class="cto-orange btn-sm">Supprimer</button>
                    </form>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>
