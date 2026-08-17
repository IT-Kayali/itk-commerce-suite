const { test, expect } = require('@playwright/test');

const fixture = '/tests/browser/fixtures/commerce-template-regression.html';

async function setArea(page, area, classes) {
  await page.evaluate(({ area, classes }) => {
    document.body.className = ['woocommerce-page', 'itk-commerce-area-' + area].concat(classes).join(' ');
  }, { area, classes });
}

async function gridColumnCount(locator) {
  return locator.evaluate((element) => {
    const value = getComputedStyle(element).gridTemplateColumns.trim();
    return value && value !== 'none' ? value.split(/\s+/).length : 0;
  });
}

test.describe('Commerce page template models', () => {
  test('Shop model supports column choices, sidebar direction and responsive collapse', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto(fixture);
    await setArea(page, 'shop', ['itk-shop-model-sidebar', 'itk-commerce-columns-5', 'itk-shop-density-comfortable', 'itk-shop-sidebar-right']);

    const grid = page.locator('[data-shop-grid]');
    const shell = page.locator('[data-shop-shell]');
    const sidebar = page.locator('[data-shop-sidebar]');
    const content = page.locator('[data-shop-content]');

    expect(await gridColumnCount(grid)).toBe(5);
    expect(await gridColumnCount(shell)).toBe(2);
    expect(await sidebar.evaluate((element) => getComputedStyle(element).order)).toBe('2');
    expect(await content.evaluate((element) => getComputedStyle(element).order)).toBe('1');

    await page.setViewportSize({ width: 820, height: 1000 });
    expect(await gridColumnCount(grid)).toBe(2);
    expect(await gridColumnCount(shell)).toBe(1);

    await page.setViewportSize({ width: 390, height: 844 });
    expect(await gridColumnCount(grid)).toBe(1);
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(1);
  });

  test('Product gallery-right model reverses desktop order and collapses safely on tablet', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto(fixture);
    await setArea(page, 'product', ['itk-product-model-gallery-right', 'itk-product-gallery-60', 'itk-product-sticky-summary', 'itk-product-tabs-tabs']);

    const layout = page.locator('[data-product-layout]');
    const gallery = page.locator('[data-product-gallery]');
    const summary = page.locator('[data-product-summary]');

    expect(await gridColumnCount(layout)).toBe(2);
    expect(await summary.evaluate((element) => getComputedStyle(element).order)).toBe('1');
    expect(await gallery.evaluate((element) => getComputedStyle(element).order)).toBe('2');
    expect(await summary.evaluate((element) => getComputedStyle(element).position)).toBe('sticky');

    await page.setViewportSize({ width: 820, height: 1000 });
    expect(await gridColumnCount(layout)).toBe(1);
    expect(await summary.evaluate((element) => getComputedStyle(element).position)).toBe('static');
  });

  test('Classic Cart split model collapses while Cart block uses only the public outer shell', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto(fixture);
    await setArea(page, 'cart', ['itk-cart-model-split', 'itk-cart-density-comfortable', 'itk-cart-sticky-totals']);

    const classic = page.locator('[data-cart-classic]');
    const totals = page.locator('[data-cart-totals]');
    const blockShell = page.locator('[data-cart-block-shell]');

    expect(await gridColumnCount(classic)).toBe(2);
    expect(await totals.evaluate((element) => getComputedStyle(element).position)).toBe('sticky');
    expect(await blockShell.evaluate((element) => getComputedStyle(element).maxWidth)).toBe('980px');

    await page.setViewportSize({ width: 820, height: 1000 });
    expect(await gridColumnCount(classic)).toBe(1);
    expect(await totals.evaluate((element) => getComputedStyle(element).position)).toBe('static');
    expect(await blockShell.evaluate((element) => getComputedStyle(element).maxWidth)).toBe('100%');
  });

  test('Classic Checkout split model collapses and focused Checkout block shell remains bounded', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto(fixture);
    await setArea(page, 'checkout', ['itk-checkout-model-split', 'itk-checkout-width-wide', 'itk-checkout-fields-comfortable', 'itk-checkout-sticky-summary']);

    const checkout = page.locator('[data-checkout-classic]');
    const review = page.locator('[data-checkout-review]');
    const blockShell = page.locator('[data-checkout-block-shell]');

    expect(await gridColumnCount(checkout)).toBe(2);
    expect(await review.evaluate((element) => getComputedStyle(element).position)).toBe('sticky');
    const wideMaxWidth = await blockShell.evaluate((element) => parseFloat(getComputedStyle(element).maxWidth));
    expect(wideMaxWidth).toBeGreaterThanOrEqual(1200);

    await setArea(page, 'checkout', ['itk-checkout-model-focused', 'itk-checkout-width-boxed', 'itk-checkout-fields-compact']);
    expect(await blockShell.evaluate((element) => getComputedStyle(element).maxWidth)).toBe('920px');

    await page.setViewportSize({ width: 820, height: 1000 });
    expect(await checkout.evaluate((element) => getComputedStyle(element).display)).toBe('block');
    expect(await blockShell.evaluate((element) => getComputedStyle(element).maxWidth)).toBe('100%');
  });
});
