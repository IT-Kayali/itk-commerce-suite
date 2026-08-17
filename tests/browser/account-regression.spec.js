const { test, expect } = require('@playwright/test');

const fixture = '/tests/browser/fixtures/account-regression.html';

async function gridColumnCount(locator) {
  return locator.evaluate((element) => {
    const value = getComputedStyle(element).gridTemplateColumns.trim();
    return value && value !== 'none' ? value.split(/\s+/).length : 0;
  });
}

async function setAccountModel(page, model) {
  await page.evaluate((modelName) => {
    Array.from(document.body.classList).forEach((className) => {
      if (className.indexOf('itk-account-model-') === 0) {
        document.body.classList.remove(className);
      }
    });
    document.body.classList.add('itk-account-model-' + modelName);
  }, model);
}

test.describe('WooCommerce My Account presentation', () => {
  test('sidebar model keeps navigation and account content in a bounded desktop layout', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto(fixture);

    const shell = page.locator('[data-account-shell]');
    const nav = page.locator('[data-account-nav]');
    const cards = page.locator('[data-account-cards]');
    const addresses = page.locator('[data-account-addresses]');

    expect(await gridColumnCount(shell)).toBe(2);
    expect(await nav.evaluate((element) => getComputedStyle(element).position)).toBe('sticky');
    expect(await gridColumnCount(cards)).toBe(2);
    expect(await gridColumnCount(addresses)).toBe(2);
  });

  test('top navigation model becomes a single-column account layout', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto(fixture);
    await setAccountModel(page, 'top-nav');

    const shell = page.locator('[data-account-shell]');
    const navList = page.locator('[data-account-nav] ul');

    expect(await gridColumnCount(shell)).toBe(1);
    expect(await navList.evaluate((element) => getComputedStyle(element).display)).toBe('flex');
  });

  test('mobile layout collapses cards and addresses without horizontal overflow', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(fixture);

    const shell = page.locator('[data-account-shell]');
    const cards = page.locator('[data-account-cards]');
    const addresses = page.locator('[data-account-addresses]');

    expect(await gridColumnCount(shell)).toBe(1);
    expect(await gridColumnCount(cards)).toBe(1);
    expect(await gridColumnCount(addresses)).toBe(1);

    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(1);
  });

  test('RTL keeps the account experience bounded and usable', async ({ page }) => {
    await page.setViewportSize({ width: 1024, height: 900 });
    await page.goto(fixture);
    await page.evaluate(() => document.documentElement.setAttribute('dir', 'rtl'));

    const shell = page.locator('[data-account-shell]');
    expect(await gridColumnCount(shell)).toBe(2);

    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(1);
  });
});
