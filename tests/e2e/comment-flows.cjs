const { chromium } = require('playwright');

(async () => {
  const baseURL = process.env.BASE_URL;
  const postID = process.env.POST_ID;
  if (!baseURL || !postID) throw new Error('BASE_URL and POST_ID are required');
  const browser = await chromium.launch({ headless: true });
  const errors = [];
  const admin = await browser.newContext();
  const page = await admin.newPage();
  page.on('console', msg => { if (msg.type() === 'error') errors.push(msg.text()); });
  await page.goto(`${baseURL}/wp-login.php`, { waitUntil: 'domcontentloaded' });
  await page.locator('#user_login').fill('admin');
  await page.locator('#user_pass').fill('adminpass');
  await Promise.all([page.waitForNavigation(), page.locator('#wp-submit').click()]);

  await page.goto(`${baseURL}/wp-admin/options-general.php?page=wpcomments-settings`, { waitUntil: 'networkidle' });
  await page.getByRole('heading', { name: /WPComments 设置/ }).waitFor();

  await page.goto(`${baseURL}/wp-admin/edit-comments.php`, { waitUntil: 'networkidle' });
  const pin = page.getByRole('link', { name: '置顶', exact: true }).first();
  await pin.waitFor();
  await Promise.all([page.waitForNavigation(), pin.click()]);

  await page.goto(`${baseURL}/?p=${postID}`, { waitUntil: 'networkidle' });
  await page.locator('.wpcomments-sticky-comment').first().waitFor();
  await page.locator('.comment-author-role-badge').first().waitFor();
  await page.locator('input[name="wpcomments_comment_nonce"]').waitFor({ state: 'attached' });
  await page.locator('#wp-comment-private').check();
  await page.locator('#wpcomments_subscribe_to_comment').check();
  await page.locator('#comment').fill('Private E2E comment with enough characters to pass validation.');
  await Promise.all([page.waitForNavigation(), page.locator('#submit').click()]);
  await admin.close();

  const anon = await browser.newContext();
  const reportPage = await anon.newPage();
  reportPage.on('console', msg => { if (msg.type() === 'error') errors.push(msg.text()); });
  reportPage.on('dialog', dialog => dialog.accept());
  await reportPage.goto(`${baseURL}/?p=${postID}`, { waitUntil: 'networkidle' });
  await reportPage.locator('.wpcomments-report-btn').first().click();
  await reportPage.locator('.wpcomments-already-reported').first().waitFor();
  await anon.close();
  await browser.close();
  if (errors.length) throw new Error(`browser console errors: ${errors.join(' | ')}`);
  console.log('PASS: browser settings, sticky, role badge, comment nonce, private subscription, and report flows');
})().catch(error => { console.error(error); process.exit(1); });
