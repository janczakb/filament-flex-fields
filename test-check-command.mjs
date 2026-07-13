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

  const commandsInfo = await editor.evaluate((node) => {
      // Filament 3 stores alpine state in __x
      const alpineState = node.__x.$data;
      if (!alpineState || !alpineState.editorInstance) {
          return "NO EDITOR INSTANCE";
      }
      
      const commands = Object.keys(alpineState.editorInstance.commands);
      
      return {
          hasYoutube: commands.includes('setYoutubeVideo'),
          allCommands: commands,
          extensions: alpineState.editorInstance.extensionManager.extensions.map(e => e.name)
      };
  });

  console.log("COMMANDS INFO:", JSON.stringify(commandsInfo, null, 2));

  await browser.close();
})();
