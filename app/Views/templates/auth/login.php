<?php // app/Views/templates/auth/login.php ?>

<section class="auth-section">
    <div class="auth-container auth-container-single">
        <div class="auth-card">
            <div class="auth-header">
                <h1>Connexion</h1>
                <p>Connectez-vous à votre compte GrowMeet</p>
            </div>
            <?php if (!empty($errors)): ?>
                <div class="form-errors">
                    <?php foreach ($errors as $error): ?>
                        <p class="form-error"><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <form class="auth-form" method="POST" action="/login">
                <div class="form-group">
                    <label for="email">Email</label>
                    <div class="input-wrapper">
                        <i class="bx bx-envelope bx-remove-padding input-icon"></i>
                        <input type="email" id="email" name="email"
                               placeholder="votre.email@exemple.com"
                               value="<?= htmlspecialchars($old_email ?? '') ?>"
                               required>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Mot de passe</label>
                    <div class="input-wrapper">
                        <i class="bx bx-lock bx-remove-padding input-icon"></i>
                        <input type="password" id="password" name="password"
                               placeholder="••••••••" required>
                        <button type="button" class="input-toggle" onclick="togglePassword('password', this)">
                            <i class="bx bx-eye bx-remove-padding"></i>
                        </button>
                    </div>
                </div>
                <div class="form-check form-check-between">
                    <label><input type="checkbox" id="remember" name="remember"> Se souvenir de moi</label>
                    <a href="#" class="forgot-link">Mot de passe oublié ?</a>
                </div>
                <button type="submit" class="cto-orange btn-full">Se connecter</button>
            </form>
            <div class="auth-footer">
                <p>Pas encore de compte ? <a href="/register">S'inscrire</a></p>
            </div>
        </div>
    </div>
</section>
