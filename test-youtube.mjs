import { chromium, expect } from '@playwright/test';

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  page.on('console', msg => console.log('BROWSER CONSOLE:', msg.text()));

  await page.goto('http://127.0.0.1:8000/admin/flex-fields-playground/flex-rich-editor');
  
  // Login
  await page.getByLabel(/email/i).fill('admin@wyachts.com');
  await page.locator('#password').fill('password');
  await page.getByRole('button', { name: /sign in|log in/i }).click();

  await page.waitForURL(/flex-fields-playground/);

  const editor = page.locator('.fff-rich-editor').first();
  const proseMirror = editor.locator('.ProseMirror');

  await proseMirror.waitFor();
  await proseMirror.click();

  console.log("Clicking YouTube button...");
  const youtubeBtn = editor.locator('button[title*="YouTube"], button[aria-label*="YouTube"], button[aria-label*="youtube"]').first();
  await youtubeBtn.click();
  
  try {
      console.log("Waiting for modal...");
      const urlInput = page.locator('.fi-modal-open input[wire\\:model]').first();
      await urlInput.waitFor({ state: 'visible', timeout: 5000 });
      console.log("Modal opened! Filling URL...");
      await urlInput.fill('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
      
      const submitBtn = page.locator('.fi-modal-open button[type="submit"]').first();
      await submitBtn.click();
      
      await page.waitForTimeout(2000);
      
      const html = await proseMirror.innerHTML();
      console.log("Editor HTML:", html);
  } catch (e) {
      console.error("Test failed:", e);
  }

  await browser.close();
})();
