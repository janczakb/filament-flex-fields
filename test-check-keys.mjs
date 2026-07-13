import { chromium, expect } from '@playwright/test';

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  page.on('console', msg => console.log('BROWSER CONSOLE:', msg.text()));

  await page.goto('http://127.0.0.1:8000/admin/flex-fields-playground/flex-rich-editor');
  
  await page.getByLabel(/email/i).fill('admin@wyachts.com');
  await page.locator('#password').fill('password');
  await page.getByRole('button', { name: /sign in|log in/i }).click();

  await page.waitForURL(/flex-fields-playground/);

  await page.evaluate(() => {
      const roots = document.querySelectorAll('.fff-rich-editor__root, .fi-fo-rich-editor');
      console.log("Total rich editors on page:", roots.length);
      roots.forEach(root => {
          const xData = root.getAttribute('x-data');
          if (!xData) return;
          const keyMatch = xData.match(/key:\s*['"]([^'"]+)['"]/);
          console.log("Component key:", keyMatch ? keyMatch[1] : 'NOT FOUND');
      });
  });

  await browser.close();
})();
