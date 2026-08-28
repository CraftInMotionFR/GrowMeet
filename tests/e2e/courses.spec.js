const { test, expect } = require('@playwright/test');
const {
    setupSession,
    newMemberWithDog,
    addDog,
    courseCard,
    monthsAgo,
} = require('./helpers');

test.describe('Catalogue des cours', () => {
    test('une carte affiche titre, âge requis, horaire, coach et places', async ({ page }) => {
        const { typeName, coachName } = await setupSession(page, { typePrefix: 'Carte', maxParticipants: 8 });
        await newMemberWithDog(page, { prefix: 'catalogue' });

        await page.goto('/courses');
        const card = courseCard(page, typeName);

        await expect(card.locator('.course-card-title')).toHaveText(typeName);
        await expect(card).toContainText('Âge requis : Tous âges');
        await expect(card).toContainText(coachName);
        await expect(card).toContainText('10:00');
        await expect(card.locator('.course-badge')).toHaveText('8 places');
        await expect(card.locator('form button[type="submit"]')).toHaveText("S'inscrire");
    });

    test('le compteur indique le nombre de cours affichés', async ({ page }) => {
        await newMemberWithDog(page, { prefix: 'count' });
        await page.goto('/courses');

        const total = await page.locator('.course-card').count();
        await expect(page.locator('#courses-count')).toHaveText(`${total} cours`);
    });

    test('la recherche filtre par titre et affiche un message si rien ne correspond', async ({ page }) => {
        const { typeName } = await setupSession(page, { typePrefix: 'Recherche' });
        await newMemberWithDog(page, { prefix: 'search' });

        await page.goto('/courses');
        await page.fill('#search-input', typeName.toLowerCase());
        await expect(page.locator('.course-card:visible')).toHaveCount(1);
        await expect(courseCard(page, typeName)).toBeVisible();
        await expect(page.locator('#courses-count')).toHaveText('1 cours');

        await page.fill('#search-input', 'zzz-aucun-resultat-zzz');
        await expect(page.locator('.course-card:visible')).toHaveCount(0);
        await expect(page.locator('#no-results')).toBeVisible();
        await expect(page.locator('#courses-count')).toHaveText('0 cours');

        await page.fill('#search-input', '');
        await expect(page.locator('#no-results')).toBeHidden();
    });

    test('les filtres par catégorie n\'affichent que les cours du type choisi', async ({ page }) => {
        const socialisation = await setupSession(page, { typePrefix: 'Socialisation', ageMinMonths: 2, ageMaxMonths: 60 });
        const dressage = await setupSession(page, { typePrefix: 'Dressage', ageMinMonths: 2, ageMaxMonths: 60 });
        await newMemberWithDog(page, { prefix: 'filter' });

        await page.goto('/courses');
        await expect(courseCard(page, socialisation.typeName)).toBeVisible();
        await expect(courseCard(page, dressage.typeName)).toBeVisible();

        await page.click('.filter-tag[data-filter="socialisation"]');
        await expect(page.locator('.filter-tag[data-filter="socialisation"]')).toHaveClass(/filter-tag-active/);
        await expect(courseCard(page, socialisation.typeName)).toBeVisible();
        await expect(courseCard(page, dressage.typeName)).toBeHidden();

        await page.click('.filter-tag[data-filter="dressage"]');
        await expect(courseCard(page, dressage.typeName)).toBeVisible();
        await expect(courseCard(page, socialisation.typeName)).toBeHidden();

        await page.click('.filter-tag[data-filter="all"]');
        await expect(courseCard(page, socialisation.typeName)).toBeVisible();
        await expect(courseCard(page, dressage.typeName)).toBeVisible();
    });

    test('un cours "tous âges" apparaît dans chacun des filtres d\'âge', async ({ page }) => {
        const { typeName } = await setupSession(page, { typePrefix: 'ToutAge' });
        await newMemberWithDog(page, { prefix: 'allages' });

        await page.goto('/courses');
        for (const filter of ['chiot', '6-12', '1-2', '2+']) {
            await page.click(`.filter-tag[data-filter="${filter}"]`);
            await expect(courseCard(page, typeName)).toBeVisible();
        }
    });

    test('cliquer sur une carte ouvre la fiche du cours', async ({ page }) => {
        const { typeName, sessionId } = await setupSession(page, { typePrefix: 'Lien' });
        await newMemberWithDog(page, { prefix: 'cardlink' });

        await page.goto('/courses');
        await courseCard(page, typeName).locator('.course-card-link').click();

        await expect(page).toHaveURL(new RegExp(`/courses/${sessionId}$`));
    });

    test('une séance passée n\'apparaît pas dans le catalogue', async ({ page }) => {
        const { typeName } = await setupSession(page, { typePrefix: 'Passee', days: -10 });
        await newMemberWithDog(page, { prefix: 'past' });

        await page.goto('/courses');
        await expect(courseCard(page, typeName)).toHaveCount(0);
    });
});

test.describe('Fiche d\'un cours', () => {
    test('affiche horaire, âge requis, durée, coach, objectifs et lieu', async ({ page }) => {
        const { typeName, coachName, sessionId } = await setupSession(page, {
            typePrefix: 'Fiche',
            startTime: '14:00',
            endTime: '15:30',
            maxParticipants: 6,
        });
        await newMemberWithDog(page, { prefix: 'detail' });

        await page.goto(`/courses/${sessionId}`);

        await expect(page.locator('h1.page-title')).toHaveText(typeName);
        await expect(page.locator('.course-detail-infocard', { hasText: 'Horaire' })).toContainText('14:00 - 15:30');
        await expect(page.locator('.course-detail-infocard', { hasText: 'Âge requis' })).toContainText('Tous âges');
        await expect(page.locator('.course-detail-infocard', { hasText: 'Durée' })).toContainText('1h30');
        await expect(page.locator('.course-detail-coach')).toContainText(coachName);
        await expect(page.locator('.course-goal-list li').first()).toBeVisible();
        await expect(page.locator('.course-location')).toContainText('Terrain A');
        await expect(page.locator('.course-badge')).toHaveText('6/6 places');
    });

    test('le lien de retour ramène au catalogue', async ({ page }) => {
        const { sessionId } = await setupSession(page, { typePrefix: 'Retour' });
        await newMemberWithDog(page, { prefix: 'back' });

        await page.goto(`/courses/${sessionId}`);
        await page.click('.back-link');

        await expect(page).toHaveURL(/\/courses$/);
    });
});

test.describe('Éligibilité selon l\'âge du chien', () => {
    test('un chien dans la tranche d\'âge est éligible et peut s\'inscrire', async ({ page }) => {
        const { sessionId } = await setupSession(page, { typePrefix: 'Eligible', ageMinMonths: 24, ageMaxMonths: 48 });
        // 30 mois : dans la tranche 24-48 mois
        await newMemberWithDog(page, { dogName: 'Adulte', birthDate: monthsAgo(30), prefix: 'eligible' });

        await page.goto(`/courses/${sessionId}`);

        await expect(page.locator('.course-alert-ok')).toContainText('Adulte');
        await expect(page.locator('.course-alert-ok')).toContainText('peut participer');
        await expect(page.getByRole('button', { name: "S'inscrire au cours" })).toBeVisible();
    });

    test('un chien trop jeune est signalé et ne peut pas s\'inscrire', async ({ page }) => {
        const { typeName, sessionId } = await setupSession(page, { typePrefix: 'TropJeune', ageMinMonths: 24, ageMaxMonths: 48 });
        // 6 mois : en dessous de la tranche
        await newMemberWithDog(page, { dogName: 'Chiot', birthDate: monthsAgo(6), prefix: 'tooyoung' });

        await page.goto(`/courses/${sessionId}`);

        await expect(page.locator('.course-alert:not(.course-alert-ok)')).toContainText('Âge non conforme');
        await expect(page.locator('.course-alert:not(.course-alert-ok)')).toContainText('Chiot');
        await expect(page.locator('.course-notice')).toContainText("Aucun de vos chiens n'est éligible");
        await expect(page.locator('form[action$="/join"]')).toHaveCount(0);

        // Le serveur refuse aussi une inscription forcée
        await page.goto('/dogs');
        const href = await page.locator('.dog-profile-card .dog-profile-card-btn', { hasText: 'Modifier' }).getAttribute('href');
        const forcedDogId = href.match(/\/dogs\/(\d+)\/edit/)[1];
        const response = await page.request.post(`/courses/${sessionId}/join`, { form: { dog_id: forcedDogId } });

        expect(response.url()).toContain(`/courses/${sessionId}?status=dog-not-eligible`);
        await page.goto(`/courses/${sessionId}?status=dog-not-eligible`);
        await expect(page.locator('.flash-message-error')).toContainText("tranche d'âge");
        await page.goto('/dashboard');
        await expect(page.locator('.upcoming-list')).not.toContainText(typeName);
    });

    test('un chien trop âgé est signalé comme non éligible', async ({ page }) => {
        const { sessionId } = await setupSession(page, { typePrefix: 'TropAge', ageMinMonths: 2, ageMaxMonths: 12 });
        // 40 mois : au-dessus de la tranche 2-12 mois
        await newMemberWithDog(page, { dogName: 'Senior', birthDate: monthsAgo(40), prefix: 'tooold' });

        await page.goto(`/courses/${sessionId}`);

        await expect(page.locator('.course-alert:not(.course-alert-ok)')).toContainText('Senior');
        await expect(page.locator('.course-alert-ok')).toHaveCount(0);
    });

    test('avec deux chiens, seul celui de la bonne tranche est proposé à l\'inscription', async ({ page }) => {
        const { sessionId } = await setupSession(page, { typePrefix: 'DeuxChiens', ageMinMonths: 24, ageMaxMonths: 48 });
        await newMemberWithDog(page, { dogName: 'Adulte', birthDate: monthsAgo(30), prefix: 'twodogs' });
        await addDog(page, { name: 'Bebe', birthDate: monthsAgo(3) });

        await page.goto(`/courses/${sessionId}`);

        await expect(page.locator('.course-alert-ok')).toContainText('Adulte');
        await expect(page.locator('.course-alert-ok')).not.toContainText('Bebe');
        await expect(page.locator('.course-alert:not(.course-alert-ok)')).toContainText('Bebe');
        // Un seul chien éligible : champ caché, pas de liste déroulante
        await expect(page.locator('select[name="dog_id"]')).toHaveCount(0);
        await expect(page.locator('input[name="dog_id"]')).toHaveCount(1);
    });
});
