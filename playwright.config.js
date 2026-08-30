// @ts-check
const { defineConfig } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests/e2e',
  // Un seul worker : le serveur PHP intégré est mono-processus et les tests partagent la même base MySQL
  workers: 1,
  fullyParallel: false,
  retries: 0,
  reporter: [['list'], ['html', { open: 'never' }]],
  use: {
    baseURL: 'http://localhost:8001',
    trace: 'retain-on-failure',
    screenshot: 'only-on-failure',
  },
  webServer: {
    // Port dédié + base dédiée : on ne réutilise jamais un serveur de dev qui pointerait vers la vraie base
    command: 'php -S localhost:8001 -t public',
    url: 'http://localhost:8001',
    env: { GROWMEET_DB: 'growmeet_test' },
    reuseExistingServer: false,
    // Le serveur PHP journalise chaque requête sur stderr : on masque pour garder un rapport lisible
    stdout: 'ignore',
    stderr: 'ignore',
    timeout: 10 * 1000,
  },
});
