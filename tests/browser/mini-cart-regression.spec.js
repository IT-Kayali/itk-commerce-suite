const { test, expect } = require('@playwright/test');

const fixture = '/tests/browser/fixtures/mini-cart-regression.html';

test.describe('Commerce mini-cart drawer', () => {
  test('opens progressively, traps focus and restores the cart trigger on Escape', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto(fixture);

    const trigger = page.locator('.itk-header-action--cart');
    const drawer = page.locator('[data-itk-mini-cart]');
    const panel = page.locator('[data-itk-mini-cart-panel]');
    const close = page.locator('[data-itk-mini-cart-close]');

    await trigger.focus();
    await trigger.click();

    await expect(drawer).toHaveClass(/is-open/);
    await expect(drawer).toHaveAttribute('aria-hidden', 'false');
    await expect(trigger).toHaveAttribute('aria-expanded', 'true');
    await expect(page.locator('body')).toHaveClass(/itk-mini-cart-open/);
    await expect(close).toBeFocused();
    await expect(panel).toBeVisible();

    await page.keyboard.press('Escape');

    await expect(drawer).not.toHaveClass(/is-open/);
    await expect(drawer).toHaveAttribute('aria-hidden', 'true');
    await expect(trigger).toHaveAttribute('aria-expanded', 'false');
    await expect(trigger).toBeFocused();
  });

  test('backdrop close works and mobile drawer remains bounded without horizontal overflow', async ({ page }) => {
    await page.setViewportSize({ width: 390, height: 844 });
    await page.goto(fixture);

    const trigger = page.locator('.itk-header-action--cart');
    const drawer = page.locator('[data-itk-mini-cart]');
    const panel = page.locator('[data-itk-mini-cart-panel]');

    await trigger.click();
    await expect(drawer).toHaveClass(/is-open/);

    const panelBox = await panel.boundingBox();
    expect(panelBox.width).toBeLessThanOrEqual(390);
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(1);

    await page.locator('[data-itk-mini-cart-backdrop]').click({ position: { x: 5, y: 5 } });
    await expect(drawer).not.toHaveClass(/is-open/);
  });

  test('logical end position follows RTL direction', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 900 });
    await page.goto(fixture);
    await page.evaluate(() => document.documentElement.setAttribute('dir', 'rtl'));

    await page.locator('.itk-header-action--cart').click();
    const box = await page.locator('[data-itk-mini-cart-panel]').boundingBox();

    expect(box.x).toBeLessThanOrEqual(1);
    expect(box.width).toBeGreaterThan(300);
  });
});
