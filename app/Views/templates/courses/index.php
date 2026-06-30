<?php // app/Views/templates/courses/index.php ?>
<div class="container">
    <div class="page-header">
        <div>
            <h1 class="page-title">Catalogue des cours</h1>
            <p class="page-subtitle">Découvrez nos cours adaptés à tous les âges et niveaux</p>
        </div>
    </div>
    <!-- Recherche + filtres -->
    <div class="courses-filters">
        <div class="search-bar">
            <i class="bx bx-search bx-remove-padding search-icon"></i>
            <input type="text" id="search-input" placeholder="Rechercher un cours..." oninput="filterCourses()">
        </div>
        <div class="filter-tags">
            <span class="filter-tag filter-tag-active" data-filter="all" onclick="setFilter(this,'all')">Tous</span>
            <?php
            $filters = [
                'chiot' => 'École du chiot',
                '6-12' => 'Éducation 6-12 mois',
                '1-2' => 'Éducation 1-2 ans',
                '2+' => 'Éducation +2 ans',
                'socialisation' => 'Socialisation',
                'sport' => 'Parcours sportifs',
                'dressage' => 'Dressage',
            ];
            foreach ($filters as $key => $label): ?>
                <span class="filter-tag" data-filter="<?= $key ?>" onclick="setFilter(this,'<?= $key ?>')">
                    <?= htmlspecialchars($label) ?>
                </span>
            <?php endforeach; ?>
        </div>
    </div>
    <p class="courses-count" id="courses-count"><?= count($courses) ?> cours</p>
    <!-- Grille -->
    <div class="courses-grid" id="courses-grid">
        <?php foreach ($courses as $course):
            if ($course['places'] <= 0) {
                $badgeClass = 'course-badge-full';
                $badgeText = 'Complet';
            } elseif ($course['places'] <= 2) {
                $badgeClass = 'course-badge-low';
                $badgeText = $course['places'] . ' place' . ($course['places'] > 1 ? 's' : '');
            } else {
                $badgeClass = 'course-badge-available';
                $badgeText = $course['places'] . ' places';
            }
            $isFull = $course['places'] <= 0;
        ?>
        <div class="course-card <?= $isFull ? 'course-card-full' : '' ?>"
             data-category="<?= htmlspecialchars($course['category']) ?>"
             data-title="<?= strtolower(htmlspecialchars($course['title'])) ?>">
            <a href="/courses/<?= (int) $course['id'] ?>" class="course-card-link">
                <div class="course-card-img">
                    <img src="/images/<?= htmlspecialchars($course['image']) ?>"
                         alt="<?= htmlspecialchars($course['title']) ?>">
                    <span class="course-badge <?= $badgeClass ?>"><?= $badgeText ?></span>
                </div>
                <div class="course-card-body">
                    <h3 class="course-card-title"><?= htmlspecialchars($course['title']) ?></h3>
                    <p class="course-card-desc"><?= htmlspecialchars($course['desc']) ?></p>
                </div>
            </a>
            <div class="course-footer">
                <div class="course-detail">
                    <i class="bx bx-dog bx-remove-padding"></i>
                    <span>Âge requis : <?= htmlspecialchars($course['age']) ?></span>
                </div>
                <div class="course-detail">
                    <i class="bx bx-clock bx-remove-padding"></i>
                    <span><?= htmlspecialchars($course['schedule']) ?></span>
                </div>
                <div class="course-detail">
                    <span class="coach-info">Coach : <strong><?= htmlspecialchars($course['coach']) ?></strong></span>
                </div>
                <form method="POST" action="/courses/<?= $course['id'] ?>/join">
                    <?php include __DIR__ . '/dog_select.php'; ?>
                    <button type="submit" class="cto-orange btn-full <?= $isFull ? 'btn-disabled' : '' ?>">
                        <?= $isFull ? "Liste d'attente" : "S'inscrire" ?>
                    </button>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <div class="no-results" id="no-results">
        <i class="bx bx-search bx-remove-padding icon-search-empty"></i>
        <p>Aucun cours trouvé pour votre recherche.</p>
    </div>
</div>
