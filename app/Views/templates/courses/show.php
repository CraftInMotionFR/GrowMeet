<?php
$eligibleDogs = $eligibleDogs ?? [];
$bookedDogs = $bookedDogs ?? [];
$ineligibleDogs = $ineligibleDogs ?? [];
$dogNames = fn(array $list) => htmlspecialchars(implode(', ', array_column($list, 'name')));
?>
<div class="container">
    <a href="/courses" class="back-link">
        <i class="bx bx-arrow-left-stroke bx-remove-padding"></i>
        Retour au catalogue
    </a>
    <div class="course-detail-hero">
        <img src="/images/<?= htmlspecialchars($course['image']) ?>" alt="<?= htmlspecialchars($course['title']) ?>">
        <span class="course-badge <?= $course['places'] <= 0 ? 'course-badge-full' : 'course-badge-available' ?>">
            <?= $course['places'] <= 0 ? 'Complet' : $course['places'] . '/' . ($course['max_participants'] ?? '-') . ' places' ?>
        </span>
    </div>
    <div class="course-detail-header">
        <div>
            <h1 class="page-title course-detail-title"><?= htmlspecialchars($course['title']) ?></h1>
            <p class="page-subtitle course-detail-desc"><?= htmlspecialchars($course['desc']) ?></p>
        </div>
    </div>
    <?php if (!empty($eligibleDogs)): ?>
        <div class="course-alert course-alert-ok">
            <i class="bx bx-check-circle bx-remove-padding"></i>
            <div>
                <strong>Éligibilité confirmée :</strong>
                <span><?= $dogNames($eligibleDogs) ?> <?= count($eligibleDogs) > 1 ? 'peuvent participer' : 'peut participer' ?> à ce cours</span>
            </div>
        </div>
    <?php endif; ?>
    <?php if (!empty($ineligibleDogs)): ?>
        <div class="course-alert">
            <i class="bx bx-error-circle bx-remove-padding"></i>
            <div>
                <strong>Âge non conforme :</strong>
                <span><?= $dogNames($ineligibleDogs) ?> <?= count($ineligibleDogs) > 1 ? 'ne correspondent pas' : 'ne correspond pas' ?>
                    à la tranche d'âge requise (<?= htmlspecialchars($course['age']) ?>)</span>
            </div>
        </div>
    <?php endif; ?>
    <div class="course-detail-infogrid">
        <div class="course-detail-infocard">
            <i class="bx bx-calendar bx-remove-padding icon-orange"></i>
            <div>
                <p class="course-detail-infolabel">Horaire</p>
                <p class="course-detail-infovalue"><?= htmlspecialchars($course['day']) ?> <?= htmlspecialchars($course['time_range']) ?></p>
            </div>
        </div>
        <div class="course-detail-infocard">
            <i class="bx bx-dog bx-remove-padding icon-orange"></i>
            <div>
                <p class="course-detail-infolabel">Âge requis</p>
                <p class="course-detail-infovalue"><?= htmlspecialchars($course['age']) ?></p>
            </div>
        </div>
        <div class="course-detail-infocard">
            <i class="bx bx-clock bx-remove-padding icon-orange"></i>
            <div>
                <p class="course-detail-infolabel">Durée</p>
                <p class="course-detail-infovalue"><?= htmlspecialchars($course['duration']) ?></p>
            </div>
        </div>
    </div>
    <div class="course-detail-coach">
        <div class="course-detail-coach-avatar">
            <i class="bx bx-user bx-remove-padding"></i>
        </div>
        <div>
            <h4><?= htmlspecialchars($course['coach']) ?></h4>
            <p>Coach de ce cours</p>
        </div>
    </div>
    <div class="course-info-grid">
        <div class="dashboard-widget">
            <h4 class="course-todo-title">Objectifs du cours</h4>
            <ul class="course-goal-list">
                <?php foreach ($course['objectives'] as $goal): ?>
                    <li><i class="bx bx-check bx-remove-padding"></i> <?= htmlspecialchars($goal) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <div class="dashboard-widget">
            <h4 class="course-todo-title">À prévoir</h4>
            <ul class="course-todo-list">
                <li>Carnet de vaccination à jour</li>
                <li>Laisse et collier adaptés</li>
                <li>Friandises pour récompenses</li>
            </ul>
        </div>
    </div>
    <div class="dashboard-widget">
        <h4 class="course-todo-title">Lieu du cours</h4>
        <div class="course-location">
            <i class="bx bx-map bx-remove-padding icon-orange"></i>
            <span><?= htmlspecialchars($course['location']) ?></span>
        </div>
    </div>
    <?php if ($isMember && empty($eligibleDogs)): ?>
        <div class="course-notice">
            <i class="bx bx-error-circle bx-remove-padding"></i>
            <p>Aucun de vos chiens n'est éligible pour ce cours. <a href="/dogs/create">Ajoutez un chien</a> correspondant à la tranche d'âge ou <a href="/courses">consultez d'autres cours</a>.</p>
        </div>
    <?php endif; ?>
    <?php if ($isMember && !empty($bookedDogs)): ?>
        <div class="course-notice">
            <i class="bx bx-check-circle bx-remove-padding"></i>
            <p><?= $dogNames($bookedDogs) ?> <?= count($bookedDogs) > 1 ? 'sont déjà inscrits' : 'est déjà inscrit' ?> à ce cours.</p>
        </div>
    <?php endif; ?>
    <?php if ($isMember && !empty($bookedDogs)): ?>
        <div class="course-cancel-list">
            <?php foreach ($bookedDogs as $bd): ?>
                <form method="POST" action="/courses/<?= (int) $course['id'] ?>/cancel"
                      onsubmit="return confirm('Se désinscrire de ce cours pour <?= htmlspecialchars(addslashes($bd['name']), ENT_QUOTES) ?> ?');">
                    <input type="hidden" name="dog_id" value="<?= (int) $bd['id_dog'] ?>">
                    <button type="submit" class="cto-white btn-full">
                        <i class="bx bx-x bx-remove-padding"></i> Se désinscrire<?= count($bookedDogs) > 1 || count($eligibleDogs) > 1 ? ' (' . htmlspecialchars($bd['name']) . ')' : '' ?>
                    </button>
                </form>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($isMember && !empty($eligibleDogs)): ?>
        <form method="POST" action="/courses/<?= (int) $course['id'] ?>/join" class="course-actions">
            <?php include __DIR__ . '/dog_select.php'; ?>
            <div class="course-actions-row">
                <?php if (empty($dogs)): ?>
                    <button type="button" class="cto-orange btn-disabled" disabled>Déjà inscrit(e)</button>
                <?php else: ?>
                    <button type="submit" class="cto-orange <?= $course['places'] <= 0 ? 'btn-disabled' : '' ?>">
                        <?= $course['places'] <= 0 ? "Rejoindre la liste d'attente" : "S'inscrire au cours" ?>
                    </button>
                <?php endif; ?>
                <button type="button" class="cto-white btn-disabled" disabled title="Bientôt disponible">Contacter le coach</button>
            </div>
        </form>
    <?php else: ?>
        <div class="course-actions">
            <div class="course-actions-row">
                <button type="button" class="cto-white btn-disabled" disabled title="Bientôt disponible">Contacter le coach</button>
            </div>
        </div>
    <?php endif; ?>
</div>
