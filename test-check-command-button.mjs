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
          const roots = document.querySelectorAll('.fff-rich-editor__root');
          const basicRoot = Array.from(roots).find(r => r.getAttribute('wire:key').includes('flex_rich_editor__basic'));
          if (basicRoot) {
              const state = basicRoot._x_dataStack ? basicRoot._x_dataStack[0] : Alpine.$data(basicRoot);
              const editor = typeof state.getEditor === 'function' ? state.getEditor() : (typeof state.$getEditor === 'function' ? state.$getEditor() : null);
              if (editor) {
                  const cmds = Object.keys(editor.commands);
                  console.log("Commands length:", cmds.length);
                  console.log("Has setYoutubeVideo:", cmds.includes('setYoutubeVideo'));
                  console.log("Has chain.setYoutubeVideo:", typeof editor.chain().setYoutubeVideo === 'function');
                  const exts = editor.extensionManager.extensions.map(e => e.name);
                  console.log("Extensions:", exts.join(', '));
              } else {
                  console.log("Editor not found via getEditor()");
              }
          }
      }, 2000);
  });

  await page.waitForTimeout(4000);
  await browser.close();
})();
