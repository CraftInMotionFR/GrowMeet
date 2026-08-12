<?php // app/Views/templates/admin/course-types/create.php
$old = $old ?? [];
?>

<section class="auth-section">
    <div class="auth-container">
        <div class="auth-card auth-card-wide">
            <div class="auth-header">
                <h1>Nouveau type de cours</h1>
                <p>Définissez une catégorie de cours et ses conditions d'âge</p>
            </div>
            <?php if (!empty($errors)): ?>
                <div class="form-errors">
                    <?php foreach ($errors as $error): ?>
                        <p class="form-error"><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form class="auth-form" method="POST" action="/admin/course-types">
                <div class="form-group">
                    <label for="ct-name">Nom du type de cours</label>
                    <div class="input-wrapper">
                        <i class="bx bx-book-open bx-remove-padding input-icon"></i>
                        <input type="text" id="ct-name" name="name" placeholder="École du chiot"
                               value="<?= htmlspecialchars($old['name'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="ct-description">Description</label>
                    <div class="input-wrapper">
                        <i class="bx bx-text bx-remove-padding input-icon"></i>
                        <input type="text" id="ct-description" name="description" placeholder="Description courte"
                               value="<?= htmlspecialchars($old['description'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="ct-age-min">Âge minimum (mois)</label>
                        <div class="input-wrapper">
                            <i class="bx bx-calendar-alt bx-remove-padding input-icon"></i>
                            <input type="number" id="ct-age-min" name="age_min_months" min="0" placeholder="0"
                                   value="<?= htmlspecialchars($old['age_min_months'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="ct-age-max">Âge maximum (mois, vide = pas de limite)</label>
                        <div class="input-wrapper">
                            <i class="bx bx-calendar-alt bx-remove-padding input-icon"></i>
                            <input type="number" id="ct-age-max" name="age_max_months" min="0" placeholder="Pas de limite"
                                   value="<?= htmlspecialchars($old['age_max_months'] ?? '') ?>">
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="/admin/course-types"><button type="button" class="cto-green">Annuler</button></a>
                    <button type="submit" class="cto-orange">Créer</button>
                </div>
            </form>
        </div>
    </div>
</section>
