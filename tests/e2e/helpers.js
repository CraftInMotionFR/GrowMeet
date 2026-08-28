// Helpers partagés entre les specs e2e GrowMeet
const { expect } = require('@playwright/test');

const ADMIN_EMAIL = 'admin@growmeet.com';
const ADMIN_PASSWORD = 'Admin1234!';
const DEFAULT_PASSWORD = 'Password123!';

// PNG 1x1 valide, pour tester l'upload de photo sans fichier sur disque
const TINY_PNG = Buffer.from(
    'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==',
    'base64'
);

let counter = 0;

function uniqueSuffix() {
    counter += 1;
    return `${Date.now()}${counter}${Math.floor(Math.random() * 1000)}`;
}

function uniqueEmail(prefix) {
    return `${prefix}.${uniqueSuffix()}@test.growmeet.com`;
}

function formatDate(date) {
    const p = (n) => String(n).padStart(2, '0');
    return `${date.getFullYear()}-${p(date.getMonth() + 1)}-${p(date.getDate())}`;
}

// Date (YYYY-MM-DD) dans N jours
function inDays(days) {
    const d = new Date();
    d.setDate(d.getDate() + days);
    return formatDate(d);
}

// Date (YYYY-MM-DD) il y a N mois : pratique pour fabriquer la date de naissance d'un chien d'un âge donné
function monthsAgo(months) {
    const d = new Date();
    d.setMonth(d.getMonth() - months);
    return formatDate(d);
}

async function login(page, email, password = DEFAULT_PASSWORD) {
    await page.goto('/login');
    await page.fill('#email', email);
    await page.fill('#password', password);
    await page.click('form.auth-form button[type="submit"]');
}

async function logout(page) {
    await page.goto('/logout');
}

async function loginAsAdmin(page) {
    await login(page, ADMIN_EMAIL, ADMIN_PASSWORD);
    await expect(page).toHaveURL(/\/admin\/course-types/);
}

// Inscription complète (la case CGU est obligatoire)
async function registerMember(page, { firstname = 'Jean', lastname = 'Test', email, phone = '', password = DEFAULT_PASSWORD, terms = true } = {}) {
    await page.goto('/register');
    await page.fill('#firstname', firstname);
    await page.fill('#lastname', lastname);
    await page.fill('#reg-email', email);
    if (phone) await page.fill('#reg-phone', phone);
    await page.fill('#reg-password', password);
    await page.fill('#reg-password-confirm', password);
    if (terms) await page.check('#terms');
    await page.click('form.auth-form button[type="submit"]');
}

// Ajoute un chien via /dogs/create ; le sexe est un <select>, la race un champ texte avec suggestions
async function addDog(page, { name = 'Rex', breed = 'Golden Retriever', gender = 'male', birthDate = monthsAgo(24) } = {}) {
    await page.goto('/dogs/create');
    await page.fill('#dog-name', name);
    await page.fill('#dog-breed', breed);
    await page.selectOption('#dog-gender', gender);
    await page.fill('#dog-birth-date', birthDate);
    await page.click('form.auth-form button[type="submit"]');
}

// Membre inscrit + un chien, connecté et prêt à réserver
async function newMemberWithDog(page, { dogName = 'Rex', birthDate = monthsAgo(24), prefix = 'member' } = {}) {
    const email = uniqueEmail(prefix);
    await registerMember(page, { email });
    await expect(page).toHaveURL(/\/dogs\/create$/);
    await addDog(page, { name: dogName, birthDate });
    await expect(page).toHaveURL(/\/dogs\?status=dog-added/);
    return { email, password: DEFAULT_PASSWORD, dogName };
}

async function createCourseType(page, { name, description = '', ageMinMonths = '', ageMaxMonths = '' }) {
    await page.goto('/admin/course-types/create');
    await page.fill('#ct-name', name);
    if (description) await page.fill('#ct-description', description);
    if (ageMinMonths !== '') await page.fill('#ct-age-min', String(ageMinMonths));
    if (ageMaxMonths !== '') await page.fill('#ct-age-max', String(ageMaxMonths));
    await page.click('form.auth-form button[type="submit"]');
}

async function createCoach(page, { firstname = 'Coach', lastname = 'Test', email, password = DEFAULT_PASSWORD }) {
    await page.goto('/admin/coaches/create');
    await page.fill('#coach-firstname', firstname);
    await page.fill('#coach-lastname', lastname);
    await page.fill('#coach-email', email);
    await page.fill('#coach-password', password);
    await page.click('form.auth-form button[type="submit"]');
}

// Sélectionne une option par son texte (les <option> contiennent des espaces/retours à la ligne, on passe donc par la valeur)
async function selectByText(page, selector, text) {
    const value = await page.locator(`${selector} option`, { hasText: text }).first().getAttribute('value');
    await page.selectOption(selector, value);
}

async function fillSessionForm(page, { courseTypeName, coachName, date, startTime = '10:00', endTime = '11:00', minParticipants = 1, maxParticipants = 10, status }) {
    if (courseTypeName) await selectByText(page, '#session-type', courseTypeName);
    // Un coach n'a pas de select : la séance lui est automatiquement assignée
    if (coachName && (await page.locator('#session-coach').count())) {
        await selectByText(page, '#session-coach', coachName);
    }
    if (date) await page.fill('#session-date', date);
    await page.fill('#session-start', startTime);
    await page.fill('#session-end', endTime);
    await page.fill('#session-min', String(minParticipants));
    await page.fill('#session-max', String(maxParticipants));
    if (status) await page.selectOption('#session-status', status);
}

async function createSession(page, options) {
    await page.goto('/admin/sessions/create');
    await fillSessionForm(page, options);
    await page.click('form.auth-form button[type="submit"]');
}

// Ligne d'une séance dans /admin/sessions, identifiée par le nom (unique) de son type de cours
function sessionRow(page, typeName) {
    return page.locator('.reservation-item', { hasText: typeName });
}

// Id d'une séance lu dans le lien "Inscrits" de sa ligne
async function sessionIdFromList(page, typeName) {
    const href = await sessionRow(page, typeName).locator('a[href$="/bookings"]').getAttribute('href');
    return Number(href.match(/\/admin\/sessions\/(\d+)\/bookings/)[1]);
}

/**
 * Prépare en tant qu'admin : un type de cours, un coach et une séance, puis déconnecte.
 * Tout est nommé de façon unique pour que les tests ne se marchent pas dessus.
 */
async function setupSession(page, { typePrefix = 'Type', ageMinMonths = '', ageMaxMonths = '', days = 14, minParticipants = 1, maxParticipants = 10, startTime = '10:00', endTime = '11:00' } = {}) {
    const suffix = uniqueSuffix();
    const typeName = `${typePrefix} ${suffix}`;
    const coachEmail = uniqueEmail('coach');
    const coachLastname = `Coach${suffix}`;
    const coachName = `Coach ${coachLastname}`;

    await loginAsAdmin(page);
    await createCourseType(page, { name: typeName, ageMinMonths, ageMaxMonths });
    await expect(page).toHaveURL(/status=course-type-created/);
    await createCoach(page, { firstname: 'Coach', lastname: coachLastname, email: coachEmail });
    await expect(page).toHaveURL(/status=coach-created/);
    await createSession(page, { courseTypeName: typeName, coachName, date: inDays(days), startTime, endTime, minParticipants, maxParticipants });
    await expect(page).toHaveURL(/status=session-created/);
    const sessionId = await sessionIdFromList(page, typeName);
    await logout(page);

    return { typeName, coachEmail, coachName, coachLastname, coachPassword: DEFAULT_PASSWORD, sessionId };
}

// Carte d'un cours dans le catalogue /courses
function courseCard(page, typeName) {
    return page.locator('.course-card', { hasText: typeName });
}

// Inscrit le chien du membre connecté depuis le catalogue (un seul chien -> champ caché)
async function joinFromCatalogue(page, typeName) {
    await page.goto('/courses');
    await courseCard(page, typeName).locator('form button[type="submit"]').click();
}

module.exports = {
    ADMIN_EMAIL,
    ADMIN_PASSWORD,
    DEFAULT_PASSWORD,
    TINY_PNG,
    uniqueSuffix,
    uniqueEmail,
    inDays,
    monthsAgo,
    login,
    logout,
    loginAsAdmin,
    registerMember,
    addDog,
    newMemberWithDog,
    createCourseType,
    createCoach,
    selectByText,
    fillSessionForm,
    createSession,
    sessionRow,
    sessionIdFromList,
    setupSession,
    courseCard,
    joinFromCatalogue,
};
