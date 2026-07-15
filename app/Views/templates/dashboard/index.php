<?php ?>

<div class="container">
    <!-- Bienvenue -->
    <div class="dashboard-welcome">
        <div>
            <h1 class="page-title">Bonjour, <?= htmlspecialchars($user['firstname'] ?? 'là') ?> ! 👋</h1>
            <p class="page-subtitle">Bienvenue sur votre tableau de bord</p>
        </div>
    </div>
    <!-- Stats rapides -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-card-icon stat-card-icon-orange">
                <i class="bx bx-book-open bx-remove-padding icon-orange"></i>
            </div>
            <div class="stat-card-body">
                <span class="stat-card-value"><?= (int) $stats['bookings_count'] ?></span>
                <span class="stat-card-label">Cours suivis</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon stat-card-icon-green">
                <i class="bx bx-calendar bx-remove-padding icon-green"></i>
            </div>
            <div class="stat-card-body">
                <span class="stat-card-value"><?= (int) $stats['upcoming_sessions'] ?></span>
                <span class="stat-card-label">Prochaines séances</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon stat-card-icon-orange">
                <i class="bx bx-clock bx-remove-padding icon-orange"></i>
            </div>
            <div class="stat-card-body">
                <span class="stat-card-value"><?= (int) $stats['training_hours'] ?>h</span>
                <span class="stat-card-label">Heures d'entraînement</span>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-icon stat-card-icon-green">
                <i class="bx bx-medal-alt bx-remove-padding icon-green"></i>
            </div>
            <div class="stat-card-body">
                <span class="stat-card-value"><?= (int) $stats['badges_count'] ?></span>
                <span class="stat-card-label">Certifications</span>
            </div>
        </div>
    </div>
    <!-- Layout principal -->
    <div class="dashboard-grid dashboard-grid-even">
        <!-- Colonne gauche : Mes chiens -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <div>
                    <h2 class="widget-title">Mes chiens</h2>
                    <p class="widget-subtitle">Gérez les profils de vos compagnons</p>
                </div>
                <a href="/dogs/create"><button class="cto-orange btn-sm">
                    <i class="bx bx-plus bx-remove-padding"></i> Ajouter
                </button></a>
            </div>
            <div class="dogs-list">
                <?php if (empty($dogs)): ?>
                    <p class="empty-state">Aucun chien enregistré. <a href="/dogs/create">Ajouter <i class="bx bx-arrow-right bx-remove-padding"></i></a></p>
                <?php else: ?>
                    <?php foreach ($dogs as $dog): ?>
                    <a href="/dogs/<?= (int) $dog['id_dog'] ?>/edit" class="dogs-list-item" title="Modifier <?= htmlspecialchars($dog['name']) ?>">
                        <div class="dogs-list-item-avatar">
                            <?php if (!empty($dog['image'])): ?>
                                <img src="/images/<?= htmlspecialchars($dog['image']) ?>"
                                     alt="<?= htmlspecialchars($dog['name']) ?>">
                            <?php else: ?>
                                <i class="bx bx-dog bx-remove-padding"></i>
                            <?php endif; ?>
                        </div>
                        <div class="dogs-list-item-info">
                            <h3><?= htmlspecialchars($dog['name']) ?></h3>
                            <p><?= htmlspecialchars($dog['breed'] ?? '') ?></p>
                        </div>
                        <span class="dog-tag"><?= htmlspecialchars($dog['age'] ?? '') ?></span>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
        <!-- Colonne droite : Prochaines séances -->
        <div class="dashboard-widget">
            <div class="widget-header">
                <div>
                    <h2 class="widget-title">Prochaines séances</h2>
                    <p class="widget-subtitle">Vos cours à venir</p>
                </div>
            </div>
            <div class="upcoming-list">
                <?php if (empty($upcoming)): ?>
                    <p class="empty-state">Aucun cours à venir. <a href="/courses">Réserver <i class="bx bx-arrow-right bx-remove-padding"></i></a></p>
                <?php else: ?>
                    <?php foreach ($upcoming as $session): ?>
                    <a href="/courses/<?= (int) $session['id_course'] ?>" class="upcoming-item upcoming-item-link">
                        <div class="upcoming-item-header">
                            <div>
                                <h3><?= htmlspecialchars($session['title']) ?></h3>
                                <p>avec <?= htmlspecialchars($session['dog']) ?></p>
                            </div>
                            <span class="status-badge <?= $session['status'] === 'confirmed' ? 'status-badge-confirmed' : 'status-badge-pending' ?>">
                                <?= $session['status'] === 'confirmed' ? 'Confirmé' : 'En attente' ?>
                            </span>
                        </div>
                        <div class="course-detail">
                            <i class="bx bx-calendar bx-remove-padding"></i>
                            <span><?= htmlspecialchars($session['day_label']) ?></span>
                        </div>
                        <div class="course-detail">
                            <i class="bx bx-time bx-remove-padding"></i>
                            <span><?= htmlspecialchars($session['time_range']) ?></span>
                        </div>
                        <div class="course-detail">
                            <span>Coach: <?= htmlspecialchars($session['coach']) ?></span>
                        </div>
                    </a>
                    <?php endforeach; ?>
                <?php endif; ?>
                <a class="widget-link widget-button nav-link-disabled" aria-disabled="true" title="Bientôt disponible">Voir le calendrier complet</a>
            </div>
        </div>
    </div>
</div>