const { test, expect } = require('@playwright/test');
const {
    uniqueEmail,
    registerMember,
    login,
    logout,
    addDog,
    loginAsAdmin,
    createCoach,
    newMemberWithDog,
    DEFAULT_PASSWORD,
    uniqueSuffix,
} = require('./helpers');

test.describe('Inscription', () => {
    test('crée uniquement le compte et redirige vers /dogs/create', async ({ page }) => {
        await registerMember(page, { email: uniqueEmail('auth'), phone: '06 12 34 56 78' });

        await expect(page).toHaveURL(/\/dogs\/create$/);
    });

    test('le formulaire d\'inscription vérifie côté client les champs obligatoires', async ({ page }) => {
        await page.goto('/register');
        await page.click('form.auth-form button[type="submit"]');

        await expect(page).toHaveURL(/\/register$/);
        // Prénom, nom, email, mot de passe, confirmation
        await expect(page.locator('.field-error')).toHaveCount(5);
        await expect(page.locator('.field-error').first()).toHaveText('Champ obligatoire.');
    });

    test('la checklist de robustesse du mot de passe se met à jour en direct', async ({ page }) => {
        await page.goto('/register');
        const pwd = page.locator('#reg-password');

        await expect(page.locator('#reg-password-criteria-length')).toBeVisible();

        await pwd.pressSequentially('abc');
        await expect(page.locator('#reg-password-criteria-length')).toHaveClass(/error/);
        await expect(page.locator('#reg-password-criteria-uppercase')).toHaveClass(/error/);

        await pwd.fill('');
        await pwd.pressSequentially('Abcdefg1!');
        for (const id of ['length', 'special', 'uppercase', 'numeric']) {
            await expect(page.locator(`#reg-password-criteria-${id}`)).toHaveClass(/success/);
        }
    });

    test('un mot de passe trop faible est refusé', async ({ page }) => {
        await registerMember(page, { email: uniqueEmail('weak'), password: 'abc' });

        await expect(page).toHaveURL(/\/register$/);
        await expect(page.locator('.field-error')).toContainText('Mot de passe invalide');
    });

    test('des mots de passe différents sont refusés', async ({ page }) => {
        await page.goto('/register');
        await page.fill('#firstname', 'Jean');
        await page.fill('#lastname', 'Test');
        await page.fill('#reg-email', uniqueEmail('mismatch'));
        await page.fill('#reg-password', DEFAULT_PASSWORD);
        await page.fill('#reg-password-confirm', 'Autre1234!');
        await page.check('#terms');
        await page.click('form.auth-form button[type="submit"]');

        await expect(page).toHaveURL(/\/register$/);
        await expect(page.locator('.field-error')).toContainText('ne correspondent pas');
    });

    test('un email invalide est refusé', async ({ page }) => {
        await page.goto('/register');
        await page.fill('#firstname', 'Jean');
        await page.fill('#lastname', 'Test');
        await page.fill('#reg-email', 'pas-un-email');
        await page.fill('#reg-password', DEFAULT_PASSWORD);
        await page.fill('#reg-password-confirm', DEFAULT_PASSWORD);
        await page.click('form.auth-form button[type="submit"]');

        await expect(page.locator('.field-error')).toContainText('Adresse e-mail invalide');
    });

    test('il faut accepter les conditions d\'utilisation (contrôle serveur)', async ({ page }) => {
        await registerMember(page, { email: uniqueEmail('terms'), terms: false });

        await expect(page).toHaveURL(/\/register$/);
        await expect(page.locator('.form-errors')).toContainText('conditions d\'utilisation');
    });

    test('les champs saisis sont conservés après une erreur serveur', async ({ page }) => {
        const email = uniqueEmail('keep');
        await registerMember(page, { firstname: 'Camille', lastname: 'Durand', email, terms: false });

        await expect(page.locator('#firstname')).toHaveValue('Camille');
        await expect(page.locator('#lastname')).toHaveValue('Durand');
        await expect(page.locator('#reg-email')).toHaveValue(email);
    });

    test('un email déjà utilisé est refusé', async ({ page }) => {
        const email = uniqueEmail('dup');

        await registerMember(page, { email });
        await expect(page).toHaveURL(/\/dogs\/create$/);
        await logout(page);

        await registerMember(page, { email });

        await expect(page).toHaveURL(/\/register$/);
        await expect(page.locator('.form-errors')).toContainText('déjà utilisée');
    });

    test('le lien "Se connecter" du formulaire mène à /login', async ({ page }) => {
        await page.goto('/register');
        await page.click('.auth-footer a');
        await expect(page).toHaveURL(/\/login$/);
    });
});

test.describe('Connexion / déconnexion', () => {
    test('login puis logout fonctionnent', async ({ page }) => {
        const email = uniqueEmail('login');
        await registerMember(page, { email });
        await addDog(page);
        await logout(page);

        await login(page, email);
        await expect(page).toHaveURL(/\/dashboard$/);

        await logout(page);
        await expect(page).toHaveURL(/\/$/);
        await page.goto('/dashboard');
        await expect(page).toHaveURL(/\/login$/);
    });

    test('un membre sans chien est redirigé vers /dogs/create après connexion', async ({ page }) => {
        const email = uniqueEmail('login-nodog');
        await registerMember(page, { email });
        await logout(page);

        await login(page, email);

        await expect(page).toHaveURL(/\/dogs\/create$/);
    });

    test('un mauvais mot de passe affiche une erreur', async ({ page }) => {
        const email = uniqueEmail('badpwd');
        await registerMember(page, { email });
        await logout(page);

        await login(page, email, 'MauvaisMotDePasse1!');

        await expect(page).toHaveURL(/\/login$/);
        await expect(page.locator('.form-error')).toHaveText('Email ou mot de passe incorrect.');
        // L'email saisi est conservé
        await expect(page.locator('#email')).toHaveValue(email);
    });

    test('un email inconnu affiche la même erreur', async ({ page }) => {
        await login(page, uniqueEmail('ghost'), DEFAULT_PASSWORD);

        await expect(page.locator('.form-error')).toHaveText('Email ou mot de passe incorrect.');
    });

    test('le bouton œil affiche puis masque le mot de passe', async ({ page }) => {
        await page.goto('/login');
        const input = page.locator('#password');

        await expect(input).toHaveAttribute('type', 'password');
        await page.click('.input-toggle');
        await expect(input).toHaveAttribute('type', 'text');
        await page.click('.input-toggle');
        await expect(input).toHaveAttribute('type', 'password');
    });

    test('le lien "S\'inscrire" du formulaire mène à /register', async ({ page }) => {
        await page.goto('/login');
        await page.click('.auth-footer a');
        await expect(page).toHaveURL(/\/register$/);
    });

    test('un utilisateur connecté est renvoyé loin de /login et /register selon son rôle', async ({ page }) => {
        // Membre -> /dashboard
        await newMemberWithDog(page, { prefix: 'guestmw' });
        await page.goto('/login');
        await expect(page).toHaveURL(/\/dashboard$/);
        await page.goto('/register');
        await expect(page).toHaveURL(/\/dashboard$/);
        await logout(page);

        // Admin -> /admin/course-types
        await loginAsAdmin(page);
        await page.goto('/login');
        await expect(page).toHaveURL(/\/admin\/course-types$/);

        // Coach -> /admin/sessions
        const coachEmail = uniqueEmail('guestcoach');
        await createCoach(page, { lastname: `G${uniqueSuffix()}`, email: coachEmail });
        await logout(page);
        await login(page, coachEmail);
        await expect(page).toHaveURL(/\/admin\/sessions$/);
        await page.goto('/register');
        await expect(page).toHaveURL(/\/admin\/sessions$/);
    });
});

test.describe('Routes protégées', () => {
    for (const path of ['/dashboard', '/profile', '/dogs', '/dogs/create', '/admin/course-types', '/admin/coaches', '/admin/sessions']) {
        test(`un visiteur est redirigé vers /login depuis ${path}`, async ({ page }) => {
            await page.goto(path);
            await expect(page).toHaveURL(/\/login$/);
        });
    }

    test('les actions POST protégées redirigent aussi un visiteur vers /login', async ({ page }) => {
        const response = await page.request.post('/dogs', { form: { dog_name: 'Hack' } });

        expect(response.url()).toMatch(/\/login$/);
    });
});
