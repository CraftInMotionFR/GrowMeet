<?php // app/Views/templates/admin/coaches/create.php
$old = $old ?? [];
?>

<section class="auth-section">
    <div class="auth-container">
        <div class="auth-card auth-card-wide">
            <div class="auth-header">
                <h1>Nouveau coach</h1>
                <p>Créez un compte coach / instructeur</p>
            </div>
            <?php if (!empty($errors)): ?>
                <div class="form-errors">
                    <?php foreach ($errors as $error): ?>
                        <p class="form-error"><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form class="auth-form" method="POST" action="/admin/coaches" data-validate>
                <div class="form-row">
                    <div class="form-group">
                        <label for="coach-firstname">Prénom</label>
                        <div class="input-wrapper">
                            <i class="bx bx-user bx-remove-padding input-icon"></i>
                            <input type="text" id="coach-firstname" name="firstname"
                                   value="<?= htmlspecialchars($old['firstname'] ?? '') ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="coach-lastname">Nom</label>
                        <div class="input-wrapper">
                            <i class="bx bx-user bx-remove-padding input-icon"></i>
                            <input type="text" id="coach-lastname" name="lastname"
                                   value="<?= htmlspecialchars($old['lastname'] ?? '') ?>" required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="coach-email">Adresse e-mail</label>
                    <div class="input-wrapper">
                        <i class="bx bx-envelope bx-remove-padding input-icon"></i>
                        <input type="email" id="coach-email" name="email"
                               value="<?= htmlspecialchars($old['email'] ?? '') ?>" required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="coach-password">Mot de passe</label>
                    <div class="input-wrapper">
                        <i class="bx bx-lock-alt bx-remove-padding input-icon"></i>
                        <input type="password" id="coach-password" name="password" data-password-strength
                               placeholder="Au moins 8 caractères" required>
                    </div>
                </div>
                <div class="form-actions">
                    <a href="/admin/coaches"><button type="button" class="cto-green">Annuler</button></a>
                    <button type="submit" class="cto-orange">Créer</button>
                </div>
            </form>
        </div>
    </div>
</section>
