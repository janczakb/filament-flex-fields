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
                  // We intercept it, but BEFORE we call the original listener,
                  // we find the basicRoot component and override its runEditorCommands!
                  const basicRoot = document.querySelectorAll('.fff-rich-editor__root')[0];
                  if (basicRoot) {
                      const state = basicRoot._x_dataStack ? basicRoot._x_dataStack[0] : Alpine.$data(basicRoot);
                      state.runEditorCommands = function({ commands, editorSelection }) {
                          console.log("MY OVERRIDDEN runEditorCommands CALLED!");
                          this.setEditorSelection(editorSelection);
                          const editor = typeof this.getEditor === 'function' ? this.getEditor() : this.$getEditor();
                          commands.forEach((command) => {
                              let commandChain = editor.chain().focus();
                              commandChain[command.name](...(command.arguments ?? []));
                              commandChain.run();
                          });
                      };
                  }
                  
                  try {
                      originalListener.call(this, event);
                  } catch(e) {
                      console.log("CAUGHT LISTENER ERROR:", e.message, e.stack);
                  }
              };
          }
          return originalAddEventListener.call(this, type, listener, options);
      };
  });

  const page = await context.newPage();
  page.on('console', msg => console.log('BROWSER CONSOLE:', msg.text()));

  await page.goto('http://127.0.0.1:8000/admin/flex-fields-playground/flex-rich-editor');
  
  await page.getByLabel(/email/i).fill('admin@wyachts.com');
  await page.locator('#password').fill('password');
  await page.getByRole('button', { name: /sign in|log in/i }).click();

  await page.waitForURL(/flex-fields-playground/);

  // Focus and click the Youtube button using CSS
  const basicRoot = page.locator('.fff-rich-editor__root').filter({ has: page.locator('button[title="YouTube"]') }).first();
  await basicRoot.locator('.ProseMirror').click();
  await page.waitForTimeout(500);
  
  // Click YouTube button
  await basicRoot.locator('button[title="YouTube"]').click();
  
  // Fill the URL and click Submit
  await page.locator('.fi-modal-window input[type="url"]').fill('https://www.youtube.com/watch?v=dQw4w9WgXcQ');
  await page.locator('.fi-modal-window button[type="submit"]').click();

  await page.waitForTimeout(3000);

  await browser.close();
})();
