const { test, expect } = require('@playwright/test');

const fixture = '/tests/browser/fixtures/layout-regression.html';

async function gridColumnCount(locator) {
  return locator.evaluate((element) => {
    const value = getComputedStyle(element).gridTemplateColumns.trim();
    return value ? value.split(/\s+/).length : 0;
  });
}

test.describe('Commerce layout browser regression', () => {
  test('rich Mega-menu toggle preserves link navigation and keyboard behavior', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto(fixture);

    const destination = page.getByRole('link', { name: 'Catalog', exact: true });
    const toggle = page.getByRole('button', { name: 'Open Catalog menu' });
    const panel = page.locator('#itk-mega-panel-fixture');

    await expect(destination).toHaveAttribute('href', '#catalog');
    await expect(destination).toHaveAttribute('aria-haspopup', 'true');
    await expect(toggle).toHaveAttribute('aria-controls', 'itk-mega-panel-fixture');
    await expect(toggle).toHaveAttribute('aria-expanded', 'false');

    await toggle.focus();
    await page.keyboard.press('Enter');
    await expect(toggle).toHaveAttribute('aria-expanded', 'true');
    await expect(panel).toBeVisible();

    await page.keyboard.press('Escape');
    await expect(toggle).toHaveAttribute('aria-expanded', 'false');
    await expect(toggle).toBeFocused();
  });

  test('clicking outside closes an open rich Mega-menu', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto(fixture);

    const toggle = page.getByRole('button', { name: 'Open Catalog menu' });
    await toggle.click();
    await expect(toggle).toHaveAttribute('aria-expanded', 'true');

    await page.locator('main').click({ position: { x: 20, y: 20 } });
    await expect(toggle).toHaveAttribute('aria-expanded', 'false');
  });

  test('desktop, tablet and mobile layouts keep bounded responsive grids', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto(fixture);

    const footerGrid = page.locator('[data-footer-grid]');
    const megaGrid = page.locator('.itk-mega-panel__inner');
    const toggle = page.getByRole('button', { name: 'Open Catalog menu' });

    expect(await gridColumnCount(footerGrid)).toBe(4);
    expect(await gridColumnCount(megaGrid)).toBe(4);

    await page.setViewportSize({ width: 820, height: 1000 });
    expect(await gridColumnCount(footerGrid)).toBe(2);
    await toggle.click();
    await expect(page.locator('#itk-mega-panel-fixture')).toBeVisible();
    expect(await gridColumnCount(megaGrid)).toBe(2);

    await page.setViewportSize({ width: 390, height: 844 });
    expect(await gridColumnCount(footerGrid)).toBe(1);
    expect(await gridColumnCount(megaGrid)).toBe(1);
    await expect(page.getByRole('navigation', { name: 'Mobile Bottom Navigation' })).toBeVisible();

    const overflow = await page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
    expect(overflow).toBeLessThanOrEqual(1);
  });

  test('RTL uses logical inline-start positioning for aligned rich panels', async ({ page }) => {
    await page.setViewportSize({ width: 1440, height: 900 });
    await page.goto(fixture);
    await page.evaluate(() => document.documentElement.setAttribute('dir', 'rtl'));

    const item = page.locator('[data-fixture-mega-item]');
    const panel = page.locator('#itk-mega-panel-fixture');
    const toggle = page.getByRole('button', { name: 'Open Catalog menu' });

    await toggle.click();
    await expect(panel).toBeVisible();

    const metrics = await panel.evaluate((element) => {
      const style = getComputedStyle(element);
      return {
        direction: style.direction,
        right: style.right,
        panelRight: element.getBoundingClientRect().right,
        itemRight: element.parentElement.getBoundingClientRect().right,
      };
    });

    expect(metrics.direction).toBe('rtl');
    expect(metrics.right).toBe('0px');
    expect(Math.abs(metrics.panelRight - metrics.itemRight)).toBeLessThanOrEqual(1);
    await expect(item).toHaveClass(/itk-menu-item--mega-rich/);
  });

  test('basic accessibility contracts stay intact across the fixture', async ({ page }) => {
    await page.setViewportSize({ width: 1280, height: 800 });
    await page.goto(fixture);

    await expect(page.getByRole('navigation', { name: 'Primary Navigation' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Skip to content' })).toHaveAttribute('href', '#primary');
    await expect(page.locator('#primary')).toHaveCount(1);

    const duplicateIds = await page.evaluate(() => {
      const seen = new Set();
      const duplicates = [];
      document.querySelectorAll('[id]').forEach((element) => {
        if (seen.has(element.id)) {
          duplicates.push(element.id);
        }
        seen.add(element.id);
      });
      return duplicates;
    });
    expect(duplicateIds).toEqual([]);

    const invalidControls = await page.evaluate(() => {
      return Array.from(document.querySelectorAll('[aria-controls]'))
        .map((element) => element.getAttribute('aria-controls'))
        .filter((id) => !id || !document.getElementById(id));
    });
    expect(invalidControls).toEqual([]);

    const unnamedInteractive = await page.evaluate(() => {
      const elements = Array.from(document.querySelectorAll('button, a[href]'));
      return elements.filter((element) => {
        const label = element.getAttribute('aria-label') || element.textContent || '';
        return !label.trim();
      }).length;
    });
    expect(unnamedInteractive).toBe(0);
  });
});
