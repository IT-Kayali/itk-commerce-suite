const { test, expect } = require('@playwright/test');

const fixture = '/tests/browser/fixtures/integration-contract-regression.html';

test.describe('Public commerce integration slots', () => {
  test('catalog search/filter slots remain bounded across desktop and mobile', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto(fixture);

    const search = page.locator('[data-itk-catalog-slot="search"]');
    const filters = page.locator('[data-itk-catalog-slot="filters"]');
    const searchBox = await search.boundingBox();
    const filterBox = await filters.boundingBox();

    expect(searchBox.x).toBeLessThan(filterBox.x);
    await expect(page.getByRole('searchbox', { name: 'Search products' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Filters' })).toBeVisible();

    await page.setViewportSize({ width: 390, height: 844 });
    const mobileSearchBox = await search.boundingBox();
    const mobileFilterBox = await filters.boundingBox();
    expect(mobileSearchBox.width).toBeLessThanOrEqual(358);
    expect(mobileFilterBox.width).toBeLessThanOrEqual(358);
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(1);
  });

  test('logical catalog slot layout follows RTL without changing semantic order', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto(fixture);
    await page.evaluate(() => document.documentElement.setAttribute('dir', 'rtl'));

    const searchBox = await page.locator('[data-itk-catalog-slot="search"]').boundingBox();
    const filterBox = await page.locator('[data-itk-catalog-slot="filters"]').boundingBox();
    expect(searchBox.x).toBeGreaterThan(filterBox.x);

    const actions = page.locator('[data-itk-component-action]');
    await expect(actions).toHaveCount(3);
    await expect(page.getByRole('button', { name: 'Quick view' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Wishlist' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Compare' })).toBeVisible();
  });
});
