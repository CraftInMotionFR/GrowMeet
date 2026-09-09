<?php
$old = $old ?? [];
?>

<section class="auth-section">
    <div class="auth-container">
        <div class="auth-card auth-card-wide">
            <div class="auth-header">
                <h1>Nouvelle séance</h1>
                <p>Planifiez une séance de cours avec un coach unique</p>
            </div>
            <?php if (!empty($errors)): ?>
                <div class="form-errors">
                    <?php foreach ($errors as $error): ?>
                        <p class="form-error"><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form class="auth-form" method="POST" action="/admin/sessions">
                <div class="form-row">
                    <div class="form-group">
                        <label for="session-type">Type de cours</label>
                        <div class="input-wrapper input-wrapper-select">
                            <i class="bx bx-book-open bx-remove-padding input-icon"></i>
                            <select id="session-type" name="id_course_type" required>
                                <option value="" disabled <?= empty($old['id_course_type']) ? 'selected' : '' ?>>Sélectionnez un type</option>
                                <?php foreach ($courseTypes as $ct): ?>
                                    <option value="<?= $ct['id_course_type'] ?>" <?= (string) ($old['id_course_type'] ?? '') === (string) $ct['id_course_type'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($ct['name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Coach (un seul)</label>
                        <?php if ($isCoach): ?>
                            <div class="input-wrapper">
                                <i class="bx bx-user bx-remove-padding input-icon"></i>
                                <input type="text" value="<?= htmlspecialchars($_SESSION['user']['firstname'] . ' ' . $_SESSION['user']['lastname']) ?>" disabled>
                            </div>
                        <?php else: ?>
                            <div class="input-wrapper input-wrapper-select">
                                <i class="bx bx-user bx-remove-padding input-icon"></i>
                                <select id="session-coach" name="id_user" required>
                                    <option value="" disabled <?= empty($old['id_user']) ? 'selected' : '' ?>>Sélectionnez un coach</option>
                                    <?php foreach ($coaches as $coach): ?>
                                        <option value="<?= $coach['id_user'] ?>" <?= (string) ($old['id_user'] ?? '') === (string) $coach['id_user'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($coach['first_name'] . ' ' . $coach['last_name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="session-date">Date</label>
                        <div class="input-wrapper">
                            <i class="bx bx-calendar-alt bx-remove-padding input-icon"></i>
                            <input type="date" id="session-date" name="date"
                                   value="<?= htmlspecialchars($old['date'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="session-status">Statut</label>
                        <div class="input-wrapper input-wrapper-select">
                            <i class="bx bx-flag bx-remove-padding input-icon"></i>
                            <select id="session-status" name="status">
                                <?php foreach (['open' => 'Ouvert', 'full' => 'Complet', 'cancelled' => 'Annulé', 'completed' => 'Terminé'] as $val => $label): ?>
                                    <option value="<?= $val ?>" <?= ($old['status'] ?? 'open') === $val ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="session-start">Heure de début</label>
                        <div class="input-wrapper">
                            <i class="bx bx-time bx-remove-padding input-icon"></i>
                            <input type="time" id="session-start" name="start_time"
                                   value="<?= htmlspecialchars($old['start_time'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="session-end">Heure de fin</label>
                        <div class="input-wrapper">
                            <i class="bx bx-time bx-remove-padding input-icon"></i>
                            <input type="time" id="session-end" name="end_time"
                                   value="<?= htmlspecialchars($old['end_time'] ?? '') ?>" required>
                        </div>
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="session-min">Places minimum</label>
                        <div class="input-wrapper">
                            <i class="bx bx-group bx-remove-padding input-icon"></i>
                            <input type="number" id="session-min" name="min_participants" min="1"
                                   value="<?= htmlspecialchars($old['min_participants'] ?? '1') ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="session-max">Places maximum</label>
                        <div class="input-wrapper">
                            <i class="bx bx-group bx-remove-padding input-icon"></i>
                            <input type="number" id="session-max" name="max_participants" min="1"
                                   value="<?= htmlspecialchars($old['max_participants'] ?? '10') ?>" required>
                        </div>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="/admin/sessions"><button type="button" class="cto-green">Annuler</button></a>
                    <button type="submit" class="cto-orange">Créer</button>
                </div>
            </form>
        </div>
    </div>
</section>
