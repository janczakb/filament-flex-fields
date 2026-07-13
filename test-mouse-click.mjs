import { chromium } from 'playwright';

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
  await page.keyboard.type('Hello');
  
  // Select all using keyboard
  await page.keyboard.press('ControlOrMeta+a');

  const boldButton = editor.locator('button[title="Bold"], button[aria-label="Bold"], button[aria-label="bold"]').first();
  const btnBox = await boldButton.boundingBox();
  
  // Real mouse mousedown and mouseup
  await page.mouse.move(btnBox.x + btnBox.width / 2, btnBox.y + btnBox.height / 2);
  await page.mouse.down();
  await page.mouse.up();

  // Check if bold is applied
  const html = await proseMirror.innerHTML();
  console.log("ProseMirror HTML after mouse click on bold:", html);

  await browser.close();
})();
