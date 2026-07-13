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
      setTimeout(() => {
          document.querySelectorAll('.fff-rich-editor__root').forEach(root => {
              const state = root._x_dataStack ? root._x_dataStack[0] : Alpine.$data(root);
              const editor = state.editorInstance;
              if (!editor) {
                  console.log("No editor for", root.getAttribute('wire:key'));
                  return;
              }
              const hasCmd = 'setYoutubeVideo' in editor.commands;
              const exts = editor.extensionManager.extensions.map(e => e.name).join(", ");
              console.log("Editor:", root.getAttribute('wire:key'), "HasCmd:", hasCmd, "Exts:", exts);
          });
      }, 2000);
  });

  await page.waitForTimeout(4000);
  await browser.close();
})();
