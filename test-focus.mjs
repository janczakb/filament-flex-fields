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
  
  // Inject script to log focus/blur on ProseMirror
  await proseMirror.evaluate(node => {
      node.addEventListener('blur', () => console.log('ProseMirror BLURRED!'));
      node.addEventListener('focus', () => console.log('ProseMirror FOCUSED!'));
  });

  await proseMirror.click();
  await page.keyboard.type('Hello');
  
  await page.keyboard.press('ControlOrMeta+a');

  const boldButton = editor.locator('button[title="Bold"], button[aria-label="Bold"], button[aria-label="bold"]').first();
  const btnBox = await boldButton.boundingBox();
  
  console.log("Mouse down on bold button...");
  await page.mouse.move(btnBox.x + btnBox.width / 2, btnBox.y + btnBox.height / 2);
  await page.mouse.down();
  
  // Wait to see if blur happened
  await page.waitForTimeout(500);
  
  console.log("Mouse up on bold button...");
  await page.mouse.up();
  
  // Check if bold is applied
  const html = await proseMirror.innerHTML();
  console.log("ProseMirror HTML:", html);

  await browser.close();
})();
