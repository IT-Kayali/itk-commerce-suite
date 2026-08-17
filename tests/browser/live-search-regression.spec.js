const { test, expect } = require('@playwright/test');

const fixture = '/tests/browser/fixtures/live-search-regression.html';

function product(id, name, sku, slug, price = '1299') {
  return {
    id,
    name,
    sku,
    permalink: `http://127.0.0.1:4173/product/${slug}/`,
    images: [],
    categories: [{ id: 1, name: 'Fragrance', slug: 'fragrance' }],
    prices: {
      price,
      regular_price: price,
      sale_price: price,
      currency_code: 'EUR',
      currency_minor_unit: 2,
    },
  };
}

function category(id, name, slug, count = 7) {
  return {
    id,
    name,
    slug,
    count,
    permalink: `http://127.0.0.1:4173/product-category/${slug}/`,
    image: null,
  };
}

async function fulfillJson(route, payload) {
  await route.fulfill({
    status: 200,
    contentType: 'application/json; charset=utf-8',
    body: JSON.stringify(payload),
  });
}

test.describe('WooCommerce Store API live search', () => {
  test('shows product/category suggestions and supports combobox keyboard navigation + cache', async ({ page }) => {
    let productRequests = 0;
    let categoryRequests = 0;

    await page.route('**/wp-json/wc/store/v1/products?**', async (route) => {
      productRequests += 1;
      const url = new URL(route.request().url());
      if (url.searchParams.get('search') === 'rose') {
        await fulfillJson(route, [product(101, 'Rose Perfume', 'ROSE-100', 'rose-perfume')]);
        return;
      }
      await fulfillJson(route, []);
    });

    await page.route('**/wp-json/wc/store/v1/products/categories?**', async (route) => {
      categoryRequests += 1;
      const url = new URL(route.request().url());
      if (url.searchParams.get('search') === 'rose') {
        await fulfillJson(route, [category(51, 'Rose Gifts', 'rose-gifts')]);
        return;
      }
      await fulfillJson(route, []);
    });

    await page.goto(fixture);

    const input = page.locator('.itk-live-search__input');
    const panel = page.locator('[data-itk-live-search-panel]');
    const options = page.locator('[data-itk-live-search-option]');

    await input.fill('rose');
    await expect(panel).toBeVisible();
    await expect(page.locator('.itk-live-search__option--product')).toContainText('Rose Perfume');
    await expect(page.locator('.itk-live-search__option--category')).toContainText('Rose Gifts');
    await expect(input).toHaveAttribute('aria-expanded', 'true');
    expect(await options.count()).toBe(3); // product + category + all-results.

    await input.press('ArrowDown');
    const activeId = await input.getAttribute('aria-activedescendant');
    expect(activeId).toBeTruthy();
    await expect(page.locator(`#${activeId}`)).toHaveAttribute('aria-selected', 'true');

    await input.press('Escape');
    await expect(panel).toBeHidden();
    await expect(input).toHaveAttribute('aria-expanded', 'false');

    const firstCounts = { productRequests, categoryRequests };
    await input.fill('');
    await input.fill('rose');
    await expect(panel).toBeVisible();
    await expect(page.locator('.itk-live-search__option--product')).toContainText('Rose Perfume');

    expect(productRequests).toBe(firstCounts.productRequests);
    expect(categoryRequests).toBe(firstCounts.categoryRequests);
  });

  test('exact SKU scope is merged ahead of ordinary name results', async ({ page }) => {
    await page.route('**/wp-json/wc/store/v1/products?**', async (route) => {
      const url = new URL(route.request().url());
      if (url.searchParams.get('sku') === 'SKU-100') {
        await fulfillJson(route, [product(202, 'Exact SKU Product', 'SKU-100', 'exact-sku-product', '990')]);
        return;
      }
      if (url.searchParams.get('search') === 'SKU-100') {
        await fulfillJson(route, [product(203, 'Name Search Product', 'OTHER-1', 'name-search-product')]);
        return;
      }
      await fulfillJson(route, []);
    });

    await page.route('**/wp-json/wc/store/v1/products/categories?**', async (route) => {
      await fulfillJson(route, []);
    });

    await page.goto(fixture);
    await page.locator('.itk-live-search__input').fill('SKU-100');

    const products = page.locator('.itk-live-search__option--product');
    await expect(products).toHaveCount(2);
    await expect(products.first()).toContainText('Exact SKU Product');
    await expect(products.first()).toContainText('SKU: SKU-100');
    await expect(products.nth(1)).toContainText('Name Search Product');
  });

  test('mobile result panel remains inside the viewport without horizontal overflow', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });

    await page.route('**/wp-json/wc/store/v1/products?**', async (route) => {
      const url = new URL(route.request().url());
      if (url.searchParams.get('search') === 'rose') {
        await fulfillJson(route, [product(101, 'Rose Perfume', 'ROSE-100', 'rose-perfume')]);
        return;
      }
      await fulfillJson(route, []);
    });
    await page.route('**/wp-json/wc/store/v1/products/categories?**', async (route) => {
      await fulfillJson(route, [category(51, 'Rose Gifts', 'rose-gifts')]);
    });

    await page.goto(fixture);
    await page.locator('.itk-live-search__input').fill('rose');

    const panel = page.locator('[data-itk-live-search-panel]');
    await expect(panel).toBeVisible();
    const box = await panel.boundingBox();
    expect(box.x).toBeGreaterThanOrEqual(0);
    expect(box.x + box.width).toBeLessThanOrEqual(390);

    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(1);
  });
});
