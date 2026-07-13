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

  const editor = page.locator('.fff-rich-editor .fff-rich-editor__root').first();
  const proseMirror = editor.locator('.ProseMirror');

  await proseMirror.waitFor();

  const alpineKeys = await editor.evaluate((node) => {
      const alpineState = Alpine.$data(node);
      const keys = Object.keys(alpineState);
      const hasEditorInstance = !!alpineState.editorInstance;
      const typeofEditor = typeof alpineState.editor;
      return { keys, hasEditorInstance, typeofEditor };
  });

  console.log("ALPINE DATA:", alpineKeys);

  await browser.close();
})();
