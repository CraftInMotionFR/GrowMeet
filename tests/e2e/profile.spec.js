const { test, expect } = require('@playwright/test');
const { uniqueEmail, registerMember, newMemberWithDog, loginAsAdmin, createCoach, login, logout, uniqueSuffix } = require('./helpers');

test.describe('Dashboard membre', () => {
    test('affiche le prénom, les statistiques, les chiens et un état vide pour les séances', async ({ page }) => {
        await registerMember(page, { firstname: 'Camille', email: uniqueEmail('dash') });
        await page.goto('/dogs/create');
        await page.fill('#dog-name', 'Milo');
        await page.selectOption('#dog-gender', 'male');
        await page.fill('#dog-birth-date', '2023-03-01');
        await page.click('form.auth-form button[type="submit"]');

        await page.goto('/dashboard');

        await expect(page.locator('.page-title')).toContainText('Bonjour, Camille');
        await expect(page.locator('.stat-card')).toHaveCount(4);
        await expect(page.locator('.stat-card', { hasText: 'Cours suivis' }).locator('.stat-card-value')).toHaveText('0');
        await expect(page.locator('.dogs-list-item', { hasText: 'Milo' })).toBeVisible();
        await expect(page.locator('.upcoming-list .empty-state')).toContainText('Aucun cours à venir');
    });

    test('un chien du dashboard mène à son formulaire de modification', async ({ page }) => {
        await newMemberWithDog(page, { dogName: 'Cliquable', prefix: 'dashlink' });
        await page.goto('/dashboard');

        await page.locator('.dogs-list-item', { hasText: 'Cliquable' }).click();

        await expect(page).toHaveURL(/\/dogs\/\d+\/edit$/);
    });

    test('la navigation membre expose Accueil, Cours, Chiens (Calendrier désactivé)', async ({ page }) => {
        await newMemberWithDog(page, { prefix: 'nav' });
        await page.goto('/dashboard');

        const nav = page.locator('.dashboard-nav');
        await expect(nav.locator('a[href="/dashboard"]')).toBeVisible();
        await expect(nav.locator('a[href="/courses"]')).toBeVisible();
        await expect(nav.locator('a[href="/dogs"]')).toBeVisible();
        await expect(nav.locator('.nav-link-disabled')).toContainText('Calendrier');
        // Pas d'entrée d'administration pour un membre
        await expect(nav.locator('a[href^="/admin"]')).toHaveCount(0);

        await nav.locator('a[href="/courses"]').click();
        await expect(page).toHaveURL(/\/courses$/);
    });
});

test.describe('Profil', () => {
    test('un membre voit son identité, son email et son rôle', async ({ page }) => {
        const email = uniqueEmail('profile');
        await registerMember(page, { firstname: 'Alex', lastname: 'Martin', email });
        await page.goto('/dogs/create');
        await page.fill('#dog-name', 'Rex');
        await page.selectOption('#dog-gender', 'male');
        await page.fill('#dog-birth-date', '2023-03-01');
        await page.click('form.auth-form button[type="submit"]');

        await page.goto('/profile');

        const summary = page.locator('.summary-card');
        await expect(summary).toContainText('Alex Martin');
        await expect(summary).toContainText(email);
        await expect(summary).toContainText('Membre');
    });

    test('le bouton Déconnexion ferme la session', async ({ page }) => {
        await newMemberWithDog(page, { prefix: 'profilelogout' });
        await page.goto('/profile');

        await page.getByRole('button', { name: 'Déconnexion' }).click();

        await expect(page).toHaveURL(/\/$/);
        await page.goto('/profile');
        await expect(page).toHaveURL(/\/login$/);
    });

    test('le profil de l\'administrateur affiche le rôle Administrateur', async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto('/profile');

        await expect(page.locator('.summary-card')).toContainText('Administrateur');
        await expect(page.locator('.summary-card')).toContainText('admin@growmeet.com');
    });

    test('le profil d\'un coach affiche le rôle Coach', async ({ page }) => {
        const email = uniqueEmail('profilecoach');
        await loginAsAdmin(page);
        await createCoach(page, { lastname: `P${uniqueSuffix()}`, email });
        await logout(page);

        await login(page, email);
        await page.goto('/profile');

        await expect(page.locator('.summary-card')).toContainText('Coach');
    });
});
