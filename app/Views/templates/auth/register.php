<?php // app/Views/templates/auth/register.php
$old = $old ?? [];
?>

<section class="auth-section">
    <div class="auth-container auth-container-single">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Créer un compte</h1>
                <p>Rejoignez GrowMeet et commencez l'aventure</p>
            </div>
            <?php if (!empty($errors)): ?>
                <div class="form-errors">
                    <?php foreach ($errors as $error): ?>
                        <p class="form-error"><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form class="auth-form" method="POST" action="/register" data-validate>
                <div class="form-row">
                    <div class="form-group">
                        <label for="firstname">Prénom</label>
                        <div class="input-wrapper">
                            <i class="bx bx-user bx-remove-padding input-icon"></i>
                            <input type="text" id="firstname" name="firstname"
                                   placeholder="Jean"
                                   value="<?= htmlspecialchars($old['firstname'] ?? '') ?>"
                                   required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="lastname">Nom</label>
                        <div class="input-wrapper">
                            <i class="bx bx-user bx-remove-padding input-icon"></i>
                            <input type="text" id="lastname" name="lastname"
                                   placeholder="Dupont"
                                   value="<?= htmlspecialchars($old['lastname'] ?? '') ?>"
                                   required>
                        </div>
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg-email">Email</label>
                    <div class="input-wrapper">
                        <i class="bx bx-envelope bx-remove-padding input-icon"></i>
                        <input type="email" id="reg-email" name="email"
                               placeholder="votre.email@exemple.com"
                               value="<?= htmlspecialchars($old['email'] ?? '') ?>"
                               required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg-phone">Téléphone</label>
                    <div class="input-wrapper">
                        <i class="bx bx-phone bx-remove-padding input-icon"></i>
                        <input type="tel" id="reg-phone" name="phone"
                               placeholder="06 12 34 56 78"
                               value="<?= htmlspecialchars($old['phone'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg-password">Mot de passe</label>
                    <div class="input-wrapper">
                        <i class="bx bx-lock bx-remove-padding input-icon"></i>
                        <input type="password" id="reg-password" name="password" data-password-strength
                               placeholder="••••••••" required>
                        <button type="button" class="input-toggle" onclick="togglePassword('reg-password', this)">
                            <i class="bx bx-eye bx-remove-padding"></i>
                        </button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg-password-confirm">Confirmer le mot de passe</label>
                    <div class="input-wrapper">
                        <i class="bx bx-lock bx-remove-padding input-icon"></i>
                        <input type="password" id="reg-password-confirm" name="password_confirm" data-confirm-of="reg-password"
                               placeholder="••••••••" required>
                        <button type="button" class="input-toggle" onclick="togglePassword('reg-password-confirm', this)">
                            <i class="bx bx-eye bx-remove-padding"></i>
                        </button>
                    </div>
                </div>
                <div class="form-check">
                    <label><input type="checkbox" id="terms" name="terms" required> J'accepte les conditions d'utilisation et la politique de confidentialité</label>
                </div>
                <button type="submit" class="cto-orange btn-full">Créer mon compte</button>
            </form>
            <div class="auth-footer">
                <p>Déjà un compte ? <a href="/login">Se connecter</a></p>
            </div>
        </div>
    </div>
</section>
