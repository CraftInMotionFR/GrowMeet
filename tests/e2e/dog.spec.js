const { test, expect } = require('@playwright/test');
const {
    uniqueEmail,
    registerMember,
    addDog,
    newMemberWithDog,
    monthsAgo,
    logout,
    TINY_PNG,
} = require('./helpers');

// Les champs de la fiche chien ont l'attribut "required" : pour tester la validation serveur,
// on désactive la validation native du navigateur avant de soumettre.
async function submitWithoutBrowserValidation(page) {
    await page.locator('form.auth-form').evaluate((form) => { form.noValidate = true; });
    await page.click('form.auth-form button[type="submit"]');
}

test.describe('Ajout d\'un chien', () => {
    test('ajouter un chien (race, sexe, date de naissance) débloque le dashboard et les cours', async ({ page }) => {
        await registerMember(page, { email: uniqueEmail('dog') });
        await expect(page).toHaveURL(/\/dogs\/create$/);

        await addDog(page, { name: 'Rex', breed: 'Golden Retriever', gender: 'male', birthDate: monthsAgo(18) });

        await expect(page).toHaveURL(/\/dogs\?status=dog-added/);
        await expect(page.locator('.flash-message-success')).toContainText('Votre chien a été ajouté');
        await expect(page.locator('.dog-profile-card', { hasText: 'Rex' })).toBeVisible();

        await page.goto('/dashboard');
        await expect(page).toHaveURL(/\/dashboard$/);
        await page.goto('/courses');
        await expect(page).toHaveURL(/\/courses$/);
    });

    test('un membre sans chien est redirigé depuis /dashboard et /courses', async ({ page }) => {
        await registerMember(page, { email: uniqueEmail('nodog') });
        await expect(page).toHaveURL(/\/dogs\/create$/);

        await page.goto('/dashboard');
        await expect(page).toHaveURL(/\/dogs\/create$/);

        await page.goto('/courses');
        await expect(page).toHaveURL(/\/dogs\/create$/);
    });

    test('la liste de suggestions de race propose les races connues', async ({ page }) => {
        await registerMember(page, { email: uniqueEmail('breeds') });

        await expect(page.locator('#breed-suggestions option[value="Labrador Retriever"]')).toHaveCount(1);
        await expect(page.locator('#breed-suggestions option[value="Border Collie"]')).toHaveCount(1);
    });

    test('la date de naissance ne peut pas être dans le futur (contrôle serveur)', async ({ page }) => {
        await registerMember(page, { email: uniqueEmail('futuredog') });

        await page.goto('/dogs/create');
        await page.fill('#dog-name', 'FutureDog');
        await page.selectOption('#dog-gender', 'male');
        await page.evaluate(() => document.querySelector('#dog-birth-date').removeAttribute('max'));
        await page.fill('#dog-birth-date', '2999-01-01');
        await submitWithoutBrowserValidation(page);

        await expect(page.locator('.form-errors')).toContainText('ne peut pas être dans le futur');
    });

    test('nom, sexe et date de naissance sont obligatoires (contrôle serveur)', async ({ page }) => {
        await registerMember(page, { email: uniqueEmail('emptydog') });

        await page.goto('/dogs/create');
        await submitWithoutBrowserValidation(page);

        const errors = page.locator('.form-errors');
        await expect(errors).toContainText('Le nom du chien est obligatoire');
        await expect(errors).toContainText('Veuillez indiquer le sexe du chien');
        await expect(errors).toContainText('La date de naissance est obligatoire');
    });

    test('les valeurs saisies sont conservées après une erreur', async ({ page }) => {
        await registerMember(page, { email: uniqueEmail('keepdog') });

        await page.goto('/dogs/create');
        await page.fill('#dog-breed', 'Beagle');
        await page.selectOption('#dog-gender', 'female');
        await submitWithoutBrowserValidation(page);

        await expect(page.locator('#dog-breed')).toHaveValue('Beagle');
        await expect(page.locator('#dog-gender')).toHaveValue('female');
    });

    test('le bouton Annuler ramène à la liste des chiens', async ({ page }) => {
        await newMemberWithDog(page, { prefix: 'cancel' });
        await page.goto('/dogs/create');

        await page.click('.form-actions a[href="/dogs"]');
        await expect(page).toHaveURL(/\/dogs$/);
    });
});

test.describe('Gestion des chiens', () => {
    test('la liste affiche nom, race, âge, sexe et nombre de cours suivis', async ({ page }) => {
        await newMemberWithDog(page, { dogName: 'Luna', birthDate: monthsAgo(30), prefix: 'list' });

        await page.goto('/dogs');
        const card = page.locator('.dog-profile-card', { hasText: 'Luna' });

        await expect(card).toContainText('Golden Retriever');
        await expect(card).toContainText('Mâle');
        await expect(card.locator('.dog-profile-card-stat strong')).toHaveText('0');
    });

    test('un membre peut ajouter plusieurs chiens', async ({ page }) => {
        await newMemberWithDog(page, { dogName: 'Premier', prefix: 'multi' });
        await addDog(page, { name: 'Second', gender: 'female' });

        await expect(page.locator('.dog-profile-card')).toHaveCount(2);
    });

    test('modifier un chien met à jour ses informations', async ({ page }) => {
        await newMemberWithDog(page, { dogName: 'Avant', prefix: 'edit' });

        await page.locator('.dog-profile-card', { hasText: 'Avant' }).locator('.dog-profile-card-btn', { hasText: 'Modifier' }).click();
        await expect(page).toHaveURL(/\/dogs\/\d+\/edit$/);
        await expect(page.locator('h1')).toHaveText('Modifier Avant');
        // Le formulaire est pré-rempli
        await expect(page.locator('#dog-name')).toHaveValue('Avant');
        await expect(page.locator('#dog-breed')).toHaveValue('Golden Retriever');
        await expect(page.locator('#dog-gender')).toHaveValue('male');

        await page.fill('#dog-name', 'Après');
        await page.selectOption('#dog-gender', 'female');
        await page.click('form.auth-form button[type="submit"]');

        await expect(page).toHaveURL(/\/dogs\?status=dog-updated/);
        await expect(page.locator('.flash-message-success')).toContainText('mises à jour');
        const card = page.locator('.dog-profile-card', { hasText: 'Après' });
        await expect(card).toBeVisible();
        await expect(card).toContainText('Femelle');
        await expect(page.locator('.dog-profile-card', { hasText: 'Avant' })).toHaveCount(0);
    });

    test('la modification est validée côté serveur', async ({ page }) => {
        await newMemberWithDog(page, { dogName: 'Valide', prefix: 'editinvalid' });

        await page.locator('.dog-profile-card').locator('.dog-profile-card-btn', { hasText: 'Modifier' }).click();
        await page.fill('#dog-name', '');
        await submitWithoutBrowserValidation(page);

        await expect(page.locator('.form-errors')).toContainText('Le nom du chien est obligatoire');
    });

    test('supprimer un chien (après confirmation) le retire de la liste', async ({ page }) => {
        await newMemberWithDog(page, { dogName: 'Partant', prefix: 'delete' });

        await page.locator('.dog-profile-card').locator('.dog-profile-card-btn', { hasText: 'Modifier' }).click();
        page.once('dialog', (dialog) => dialog.accept());
        await page.getByRole('button', { name: 'Supprimer ce chien' }).click();

        await expect(page).toHaveURL(/\/dogs\?status=dog-deleted/);
        await expect(page.locator('.flash-message-success')).toContainText('Le chien a été supprimé');
        await expect(page.locator('.empty-state')).toContainText('Aucun chien enregistré');
    });

    test('refuser la confirmation conserve le chien', async ({ page }) => {
        await newMemberWithDog(page, { dogName: 'Reste', prefix: 'keep' });

        await page.locator('.dog-profile-card').locator('.dog-profile-card-btn', { hasText: 'Modifier' }).click();
        page.once('dialog', (dialog) => dialog.dismiss());
        await page.getByRole('button', { name: 'Supprimer ce chien' }).click();

        await expect(page).toHaveURL(/\/dogs\/\d+\/edit$/);
        await page.goto('/dogs');
        await expect(page.locator('.dog-profile-card', { hasText: 'Reste' })).toBeVisible();
    });

    test('une photo JPG/PNG peut être envoyée puis est supprimée avec le chien', async ({ page }) => {
        await registerMember(page, { email: uniqueEmail('photo') });
        await page.goto('/dogs/create');
        await page.fill('#dog-name', 'Photogénique');
        await page.selectOption('#dog-gender', 'male');
        await page.fill('#dog-birth-date', monthsAgo(12));
        await page.setInputFiles('#dog-photo', { name: 'chien.png', mimeType: 'image/png', buffer: TINY_PNG });
        await page.click('form.auth-form button[type="submit"]');

        await expect(page).toHaveURL(/status=dog-added/);
        const photo = page.locator('.dog-profile-card', { hasText: 'Photogénique' }).locator('img');
        await expect(photo).toHaveAttribute('src', /^\/images\/dogs\/dog_[a-f0-9]+\.png$/);

        // Nettoyage : la suppression du chien supprime aussi le fichier
        await page.locator('.dog-profile-card').locator('.dog-profile-card-btn', { hasText: 'Modifier' }).click();
        page.once('dialog', (dialog) => dialog.accept());
        await page.getByRole('button', { name: 'Supprimer ce chien' }).click();
        await expect(page).toHaveURL(/status=dog-deleted/);
    });

    test('un membre ne peut pas modifier ni supprimer le chien d\'un autre membre', async ({ page }) => {
        // Le chien du membre A
        await newMemberWithDog(page, { dogName: 'ChienDeA', prefix: 'ownerA' });
        const href = await page.locator('.dog-profile-card').locator('.dog-profile-card-btn', { hasText: 'Modifier' }).getAttribute('href');
        await logout(page);

        // Le membre B tente d'y accéder
        await newMemberWithDog(page, { dogName: 'ChienDeB', prefix: 'ownerB' });
        const response = await page.goto(href);
        expect(response.status()).toBe(404);
        await expect(page.locator('.error-404-title')).toHaveText('Page non trouvée');

        const deleteResponse = await page.request.post(href.replace('/edit', '/delete'));
        expect(deleteResponse.status()).toBe(404);
    });
});
