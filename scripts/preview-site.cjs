const fs = require("node:fs");
const path = require("node:path");
const { chromium } = require("playwright");
const base = process.env.WP_BASE_URL || "http://127.0.0.1:8080";
(async () => {
  const folder = path.resolve(__dirname, "../docs/previews");
  fs.mkdirSync(folder, { recursive: true });
  const browser = await chromium.launch({
    executablePath:
      process.env.CHROMIUM_PATH ||
      (fs.existsSync("/usr/bin/chromium") ? "/usr/bin/chromium" : undefined),
    headless: true,
    args: ["--no-sandbox", "--disable-dev-shm-usage"],
  });
  try {
    for (const device of [
      { name: "desktop", width: 1440, height: 960 },
      { name: "mobile", width: 390, height: 844 },
    ]) {
      const context = await browser.newContext({
        viewport: { width: device.width, height: device.height },
        reducedMotion: "reduce",
      });
      const page = await context.newPage();
      for (const [name, route] of [
        ["home", "/"],
        ["about", "/about/"],
        ["projects", "/projects/"],
        ["writing", "/writing/"],
        ["case", "/projects/when-ai-sets-the-price/"],
      ]) {
        const response = await page.goto(base + route, {
          waitUntil: "networkidle",
        });
        if (response.status() !== 200)
          throw new Error(`${route}: HTTP ${response.status()}`);
        await page.evaluate(() => document.fonts.ready);
        if (name === "about") {
          await page.locator(".globe-wrap").scrollIntoViewIfNeeded();
          await page.waitForSelector(".globe-wrap.is-rendered");
          await page.evaluate(() => window.scrollTo(0, 0));
        }
        await page.screenshot({
          path: path.join(folder, `${name}-${device.name}.png`),
          fullPage: true,
        });
        if (name === "home")
          await page.screenshot({
            path: path.join(folder, `hero-${device.name}.png`),
          });
        if (name === "home" && device.name === "desktop") {
          await page.setViewportSize({ width: 1200, height: 900 });
          await page.screenshot({
            path: path.resolve(
              __dirname,
              "../wp-content/themes/adi-personal/screenshot.png",
            ),
          });
          await page.setViewportSize({
            width: device.width,
            height: device.height,
          });
        }
        console.log(`${name}-${device.name}: captured`);
      }
      await context.close();
    }
  } finally {
    await browser.close();
  }
})().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});
