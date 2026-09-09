const { test, expect } = require('@playwright/test');
const {
    setupSession,
    newMemberWithDog,
    addDog,
    joinFromCatalogue,
    courseCard,
    login,
    logout,
    loginAsAdmin,
    monthsAgo,
} = require('./helpers');

const upcomingItem = (page, typeName) => page.locator('.upcoming-item', { hasText: typeName });

test.describe('Inscription à une séance', () => {
    test('un membre avec un chien s\'inscrit depuis le catalogue et retrouve la séance sur son dashboard', async ({ page }) => {
        const { typeName, coachName } = await setupSession(page, { typePrefix: 'Booking', minParticipants: 1, maxParticipants: 5 });
        await newMemberWithDog(page, { dogName: 'Buddy', prefix: 'booker' });

        await joinFromCatalogue(page, typeName);

        await expect(page).toHaveURL(/\/dashboard\?status=booked$/);
        await expect(page.locator('.flash-message-success')).toContainText('Inscription confirmée');

        const item = upcomingItem(page, typeName);
        await expect(item).toContainText('avec Buddy');
        await expect(item).toContainText('10:00 - 11:00');
        await expect(item).toContainText(coachName);
        await expect(item.locator('.status-badge')).toHaveText('Confirmé');
        await expect(page.locator('.stat-card', { hasText: 'Cours suivis' }).locator('.stat-card-value')).toHaveText('1');
        await expect(page.locator('.stat-card', { hasText: 'Prochaines séances' }).locator('.stat-card-value')).toHaveText('1');
    });

    test('les places restantes diminuent après une inscription', async ({ page }) => {
        const { typeName, sessionId } = await setupSession(page, { typePrefix: 'Places', maxParticipants: 5 });
        await newMemberWithDog(page, { prefix: 'places' });

        await page.goto('/courses');
        await expect(courseCard(page, typeName).locator('.course-badge')).toHaveText('5 places');

        await joinFromCatalogue(page, typeName);
        await page.goto(`/courses/${sessionId}`);

        await expect(page.locator('.course-badge')).toHaveText('4/5 places');
    });

    test('s\'inscrire depuis la fiche du cours fonctionne aussi', async ({ page }) => {
        const { typeName, sessionId } = await setupSession(page, { typePrefix: 'DepuisFiche' });
        await newMemberWithDog(page, { prefix: 'fromdetail' });

        await page.goto(`/courses/${sessionId}`);
        await page.getByRole('button', { name: "S'inscrire au cours" }).click();

        await expect(page).toHaveURL(/\/dashboard\?status=booked/);
        await expect(upcomingItem(page, typeName)).toBeVisible();
    });

    test('la fiche indique que le chien est déjà inscrit et l\'inscription en double est refusée', async ({ page }) => {
        const { typeName, sessionId } = await setupSession(page, { typePrefix: 'Doublon' });
        await newMemberWithDog(page, { dogName: 'Doublon', prefix: 'dup' });
        await joinFromCatalogue(page, typeName);

        await page.goto(`/courses/${sessionId}`);
        await expect(page.locator('.course-notice')).toContainText('est déjà inscrit');
        await expect(page.getByRole('button', { name: 'Déjà inscrit(e)' })).toBeDisabled();

        const response = await page.request.post(`/courses/${sessionId}/join`);
        expect(response.url()).toContain('/courses?status=already-booked');
        await page.goto('/courses?status=already-booked');
        await expect(page.locator('.flash-message-warning')).toContainText('déjà inscrit');
    });

    test('s\'inscrire à un cours inexistant renvoie une 404', async ({ page }) => {
        await newMemberWithDog(page, { prefix: 'ghostcourse' });

        const response = await page.request.post('/courses/99999999/join');

        expect(response.status()).toBe(404);
    });
});

test.describe('Confirmation selon le minimum de participants', () => {
    test('l\'inscription reste en attente tant que le minimum n\'est pas atteint, puis se confirme', async ({ page }) => {
        const { typeName } = await setupSession(page, { typePrefix: 'Minimum', minParticipants: 2, maxParticipants: 5 });

        // Premier membre : en attente
        const first = await newMemberWithDog(page, { dogName: 'Premier', prefix: 'minfirst' });
        await joinFromCatalogue(page, typeName);
        await expect(page).toHaveURL(/\/dashboard\?status=booked-pending$/);
        await expect(page.locator('.flash-message-warning')).toContainText('sera confirmée dès que le nombre minimum');
        await expect(upcomingItem(page, typeName).locator('.status-badge')).toHaveText('En attente');
        await logout(page);

        // Second membre : le minimum est atteint, tout le monde est confirmé
        await newMemberWithDog(page, { dogName: 'Second', prefix: 'minsecond' });
        await joinFromCatalogue(page, typeName);
        await expect(page).toHaveURL(/\/dashboard\?status=booked$/);
        await expect(upcomingItem(page, typeName).locator('.status-badge')).toHaveText('Confirmé');
        await logout(page);

        await login(page, first.email);
        await expect(upcomingItem(page, typeName).locator('.status-badge')).toHaveText('Confirmé');
    });
});

test.describe('Liste d\'attente', () => {
    test('une séance complète place le membre suivant en liste d\'attente', async ({ page }) => {
        const { typeName } = await setupSession(page, { typePrefix: 'Complet', maxParticipants: 1 });

        await newMemberWithDog(page, { dogName: 'Premier', prefix: 'first' });
        await joinFromCatalogue(page, typeName);
        await expect(page).toHaveURL(/status=booked/);
        await logout(page);

        await newMemberWithDog(page, { dogName: 'Second', prefix: 'second' });
        await page.goto('/courses');
        const card = courseCard(page, typeName);
        await expect(card).toHaveClass(/course-card-full/);
        await expect(card.locator('.course-badge')).toHaveText('Complet');
        await expect(card.locator('form button[type="submit"]')).toHaveText("Liste d'attente");

        await card.locator('form button[type="submit"]').click();

        await expect(page).toHaveURL(/\/courses\?status=waiting$/);
        await expect(page.locator('.flash-message-warning')).toContainText("liste d'attente");
        // En attente de place : rien dans "Prochaines séances"
        await page.goto('/dashboard');
        await expect(upcomingItem(page, typeName)).toHaveCount(0);
    });

    test('la fiche d\'une séance complète propose de rejoindre la liste d\'attente', async ({ page }) => {
        const { typeName, sessionId } = await setupSession(page, { typePrefix: 'FicheComplete', maxParticipants: 1 });
        await newMemberWithDog(page, { prefix: 'fullfirst' });
        await joinFromCatalogue(page, typeName);
        await logout(page);

        await newMemberWithDog(page, { prefix: 'fullsecond' });
        await page.goto(`/courses/${sessionId}`);

        await expect(page.locator('.course-badge')).toHaveText('Complet');
        await expect(page.getByRole('button', { name: "Rejoindre la liste d'attente" })).toBeVisible();
    });

    test('une désinscription fait passer le premier de la liste d\'attente en séance confirmée', async ({ page }) => {
        const { typeName, sessionId } = await setupSession(page, { typePrefix: 'Promotion', maxParticipants: 1 });

        const first = await newMemberWithDog(page, { dogName: 'Premier', prefix: 'promofirst' });
        await joinFromCatalogue(page, typeName);
        await logout(page);

        const second = await newMemberWithDog(page, { dogName: 'Second', prefix: 'promosecond' });
        await joinFromCatalogue(page, typeName);
        await expect(page).toHaveURL(/status=waiting/);
        await logout(page);

        // Le premier se désinscrit -> libère la place
        await login(page, first.email);
        await page.goto(`/courses/${sessionId}`);
        page.once('dialog', (dialog) => dialog.accept());
        await page.getByRole('button', { name: /Se désinscrire/ }).click();
        await expect(page).toHaveURL(new RegExp(`/courses/${sessionId}\\?status=booking-cancelled`));
        await logout(page);

        // Le second est promu
        await login(page, second.email);
        await expect(upcomingItem(page, typeName)).toBeVisible();
        await expect(upcomingItem(page, typeName).locator('.status-badge')).toHaveText('Confirmé');
    });

    test('supprimer un chien inscrit libère sa place pour la liste d\'attente', async ({ page }) => {
        const { typeName } = await setupSession(page, { typePrefix: 'PromoSuppression', maxParticipants: 1 });

        const first = await newMemberWithDog(page, { dogName: 'Partira', prefix: 'delfirst' });
        await joinFromCatalogue(page, typeName);
        await logout(page);

        const second = await newMemberWithDog(page, { dogName: 'Attend', prefix: 'delsecond' });
        await joinFromCatalogue(page, typeName);
        await logout(page);

        await login(page, first.email);
        await page.goto('/dogs');
        await page.locator('.dog-profile-card .dog-profile-card-btn', { hasText: 'Modifier' }).click();
        page.once('dialog', (dialog) => dialog.accept());
        await page.getByRole('button', { name: 'Supprimer ce chien' }).click();
        await expect(page).toHaveURL(/status=dog-deleted/);
        await logout(page);

        await login(page, second.email);
        await expect(upcomingItem(page, typeName).locator('.status-badge')).toHaveText('Confirmé');
    });
});

test.describe('Désinscription', () => {
    test('un membre se désinscrit depuis la fiche du cours', async ({ page }) => {
        const { typeName, sessionId } = await setupSession(page, { typePrefix: 'Desinscription' });
        await newMemberWithDog(page, { prefix: 'cancel' });
        await joinFromCatalogue(page, typeName);

        await page.goto(`/courses/${sessionId}`);
        page.once('dialog', (dialog) => dialog.accept());
        await page.getByRole('button', { name: /Se désinscrire/ }).click();

        await expect(page).toHaveURL(new RegExp(`/courses/${sessionId}\\?status=booking-cancelled$`));
        await expect(page.locator('.flash-message-success')).toContainText('Votre inscription a été annulée');
        // Il peut à nouveau s'inscrire
        await expect(page.getByRole('button', { name: "S'inscrire au cours" })).toBeVisible();

        await page.goto('/dashboard');
        await expect(upcomingItem(page, typeName)).toHaveCount(0);
    });

    test('refuser la confirmation conserve l\'inscription', async ({ page }) => {
        const { typeName, sessionId } = await setupSession(page, { typePrefix: 'GardeInscription' });
        await newMemberWithDog(page, { prefix: 'keepbooking' });
        await joinFromCatalogue(page, typeName);

        await page.goto(`/courses/${sessionId}`);
        page.once('dialog', (dialog) => dialog.dismiss());
        await page.getByRole('button', { name: /Se désinscrire/ }).click();

        await expect(page).toHaveURL(new RegExp(`/courses/${sessionId}$`));
        await page.goto('/dashboard');
        await expect(upcomingItem(page, typeName)).toBeVisible();
    });

    test('un membre ne peut pas désinscrire le chien d\'un autre membre', async ({ page }) => {
        const { typeName, sessionId } = await setupSession(page, { typePrefix: 'Intrusion' });

        const owner = await newMemberWithDog(page, { dogName: 'Protege', prefix: 'victim' });
        await joinFromCatalogue(page, typeName);
        await page.goto('/dogs');
        const href = await page.locator('.dog-profile-card .dog-profile-card-btn', { hasText: 'Modifier' }).getAttribute('href');
        const dogId = href.match(/\/dogs\/(\d+)\/edit/)[1];
        await logout(page);

        await newMemberWithDog(page, { prefix: 'intruder' });
        await page.request.post(`/courses/${sessionId}/cancel`, { form: { dog_id: dogId } });
        await logout(page);

        await login(page, owner.email);
        await expect(upcomingItem(page, typeName)).toBeVisible();
    });
});

test.describe('Plusieurs chiens', () => {
    test('le membre choisit quel chien inscrire, puis désinscrit celui-ci depuis la fiche', async ({ page }) => {
        const { typeName, sessionId } = await setupSession(page, { typePrefix: 'PlusieursChiens' });
        await newMemberWithDog(page, { dogName: 'Un', birthDate: monthsAgo(24), prefix: 'multidog' });
        await addDog(page, { name: 'Deux', birthDate: monthsAgo(24) });

        // Catalogue : liste déroulante des chiens
        await page.goto('/courses');
        const card = courseCard(page, typeName);
        await expect(card.locator('select[name="dog_id"] option')).toHaveCount(2);
        await card.locator('select[name="dog_id"]').selectOption({ label: 'Deux' });
        await card.locator('form button[type="submit"]').click();

        await expect(page).toHaveURL(/status=booked/);
        await expect(upcomingItem(page, typeName)).toContainText('avec Deux');
        await expect(upcomingItem(page, typeName)).not.toContainText('avec Un');

        // Fiche : "Deux" est inscrit, seul "Un" reste à inscrire
        await page.goto(`/courses/${sessionId}`);
        await expect(page.locator('.course-notice')).toContainText('Deux');
        await expect(page.locator('.course-actions input[name="dog_id"]')).toHaveCount(1);
        await expect(page.getByRole('button', { name: 'Se désinscrire (Deux)' })).toBeVisible();

        page.once('dialog', (dialog) => dialog.accept());
        await page.getByRole('button', { name: 'Se désinscrire (Deux)' }).click();
        await expect(page).toHaveURL(/status=booking-cancelled/);
    });
});

test.describe('Inscriptions vues par l\'administrateur', () => {
    test('la liste des inscrits affiche chien, propriétaire et statut, et permet d\'annuler', async ({ page }) => {
        const { typeName, sessionId } = await setupSession(page, { typePrefix: 'Inscrits' });
        const member = await newMemberWithDog(page, { dogName: 'Inscrit', prefix: 'listed' });
        await joinFromCatalogue(page, typeName);
        await logout(page);

        await loginAsAdmin(page);
        await page.goto(`/admin/sessions/${sessionId}/bookings`);

        const row = page.locator('.reservation-item', { hasText: 'Inscrit' });
        await expect(page.locator('h1.page-title')).toContainText(typeName);
        await expect(row).toContainText('Propriétaire : Jean Test');
        await expect(row.locator('.status-badge')).toHaveText('Confirmé');

        page.once('dialog', (dialog) => dialog.accept());
        await row.getByRole('button', { name: 'Annuler' }).click();

        await expect(page).toHaveURL(new RegExp(`/admin/sessions/${sessionId}/bookings\\?status=booking-cancelled-staff$`));
        await expect(page.locator('.flash-message-success')).toContainText("L'inscription a été annulée");
        await expect(page.locator('.empty-state')).toContainText('Aucune inscription');
        await logout(page);

        // Côté membre : la séance a disparu du dashboard
        await login(page, member.email);
        await expect(upcomingItem(page, typeName)).toHaveCount(0);
    });

    test('la liste d\'attente est visible côté admin avec le statut "Liste d\'attente"', async ({ page }) => {
        const { typeName, sessionId } = await setupSession(page, { typePrefix: 'AttenteAdmin', maxParticipants: 1 });
        await newMemberWithDog(page, { dogName: 'Titulaire', prefix: 'holder' });
        await joinFromCatalogue(page, typeName);
        await logout(page);
        await newMemberWithDog(page, { dogName: 'EnAttente', prefix: 'waiter' });
        await joinFromCatalogue(page, typeName);
        await logout(page);

        await loginAsAdmin(page);
        await page.goto(`/admin/sessions/${sessionId}/bookings`);

        await expect(page.locator('.reservation-item', { hasText: 'EnAttente' }).locator('.status-badge')).toHaveText("Liste d'attente");
        await expect(page.locator('.reservation-item', { hasText: 'Titulaire' }).locator('.status-badge')).toHaveText('Confirmé');
    });
});
