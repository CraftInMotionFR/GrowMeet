const { test, expect } = require('@playwright/test');
const {
    uniqueEmail,
    uniqueSuffix,
    inDays,
    loginAsAdmin,
    logout,
    createCourseType,
    createCoach,
    createSession,
    fillSessionForm,
    sessionRow,
    sessionIdFromList,
    setupSession,
    newMemberWithDog,
    joinFromCatalogue,
    login,
} = require('./helpers');

// Désactive la validation native du navigateur pour atteindre les contrôles serveur
async function submitWithoutBrowserValidation(page) {
    await page.locator('form.auth-form').evaluate((form) => { form.noValidate = true; });
    await page.click('form.auth-form button[type="submit"]');
}

test.describe('Contrôle d\'accès administrateur', () => {
    test('un membre est renvoyé vers son dashboard depuis les pages d\'administration', async ({ page }) => {
        await newMemberWithDog(page, { prefix: 'notadmin' });

        await page.goto('/admin/course-types');
        await expect(page).toHaveURL(/\/dashboard\?status=admins-only$/);
        await expect(page.locator('.flash-message-error')).toContainText('réservée aux administrateurs');

        await page.goto('/admin/coaches');
        await expect(page).toHaveURL(/\/dashboard\?status=admins-only$/);

        await page.goto('/admin/sessions');
        await expect(page).toHaveURL(/\/dashboard\?status=staff-only$/);
        await expect(page.locator('.flash-message-error')).toContainText('administrateurs et aux coachs');
    });

    test('un membre ne peut pas créer de type de cours ni de coach par requête directe', async ({ page }) => {
        await newMemberWithDog(page, { prefix: 'forge' });
        const name = `Pirate ${uniqueSuffix()}`;

        const typeResponse = await page.request.post('/admin/course-types', { form: { name } });
        expect(typeResponse.url()).toContain('status=admins-only');

        const coachResponse = await page.request.post('/admin/coaches', {
            form: { firstname: 'Pi', lastname: 'Rate', email: uniqueEmail('pirate'), password: 'Password123!' },
        });
        expect(coachResponse.url()).toContain('status=admins-only');
        await logout(page);

        await loginAsAdmin(page);
        await expect(page.locator('.reservation-item', { hasText: name })).toHaveCount(0);
    });

    test('la navigation admin expose Types de cours, Coachs et Séances (pas de menu membre)', async ({ page }) => {
        await loginAsAdmin(page);

        const nav = page.locator('.dashboard-nav');
        await expect(nav.locator('a[href="/admin/course-types"]')).toBeVisible();
        await expect(nav.locator('a[href="/admin/coaches"]')).toBeVisible();
        await expect(nav.locator('a[href="/admin/sessions"]')).toBeVisible();
        await expect(nav.locator('a[href="/dogs"]')).toHaveCount(0);
        await expect(nav.locator('a[href="/courses"]')).toHaveCount(0);

        await nav.locator('a[href="/admin/sessions"]').click();
        await expect(page).toHaveURL(/\/admin\/sessions$/);
    });

    test('l\'admin n\'a pas accès aux fonctions réservées aux membres', async ({ page }) => {
        // setupSession se connecte lui-même en admin puis déconnecte
        const { sessionId } = await setupSession(page, { typePrefix: 'AdminJoin' });
        await loginAsAdmin(page);

        // /dashboard et /dogs le ramènent à son propre espace
        await page.goto('/dashboard');
        await expect(page).toHaveURL(/\/admin\/course-types$/);
        await page.goto('/dogs');
        await expect(page).toHaveURL(/\/admin\/course-types$/);

        // Il ne peut pas s'inscrire à un cours (pas de chien)
        const response = await page.request.post(`/courses/${sessionId}/join`);
        expect(response.url()).toContain('/courses?status=no-dog');
    });
});

test.describe('Types de cours (admin)', () => {
    test('CRUD complet d\'un type de cours', async ({ page }) => {
        await loginAsAdmin(page);
        const name = 'Type Test ' + uniqueSuffix();

        await createCourseType(page, { name, description: 'Une description', ageMinMonths: 2, ageMaxMonths: 12 });
        await expect(page).toHaveURL(/\/admin\/course-types\?status=course-type-created/);
        await expect(page.locator('.flash-message-success')).toContainText('Type de cours créé');
        await expect(page.locator('.reservation-item', { hasText: name })).toBeVisible();

        // Modification : le formulaire est pré-rempli
        await page.locator('.reservation-item', { hasText: name }).getByText('Modifier').click();
        await expect(page.locator('h1')).toHaveText('Modifier le type de cours');
        await expect(page.locator('#ct-name')).toHaveValue(name);
        await expect(page.locator('#ct-description')).toHaveValue('Une description');
        const newName = name + ' modifié';
        await page.fill('#ct-name', newName);
        await page.click('form.auth-form button[type="submit"]');
        await expect(page).toHaveURL(/status=course-type-updated/);
        await expect(page.locator('.flash-message-success')).toContainText('modifié avec succès');
        await expect(page.locator('.reservation-item', { hasText: newName })).toBeVisible();

        // Suppression
        page.once('dialog', (dialog) => dialog.accept());
        await page.locator('.reservation-item', { hasText: newName }).getByText('Supprimer').click();
        await expect(page).toHaveURL(/status=course-type-deleted/);
        await expect(page.locator('.reservation-item', { hasText: newName })).toHaveCount(0);
    });

    test('le nom est obligatoire et l\'âge minimum doit être inférieur à l\'âge maximum', async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto('/admin/course-types/create');
        await submitWithoutBrowserValidation(page);
        await expect(page.locator('.form-errors')).toContainText('Le nom du type de cours est obligatoire');

        await page.fill('#ct-name', 'Age incohérent');
        await page.fill('#ct-age-min', '12');
        await page.fill('#ct-age-max', '6');
        await page.click('form.auth-form button[type="submit"]');
        await expect(page.locator('.form-errors')).toContainText("L'âge minimum doit être inférieur à l'âge maximum");
        // Les valeurs saisies sont conservées
        await expect(page.locator('#ct-name')).toHaveValue('Age incohérent');
    });

    test('un type rattaché à une séance ne peut pas être supprimé', async ({ page }) => {
        const { typeName } = await setupSession(page, { typePrefix: 'TypeUtilise' });
        await loginAsAdmin(page);

        page.once('dialog', (dialog) => dialog.accept());
        await page.locator('.reservation-item', { hasText: typeName }).getByText('Supprimer').click();

        await expect(page).toHaveURL(/status=type-in-use/);
        await expect(page.locator('.flash-message-error')).toContainText('Impossible de supprimer ce type de cours');
        await expect(page.locator('.reservation-item', { hasText: typeName })).toBeVisible();
    });

    test('modifier un type inexistant renvoie une 404', async ({ page }) => {
        await loginAsAdmin(page);

        const response = await page.goto('/admin/course-types/99999999/edit');

        expect(response.status()).toBe(404);
    });
});

test.describe('Coachs (admin)', () => {
    test('création puis suppression d\'un coach', async ({ page }) => {
        await loginAsAdmin(page);
        const email = uniqueEmail('coach');

        await createCoach(page, { firstname: 'Coach', lastname: 'Playwright', email });
        await expect(page).toHaveURL(/status=coach-created/);
        await expect(page.locator('.flash-message-success')).toContainText('Coach créé');
        await expect(page.locator('.reservation-item', { hasText: email })).toContainText('Coach Playwright');

        page.once('dialog', (dialog) => dialog.accept());
        await page.locator('.reservation-item', { hasText: email }).getByText('Supprimer').click();
        await expect(page).toHaveURL(/status=coach-deleted/);
        await expect(page.locator('.reservation-item', { hasText: email })).toHaveCount(0);
    });

    test('le nouveau coach peut se connecter et arrive sur ses séances', async ({ page }) => {
        await loginAsAdmin(page);
        const email = uniqueEmail('newcoach');
        await createCoach(page, { lastname: `L${uniqueSuffix()}`, email });
        await logout(page);

        await login(page, email);

        await expect(page).toHaveURL(/\/admin\/sessions$/);
    });

    test('un email déjà utilisé est refusé', async ({ page }) => {
        await loginAsAdmin(page);
        const email = uniqueEmail('coachdup');
        await createCoach(page, { lastname: 'Doublon', email });
        await expect(page).toHaveURL(/status=coach-created/);

        await createCoach(page, { lastname: 'Doublon', email });

        await expect(page.locator('.form-errors')).toContainText('déjà utilisée');
        await expect(page.locator('#coach-firstname')).toHaveValue('Coach');
    });

    test('le mot de passe du coach doit respecter les critères de robustesse', async ({ page }) => {
        await loginAsAdmin(page);

        await createCoach(page, { lastname: 'Faible', email: uniqueEmail('coachweak'), password: 'faible' });

        await expect(page).toHaveURL(/\/admin\/coaches\/create$/);
        await expect(page.locator('.field-error')).toContainText('Mot de passe invalide');
    });

    test('les champs obligatoires sont contrôlés', async ({ page }) => {
        await loginAsAdmin(page);
        await page.goto('/admin/coaches/create');

        await page.click('form.auth-form button[type="submit"]');

        await expect(page.locator('.field-error')).toHaveCount(4);
    });

    test('un coach assigné à une séance ne peut pas être supprimé', async ({ page }) => {
        const { coachEmail } = await setupSession(page, { typePrefix: 'CoachUtilise' });
        await loginAsAdmin(page);
        await page.goto('/admin/coaches');

        page.once('dialog', (dialog) => dialog.accept());
        await page.locator('.reservation-item', { hasText: coachEmail }).getByText('Supprimer').click();

        await expect(page).toHaveURL(/status=coach-in-use/);
        await expect(page.locator('.flash-message-error')).toContainText('Impossible de supprimer ce coach');
        await expect(page.locator('.reservation-item', { hasText: coachEmail })).toBeVisible();
    });
});

test.describe('Séances (admin)', () => {
    test('créer une séance et la retrouver dans la liste admin puis dans le catalogue public', async ({ page }) => {
        const { typeName, coachName } = await setupSession(page, { typePrefix: 'Catalogue', maxParticipants: 12 });

        await loginAsAdmin(page);
        await page.goto('/admin/sessions');
        const row = sessionRow(page, typeName);
        await expect(row).toContainText(coachName);
        await expect(row).toContainText('12/12 places');
        await expect(row.locator('.status-badge')).toHaveText('open');

        await logout(page);
        await page.goto('/courses');
        await expect(page.locator('.course-card', { hasText: typeName })).toBeVisible();
    });

    test('les champs de la séance sont validés côté serveur', async ({ page }) => {
        await loginAsAdmin(page);
        const typeName = 'Validation ' + uniqueSuffix();
        const coachEmail = uniqueEmail('valcoach');
        const lastname = `V${uniqueSuffix()}`;
        await createCourseType(page, { name: typeName });
        await createCoach(page, { lastname, email: coachEmail });

        // Fin avant début
        await createSession(page, { courseTypeName: typeName, coachName: `Coach ${lastname}`, date: inDays(5), startTime: '11:00', endTime: '10:00' });
        await expect(page.locator('.form-errors')).toContainText("L'heure de fin doit être après l'heure de début");

        // Minimum supérieur au maximum
        await fillSessionForm(page, { courseTypeName: typeName, coachName: `Coach ${lastname}`, date: inDays(5), minParticipants: 8, maxParticipants: 3 });
        await page.click('form.auth-form button[type="submit"]');
        await expect(page.locator('.form-errors')).toContainText('minimum doit être inférieur ou égal au maximum');

        // Aucun champ rempli : les contrôles serveur listent chaque manque
        await page.goto('/admin/sessions/create');
        await submitWithoutBrowserValidation(page);
        const errors = page.locator('.form-errors');
        await expect(errors).toContainText('Le type de cours est obligatoire');
        await expect(errors).toContainText('Le coach est obligatoire');
        await expect(errors).toContainText('La date est obligatoire');
    });

    test('modifier une séance met à jour ses places et son statut', async ({ page }) => {
        const { typeName } = await setupSession(page, { typePrefix: 'Modif', maxParticipants: 10 });
        await loginAsAdmin(page);
        await page.goto('/admin/sessions');

        await sessionRow(page, typeName).getByRole('button', { name: 'Modifier' }).click();
        await expect(page.locator('h1')).toHaveText('Modifier la séance');
        await expect(page.locator('#session-max')).toHaveValue('10');
        await expect(page.locator('#session-start')).toHaveValue('10:00');

        await page.fill('#session-max', '4');
        await page.selectOption('#session-status', 'cancelled');
        await page.click('form.auth-form button[type="submit"]');

        await expect(page).toHaveURL(/\/admin\/sessions\?status=session-updated$/);
        await expect(page.locator('.flash-message-success')).toContainText('Séance modifiée');
        const row = sessionRow(page, typeName);
        await expect(row).toContainText('4/4 places');
        await expect(row.locator('.status-badge')).toHaveText('cancelled');
    });

    test('supprimer une séance supprime aussi les inscriptions liées', async ({ page }) => {
        const { typeName } = await setupSession(page, { typePrefix: 'Suppression' });
        const member = await newMemberWithDog(page, { prefix: 'delsession' });
        await joinFromCatalogue(page, typeName);
        await expect(page.locator('.upcoming-item', { hasText: typeName })).toBeVisible();
        await logout(page);

        await loginAsAdmin(page);
        await page.goto('/admin/sessions');
        page.once('dialog', (dialog) => dialog.accept());
        await sessionRow(page, typeName).getByRole('button', { name: 'Supprimer' }).click();

        await expect(page).toHaveURL(/\/admin\/sessions\?status=session-deleted$/);
        await expect(page.locator('.flash-message-success')).toContainText('Séance supprimée');
        await expect(sessionRow(page, typeName)).toHaveCount(0);
        await logout(page);

        await login(page, member.email);
        await expect(page.locator('.upcoming-item', { hasText: typeName })).toHaveCount(0);
        await expect(page.locator('.stat-card', { hasText: 'Cours suivis' }).locator('.stat-card-value')).toHaveText('0');
    });

    test('la liste des inscrits d\'une séance sans réservation est vide', async ({ page }) => {
        const { typeName } = await setupSession(page, { typePrefix: 'SansInscrit' });
        await loginAsAdmin(page);
        await page.goto('/admin/sessions');

        await sessionRow(page, typeName).getByRole('button', { name: 'Inscrits' }).click();

        await expect(page).toHaveURL(/\/admin\/sessions\/\d+\/bookings$/);
        await expect(page.locator('.empty-state')).toContainText('Aucune inscription pour cette séance');
        await page.getByRole('button', { name: 'Retour aux séances' }).click();
        await expect(page).toHaveURL(/\/admin\/sessions$/);
    });

    test('modifier ou supprimer une séance inexistante renvoie une 404', async ({ page }) => {
        await loginAsAdmin(page);

        const edit = await page.goto('/admin/sessions/99999999/edit');
        expect(edit.status()).toBe(404);

        const remove = await page.request.post('/admin/sessions/99999999/delete');
        expect(remove.status()).toBe(404);
    });

    test('l\'id de la séance créée est bien celui de sa fiche publique', async ({ page }) => {
        const { typeName, sessionId } = await setupSession(page, { typePrefix: 'Identifiant' });

        await page.goto(`/courses/${sessionId}`);

        await expect(page.locator('h1.page-title')).toHaveText(typeName);
        await loginAsAdmin(page);
        await page.goto('/admin/sessions');
        expect(await sessionIdFromList(page, typeName)).toBe(sessionId);
    });
});
