import { chromium, expect } from '@playwright/test';

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  page.on('console', msg => console.log('BROWSER CONSOLE:', msg.text()));
  page.on('pageerror', err => console.log('BROWSER ERROR:', err.message));

  await page.goto('http://127.0.0.1:8000/admin/flex-fields-playground/flex-rich-editor');
  
  await page.getByLabel(/email/i).fill('admin@wyachts.com');
  await page.locator('#password').fill('password');
  await page.getByRole('button', { name: /sign in|log in/i }).click();

  await page.waitForURL(/flex-fields-playground/);

  await page.evaluate(() => {
      setTimeout(() => {
          const roots = document.querySelectorAll('.fff-rich-editor__root');
          const basicRoot = Array.from(roots).find(r => r.getAttribute('wire:key').includes('flex_rich_editor__basic'));
          
          const xData = basicRoot.getAttribute('x-data');
          const livewireId = xData.match(/livewireId:\s*['"]([^'"]+)['"]/)[1];
          const key = xData.match(/key:\s*['"]([^'"]+)['"]/)[1];

          console.log("Extracted livewireId:", livewireId);
          console.log("Extracted key:", key);

          console.log("Dispatching run-rich-editor-commands...");
          window.dispatchEvent(new CustomEvent('run-rich-editor-commands', {
              detail: {
                  livewireId: livewireId,
                  key: key,
                  commands: [
                      {
                          name: 'setYoutubeVideo',
                          arguments: [{ src: 'https://youtube.com/watch?v=dQw4w9WgXcQ' }]
                      }
                  ],
                  editorSelection: { type: 'text', anchor: 1, head: 1 }
              }
          }));
          
          console.log("Dispatched!");
      }, 2000);
  });

  await page.waitForTimeout(4000);
  await browser.close();
})();
