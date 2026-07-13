import { chromium, expect } from '@playwright/test';

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();

  await context.addInitScript(() => {
      const originalAddEventListener = window.addEventListener;
      window.addEventListener = function(type, listener, options) {
          if (type === 'run-rich-editor-commands') {
              const originalListener = listener;
              listener = function(event) {
                  console.log("INTERCEPTED run-rich-editor-commands:");
                  console.log(JSON.stringify(event.detail));
                  try {
                      originalListener.call(this, event);
                  } catch(e) {
                      console.log("CAUGHT LISTENER ERROR:", e.message);
                  }
              };
          }
          return originalAddEventListener.call(this, type, listener, options);
      };
      console.log("Interceptor installed!");
  });

  const page = await context.newPage();
  page.on('console', msg => console.log('BROWSER CONSOLE:', msg.text()));

  await page.goto('http://127.0.0.1:8000/admin/flex-fields-playground/flex-rich-editor');
  
  await page.getByLabel(/email/i).fill('admin@wyachts.com');
  await page.locator('#password').fill('password');
  await page.getByRole('button', { name: /sign in|log in/i }).click();

  await page.waitForURL(/flex-fields-playground/);

  // Focus and click the Youtube button!
  const basicRoot = page.locator('.fff-rich-editor__root').filter({ has: page.locator('button[title="YouTube"]') }).first();
  await basicRoot.locator('.ProseMirror').click();
  await page.waitForTimeout(500);
  await basicRoot.locator('button[title="YouTube"]').click();
  
  // Wait for the modal and submit
  const modal = page.locator('.fi-modal-window').filter({ hasText: /YouTube/i });
  await modal.waitFor();
  await modal.locator('input[type="url"]').fill('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
  await modal.locator('button', { hasText: /Insert|Submit|Add/i }).click();

  // Wait to see the logs
  await page.waitForTimeout(3000);

  await browser.close();
})();
