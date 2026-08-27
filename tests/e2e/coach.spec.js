const { test, expect } = require('@playwright/test');
const {
    inDays,
    login,
    logout,
    loginAsAdmin,
    createCourseType,
    createSession,
    sessionRow,
    sessionIdFromList,
    setupSession,
    newMemberWithDog,
    joinFromCatalogue,
    uniqueSuffix,
} = require('./helpers');

test.describe('Espace coach', () => {
    test('la navigation du coach ne contient que les séances', async ({ page }) => {
        const { coachEmail, coachPassword } = await setupSession(page, { typePrefix: 'CoachNav' });
        await login(page, coachEmail, coachPassword);

        await expect(page).toHaveURL(/\/admin\/sessions$/);
        const nav = page.locator('.dashboard-nav');
        await expect(nav.locator('a[href="/admin/sessions"]')).toBeVisible();
        await expect(nav.locator('a[href="/admin/course-types"]')).toHaveCount(0);
        await expect(nav.locator('a[href="/admin/coaches"]')).toHaveCount(0);
        await expect(nav.locator('a[href="/dogs"]')).toHaveCount(0);
    });

    test('un coach ne peut pas accéder aux pages réservées aux administrateurs', async ({ page }) => {
        const { coachEmail, coachPassword } = await setupSession(page, { typePrefix: 'CoachAdminOnly' });
        await login(page, coachEmail, coachPassword);

        await page.goto('/admin/course-types');
        await expect(page).toHaveURL(/\/admin\/sessions\?status=admins-only$/);
        await expect(page.locator('.flash-message-error')).toContainText('réservée aux administrateurs');

        await page.goto('/admin/coaches');
        await expect(page).toHaveURL(/\/admin\/sessions\?status=admins-only$/);
    });

    test('un coach ne peut pas utiliser les pages réservées aux membres', async ({ page }) => {
        const { coachEmail, coachPassword } = await setupSession(page, { typePrefix: 'CoachNoMember' });
        await login(page, coachEmail, coachPassword);

        await page.goto('/dogs');
        await expect(page).toHaveURL(/\/admin\/sessions$/);
        await page.goto('/dashboard');
        await expect(page).toHaveURL(/\/admin\/sessions$/);
    });

    test('le coach voit ses séances avec les actions, et celles des autres sans action', async ({ page }) => {
        const mine = await setupSession(page, { typePrefix: 'Mienne' });
        const other = await setupSession(page, { typePrefix: 'Autre' });
        await login(page, mine.coachEmail, mine.coachPassword);

        const myRow = sessionRow(page, mine.typeName);
        await expect(myRow.getByRole('button', { name: 'Modifier' })).toBeVisible();
        await expect(myRow.getByRole('button', { name: 'Inscrits' })).toBeVisible();
        await expect(myRow.getByRole('button', { name: 'Supprimer' })).toBeVisible();

        const otherRow = sessionRow(page, other.typeName);
        await expect(otherRow).toBeVisible();
        await expect(otherRow.getByRole('button')).toHaveCount(0);
    });

    test('un coach crée une séance : elle lui est automatiquement assignée', async ({ page }) => {
        const { coachEmail, coachPassword, coachName } = await setupSession(page, { typePrefix: 'CoachCreate' });
        // Un type de cours pour la nouvelle séance
        const typeName = 'CoachType ' + uniqueSuffix();
        await loginAsAdmin(page);
        await createCourseType(page, { name: typeName });
        await logout(page);

        await login(page, coachEmail, coachPassword);
        await page.goto('/admin/sessions/create');
        // Pas de liste de coachs : le champ affiche le coach connecté et est désactivé
        await expect(page.locator('#session-coach')).toHaveCount(0);
        await expect(page.locator('input[disabled]')).toHaveValue(coachName);

        await createSession(page, { courseTypeName: typeName, date: inDays(9) });

        await expect(page).toHaveURL(/\/admin\/sessions\?status=session-created$/);
        await expect(sessionRow(page, typeName)).toContainText(coachName);
        await expect(sessionRow(page, typeName).getByRole('button', { name: 'Modifier' })).toBeVisible();
    });

    test('un coach modifie et supprime sa propre séance', async ({ page }) => {
        const { coachEmail, coachPassword, typeName } = await setupSession(page, { typePrefix: 'CoachEdit', maxParticipants: 10 });
        await login(page, coachEmail, coachPassword);

        await sessionRow(page, typeName).getByRole('button', { name: 'Modifier' }).click();
        await page.fill('#session-max', '6');
        await page.click('form.auth-form button[type="submit"]');
        await expect(page).toHaveURL(/status=session-updated/);
        await expect(sessionRow(page, typeName)).toContainText('6/6 places');

        page.once('dialog', (dialog) => dialog.accept());
        await sessionRow(page, typeName).getByRole('button', { name: 'Supprimer' }).click();
        await expect(page).toHaveURL(/status=session-deleted/);
        await expect(sessionRow(page, typeName)).toHaveCount(0);
    });

    test('un coach voit et annule les inscriptions de sa séance', async ({ page }) => {
        const { coachEmail, coachPassword, typeName, sessionId } = await setupSession(page, { typePrefix: 'CoachBookings' });
        await newMemberWithDog(page, { dogName: 'Suivi', prefix: 'coachbook' });
        await joinFromCatalogue(page, typeName);
        await logout(page);

        await login(page, coachEmail, coachPassword);
        await page.goto(`/admin/sessions/${sessionId}/bookings`);
        const row = page.locator('.reservation-item', { hasText: 'Suivi' });
        await expect(row).toBeVisible();

        page.once('dialog', (dialog) => dialog.accept());
        await row.getByRole('button', { name: 'Annuler' }).click();
        await expect(page).toHaveURL(/status=booking-cancelled-staff/);
    });

    test('un coach ne peut pas gérer la séance d\'un autre coach', async ({ page }) => {
        const mine = await setupSession(page, { typePrefix: 'CoachA' });
        const other = await setupSession(page, { typePrefix: 'CoachB' });
        await login(page, mine.coachEmail, mine.coachPassword);

        // Modification, inscrits : refusées
        for (const path of [`/admin/sessions/${other.sessionId}/edit`, `/admin/sessions/${other.sessionId}/bookings`]) {
            await page.goto(path);
            await expect(page).toHaveURL(/\/admin\/sessions\?status=not-your-session$/);
            await expect(page.locator('.flash-message-error')).toContainText('vos propres séances');
        }

        // Modification et suppression forcées par requête directe : refusées
        const update = await page.request.post(`/admin/sessions/${other.sessionId}/update`, {
            form: { id_course_type: '1', id_user: '1', date: inDays(3), start_time: '09:00', end_time: '10:00', min_participants: '1', max_participants: '2', status: 'open' },
        });
        expect(update.url()).toContain('status=not-your-session');
        const remove = await page.request.post(`/admin/sessions/${other.sessionId}/delete`);
        expect(remove.url()).toContain('status=not-your-session');

        // La séance de l'autre coach existe toujours, inchangée
        await page.goto('/admin/sessions');
        await expect(sessionRow(page, other.typeName)).toContainText('10/10 places');
        await logout(page);
        await loginAsAdmin(page);
        await page.goto('/admin/sessions');
        expect(await sessionIdFromList(page, other.typeName)).toBe(other.sessionId);
    });
});
