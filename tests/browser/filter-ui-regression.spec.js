const { test, expect } = require('@playwright/test');

const fixture = '/tests/browser/fixtures/filter-ui-regression.html';

async function gridColumnCount(locator) {
  return locator.evaluate((element) => {
    const value = getComputedStyle(element).gridTemplateColumns.trim();
    return value && value !== 'none' ? value.split(/\s+/).length : 0;
  });
}

test.describe('Search Filter progressive UI', () => {
  test('desktop filter panel uses bounded two-column groups and active chips', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto(fixture);

    const panel = page.locator('[data-filter-panel]');
    const groups = page.locator('[data-filter-groups]');
    const chips = page.locator('[data-active-filters] .itk-active-filter');

    await expect(panel).toBeVisible();
    expect(await gridColumnCount(groups)).toBe(2);
    expect(await chips.count()).toBe(4);

    const box = await panel.boundingBox();
    expect(box.width).toBeLessThanOrEqual(760);
  });

  test('native details interaction preserves collapsed filter groups without JavaScript', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto(fixture);

    const stock = page.locator('.itk-filter-group--stock');
    await expect(stock).not.toHaveAttribute('open', '');
    await stock.locator('summary').click();
    await expect(stock).toHaveAttribute('open', '');
    await expect(stock.locator('.itk-filter-select')).toBeVisible();
  });

  test('mobile filter UI collapses to one column without horizontal overflow', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(fixture);

    expect(await gridColumnCount(page.locator('[data-filter-groups]'))).toBe(1);
    const panelBox = await page.locator('[data-filter-panel]').boundingBox();
    expect(panelBox.width).toBeLessThanOrEqual(350);

    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(1);
  });

  test('RTL keeps toolbar, filter panel and chips bounded', async ({ page }) => {
    await page.setViewportSize({ width: 1024, height: 900 });
    await page.goto(fixture);
    await page.evaluate(() => document.documentElement.setAttribute('dir', 'rtl'));

    await expect(page.locator('[data-filter-panel]')).toBeVisible();
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(1);
  });
});
