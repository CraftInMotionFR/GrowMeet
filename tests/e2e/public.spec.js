const { test, expect } = require('@playwright/test');
const { setupSession, courseCard } = require('./helpers');

test.describe('Pages publiques', () => {
    test('la page d\'accueil présente le club et ses appels à l\'action', async ({ page }) => {
        await page.goto('/');

        await expect(page).toHaveTitle(/GrowMeet/);
        await expect(page.locator('h1')).toContainText('Éduquez votre chien');
        await expect(page.locator('#hero-banner a[href="/register"]')).toBeVisible();
        await expect(page.locator('#hero-banner a[href="/courses"]')).toBeVisible();
        // Les 7 types de cours de démonstration sont listés
        await expect(page.locator('.s-card')).toHaveCount(7);
        await expect(page.locator('.gallery img')).toHaveCount(4);
    });

    test('le header public propose Se connecter / S\'inscrire', async ({ page }) => {
        await page.goto('/');

        await expect(page.locator('header a[href="/login"]')).toBeVisible();
        await expect(page.locator('header a[href="/register"]')).toBeVisible();
    });

    test('les boutons de l\'accueil mènent à l\'inscription et au catalogue', async ({ page }) => {
        await page.goto('/');
        await page.click('#hero-banner a[href="/register"]');
        await expect(page).toHaveURL(/\/register$/);

        await page.goto('/');
        await page.click('#hero-banner a[href="/courses"]');
        await expect(page).toHaveURL(/\/courses$/);
    });

    test('une URL inconnue affiche la page 404', async ({ page }) => {
        const response = await page.goto('/cette-page-nexiste-pas');

        expect(response.status()).toBe(404);
        await expect(page.locator('.error-404-title')).toHaveText('Page non trouvée');
        await page.click('.error-404 a');
        await expect(page).toHaveURL(/\/$/);
    });

    test('un visiteur voit le catalogue et une fiche cours sans pouvoir s\'inscrire', async ({ page }) => {
        const { typeName, sessionId } = await setupSession(page, { typePrefix: 'Public' });

        await page.goto('/courses');
        await expect(courseCard(page, typeName)).toBeVisible();

        await page.goto(`/courses/${sessionId}`);
        await expect(page.locator('h1.page-title')).toHaveText(typeName);
        await expect(page.locator('.course-detail-infogrid')).toBeVisible();
        await expect(page.locator('.course-detail-coach')).toBeVisible();
        // Pas de formulaire d'inscription pour un visiteur, le bouton "Contacter le coach" est désactivé (bientôt disponible)
        await expect(page.locator('form[action$="/join"]')).toHaveCount(0);
        await expect(page.getByRole('button', { name: 'Contacter le coach' })).toBeDisabled();
    });

    test('une fiche cours inexistante affiche "Cours non trouvé"', async ({ page }) => {
        const response = await page.goto('/courses/99999999');

        expect(response.status()).toBe(404);
        await expect(page.locator('.error-404-title')).toHaveText('Cours non trouvé');
        await page.click('.error-404 a');
        await expect(page).toHaveURL(/\/courses$/);
    });
});
