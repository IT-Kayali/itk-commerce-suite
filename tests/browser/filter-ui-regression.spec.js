const fs = require('fs');
const path = require('path');
const { test, expect } = require('@playwright/test');

const fixture = '/tests/browser/fixtures/filter-ui-regression.html';
const filteredHtml = fs.readFileSync(
  path.join(process.cwd(), 'tests/browser/fixtures/filter-ui-regression-filtered.html'),
  'utf8'
);

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

  test('filter submit replaces catalog without reload and browser Back restores previous results', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });

    await page.route('**/tests/browser/fixtures/filter-ui-regression.html?**', async (route) => {
      const url = new URL(route.request().url());
      if (url.searchParams.get('filter_category') === 'fragrance,gifts') {
        await route.fulfill({ status: 200, contentType: 'text/html; charset=utf-8', body: filteredHtml });
        return;
      }
      await route.continue();
    });

    await page.goto(fixture);
    await page.evaluate(() => {
      window.__itkFixtureMarker = 'preserved';
      window.__itkCatalogUpdates = 0;
      document.addEventListener('itk:catalog-updated', () => {
        window.__itkCatalogUpdates += 1;
      });
    });

    await page.locator('input[name="filter_category[]"][value="gifts"]').check();
    await page.locator('.itk-filter-form').evaluate((form) => form.requestSubmit());

    await expect(page.locator('[data-product-state="filtered"]')).toBeVisible();
    await expect(page.locator('.woocommerce-result-count')).toContainText('2 results');

    const state = await page.evaluate(() => ({
      marker: window.__itkFixtureMarker,
      updates: window.__itkCatalogUpdates,
      busy: document.querySelector('[data-itk-catalog-results]').getAttribute('aria-busy'),
      status: document.querySelector('[data-itk-catalog-live-status]').textContent,
      href: window.location.href,
    }));

    expect(state.marker).toBe('preserved');
    expect(state.updates).toBe(1);
    expect(state.busy).toBe('false');
    expect(state.status).toBe('Products updated.');

    const filteredUrl = new URL(state.href);
    expect(filteredUrl.searchParams.get('filter_category')).toBe('fragrance,gifts');
    expect(filteredUrl.searchParams.get('filter_price')).toBe('20-150');
    expect(filteredUrl.searchParams.get('filter_rating')).toBe('4');
    expect(filteredUrl.search).not.toContain('%5B%5D');
    expect(filteredUrl.search).not.toContain('%5Bmin%5D');

    await page.evaluate(() => window.history.back());
    await expect(page.locator('[data-product-state="original"]')).toBeVisible();

    const restored = await page.evaluate(() => ({
      marker: window.__itkFixtureMarker,
      updates: window.__itkCatalogUpdates,
      search: window.location.search,
    }));

    expect(restored.marker).toBe('preserved');
    expect(restored.updates).toBe(2);
    expect(restored.search).toBe('');
  });
});
