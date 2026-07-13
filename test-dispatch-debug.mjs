import { chromium, expect } from '@playwright/test';

(async () => {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext();
  const page = await context.newPage();

  page.on('console', msg => console.log('BROWSER CONSOLE:', msg.text()));
  page.on('pageerror', err => console.log('BROWSER ERROR:', err.message, err.stack));

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

          // Override runEditorCommands to log what happens!
          const state = basicRoot._x_dataStack ? basicRoot._x_dataStack[0] : Alpine.$data(basicRoot);
          const originalRun = state.runEditorCommands;
          
          state.runEditorCommands = function(detail) {
              console.log("runEditorCommands called with:", JSON.stringify(detail));
              const editor = typeof this.getEditor === 'function' ? this.getEditor() : this.$getEditor();
              console.log("Editor commands:", Object.keys(editor.commands).join(", "));
              console.log("Chain setYoutubeVideo type:", typeof editor.chain().setYoutubeVideo);
              try {
                  originalRun.call(this, detail);
              } catch (e) {
                  console.log("CAUGHT ERROR:", e.message);
              }
          };

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
          
      }, 2000);
  });

  await page.waitForTimeout(4000);
  await browser.close();
})();
