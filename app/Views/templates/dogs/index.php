<?php
$genderLabels = ['male' => 'Mâle', 'female' => 'Femelle', 'unknown' => 'Inconnu'];
?>
<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Mes chiens</h1>
            <p class="page-subtitle">Gérez les profils de vos compagnons</p>
        </div>
        <a href="/dogs/create"><button class="cto-orange btn-sm">
            <i class="bx bx-plus bx-remove-padding"></i> Ajouter un chien
        </button></a>
    </div>
    <?php if (empty($dogs)): ?>
        <div class="dashboard-widget">
            <p class="empty-state">Aucun chien enregistré. <a href="/dogs/create">Ajouter <i class="bx bx-arrow-right bx-remove-padding"></i></a></p>
        </div>
    <?php else: ?>
        <div class="dog-profile-grid">
            <?php foreach ($dogs as $dog): ?>
            <div class="dog-profile-card">
                <div class="dog-profile-card-img">
                    <?php if (!empty($dog['image'])): ?>
                        <img src="/images/<?= htmlspecialchars($dog['image']) ?>" alt="<?= htmlspecialchars($dog['name']) ?>">
                    <?php else: ?>
                        <i class="bx bx-dog bx-remove-padding"></i>
                    <?php endif; ?>
                </div>
                <div class="dog-profile-card-body">
                    <div class="dog-profile-card-title">
                        <h3><?= htmlspecialchars($dog['name']) ?></h3>
                        <a href="/dogs/<?= (int) $dog['id_dog'] ?>/edit" aria-label="Modifier <?= htmlspecialchars($dog['name']) ?>">
                            <i class="bx bx-edit bx-remove-padding"></i>
                        </a>
                    </div>
                    <p class="dog-profile-card-breed"><?= htmlspecialchars($dog['breed'] ?? 'Race non renseignée') ?></p>
                    <div class="dog-profile-card-tags">
                        <span class="dog-tag"><?= htmlspecialchars($dog['age']) ?></span>
                        <span class="dog-tag"><?= htmlspecialchars($genderLabels[$dog['gender']] ?? 'Inconnu') ?></span>
                    </div>
                    <div class="dog-profile-card-stat">
                        <span>Cours suivis</span>
                        <strong><?= (int) $dog['bookings_count'] ?></strong>
                    </div>
                    <div class="dog-profile-card-actions">
                        <a href="/dogs/<?= (int) $dog['id_dog'] ?>/edit" class="dog-profile-card-btn">
                            <i class="bx bx-edit bx-remove-padding"></i> Modifier
                        </a>
                        <a href="/courses" class="dog-profile-card-btn">
                            <i class="bx bx-calendar bx-remove-padding"></i> Cours
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>
