/* Local integration checks against real WordPress, with temporary content cleaned up. */
const assert = require("node:assert/strict");
const fs = require("node:fs");
const path = require("node:path");
const { execFileSync } = require("node:child_process");
const { chromium } = require("playwright");
const AxeBuilder = require("@axe-core/playwright").default;
const base = process.env.WP_BASE_URL || "http://127.0.0.1:8080";
assert(
  ["127.0.0.1", "localhost"].includes(new URL(base).hostname),
  "These checks create temporary content and must run against local WordPress.",
);
const php = (code) =>
  execFileSync("docker", ["compose", "exec", "-T", "wordpress", "php"], {
    cwd: path.resolve(__dirname, ".."),
    input: '<?php require "/var/www/html/wp-load.php"; ' + code,
    encoding: "utf8",
  });
const browserPath =
  process.env.CHROMIUM_PATH ||
  (fs.existsSync("/usr/bin/chromium") ? "/usr/bin/chromium" : undefined);
const tags = ["wcag2a", "wcag2aa", "wcag21a", "wcag21aa"];
let browser, fixtures;
const errors = [];
async function load(page, route, status = 200) {
  const response = await page.goto(base + route, { waitUntil: "networkidle" });
  assert.equal(response.status(), status, route);
  await page.evaluate(() => document.fonts.ready);
  assert.equal(
    await page.locator("h1").count(),
    1,
    "Each page has one main heading",
  );
  assert.equal(
    await page.evaluate(
      () => document.documentElement.scrollWidth > innerWidth,
    ),
    false,
    `${route} has no horizontal overflow`,
  );
}
async function accessibility(page, label) {
  const result = await new AxeBuilder({ page }).withTags(tags).analyze();
  assert.equal(
    result.violations.length,
    0,
    label +
      ": " +
      JSON.stringify(
        result.violations.map((v) => ({
          id: v.id,
          nodes: v.nodes.map((n) => n.target),
        })),
      ),
  );
}
(async () => {
  assert.equal(
    Number(php("echo adi_content_import();").trim()),
    0,
    "Importer is idempotent",
  );
  fixtures = JSON.parse(
    php(`
    $category = get_term_by('name', 'AI & Business', 'category');
    $post = wp_insert_post(array('post_type'=>'post','post_status'=>'publish','post_title'=>'Verification: a complete article','post_name'=>'adi-verification-article','post_excerpt'=>'A short card summary.','post_content'=>'<h2>A useful question</h2><p>A complete article body visible to readers.</p>','post_category'=>array($category->term_id)));
    $project = wp_insert_post(array('post_type'=>'adi_project','post_status'=>'publish','post_title'=>'Verification: a deliberately long project title about AI, consumers, business decisions, and useful evidence','post_name'=>'adi-verification-project','post_excerpt'=>'Temporary layout verification.','post_content'=>'<h2>The approach</h2><p>A temporary project body.</p>','menu_order'=>100));
    wp_set_object_terms($project, 'design-ai', 'adi_space');
    update_post_meta($project, 'adi_role', 'Temporary reviewer');
    update_post_meta($project, 'adi_year', '2026');
    echo json_encode(array('post'=>$post,'project'=>$project,'post_url'=>get_permalink($post),'project_url'=>get_permalink($project),'category_url'=>get_term_link($category)));
  `),
  );
  browser = await chromium.launch({
    executablePath: browserPath,
    headless: true,
    args: ["--no-sandbox", "--disable-dev-shm-usage"],
  });
  const context = await browser.newContext({
    viewport: { width: 1440, height: 960 },
    reducedMotion: "reduce",
  });
  const page = await context.newPage();
  page.setDefaultTimeout(10000);
  page.on("pageerror", (error) => errors.push(error.message));
  page.on("requestfailed", (request) => {
    if (request.url().startsWith(base))
      errors.push(request.url() + ": " + request.failure().errorText);
  });
  await load(page, "/");
  assert.equal(await page.locator(".project-card").count(), 6);
  assert.equal(await page.locator(".project-card:visible").count(), 6);
  for (const [name, count] of [
    ["Research", 2],
    ["Design & AI", 2],
    ["Startups & business", 2],
    ["All work", 6],
  ]) {
    await page
      .locator(".filter-bar")
      .getByRole("link", { name, exact: true })
      .click();
    assert.equal(
      await page.locator(".project-card:visible").count(),
      count,
      name,
    );
  }
  await page.locator("#price-range").focus();
  await page.keyboard.press("ArrowRight");
  assert.equal(await page.locator("#ai-price").textContent(), "€21");
  await page.getByRole("button", { name: "Wake up the robot" }).click();
  assert.equal(
    await page.locator(".robot-button").getAttribute("aria-pressed"),
    "true",
  );
  const question = await page.locator("#startup-question").textContent();
  await page.getByRole("button", { name: "Another question" }).click();
  assert.notEqual(
    await page.locator("#startup-question").textContent(),
    question,
  );
  await accessibility(page, "Homepage");

  await load(page, "/about/");
  await page.locator(".globe-wrap").scrollIntoViewIfNeeded();
  await page.waitForSelector(".globe-wrap.is-rendered");
  for (const [name, city] of [
    ["Germany", "Aachen, Germany."],
    ["Switzerland", "Bern, Switzerland."],
    ["India", "Coimbatore, India."],
  ]) {
    await page.getByRole("button", { name, exact: true }).click();
    assert.equal(
      await page.getByRole("heading", { name: city }).isVisible(),
      true,
    );
    assert.equal(await page.locator(".place-panel:visible").count(), 1);
  }
  await accessibility(page, "About");
  await load(page, "/projects/");
  assert.equal(await page.locator(".project-card").count(), 7);
  await load(page, "/spaces/research/");
  assert.equal(await page.locator(".project-card").count(), 2);
  assert.equal(await page.locator(".publication").count(), 2);
  await load(page, "/spaces/startups-business/");
  assert.equal(await page.locator(".project-card").count(), 2);
  await load(page, "/projects/when-ai-sets-the-price/");
  assert.equal(
    await page
      .getByRole("heading", { name: "The question", exact: true })
      .isVisible(),
    true,
  );
  await accessibility(page, "Case study");
  await load(page, "/writing/");
  assert.equal(
    await page
      .getByRole("heading", {
        name: "Verification: a complete article",
        exact: true,
      })
      .count(),
    1,
  );
  await page.goto(fixtures.post_url, { waitUntil: "networkidle" });
  assert.equal(
    await page
      .getByText("A complete article body visible to readers.", { exact: true })
      .isVisible(),
    true,
  );
  await accessibility(page, "Article");
  await page.goto(fixtures.category_url, { waitUntil: "networkidle" });
  assert.equal(
    await page
      .getByRole("heading", {
        name: "Verification: a complete article",
        exact: true,
      })
      .count(),
    1,
  );
  await load(page, "/?s=complete&post_type=post");
  assert.equal(
    await page
      .getByRole("heading", {
        name: "Verification: a complete article",
        exact: true,
      })
      .count(),
    1,
  );
  await load(page, "/a-page-that-does-not-exist/", 404);

  for (const width of [320, 390, 768]) {
    await page.setViewportSize({ width, height: 844 });
    for (const route of [
      "/",
      "/about/",
      "/projects/",
      "/writing/",
      "/projects/when-ai-sets-the-price/",
      "/projects/adi-verification-project/",
    ])
      await load(page, route);
    await load(page, "/");
    const toggle = page.getByRole("button", { name: "Menu", exact: true });
    await toggle.click();
    assert.equal(await toggle.getAttribute("aria-expanded"), "true");
    assert.equal(
      await page
        .locator(".site-nav")
        .getByRole("link", { name: "Writing" })
        .isVisible(),
      true,
    );
    await page.keyboard.press("Escape");
    assert.equal(await toggle.getAttribute("aria-expanded"), "false");
    if (width === 390) await accessibility(page, "Mobile home");
  }
  await context.close();
  const noJs = await browser.newContext({
    javaScriptEnabled: false,
    viewport: { width: 390, height: 844 },
  });
  const fallback = await noJs.newPage();
  await load(fallback, "/");
  assert.equal(
    await fallback
      .locator(".site-nav")
      .getByRole("link", { name: "Writing" })
      .isVisible(),
    true,
  );
  await fallback
    .locator(".filter-bar")
    .getByRole("link", { name: "Research", exact: true })
    .click();
  assert.equal(await fallback.locator(".project-card").count(), 2);
  await fallback.goto(base + "/about/");
  assert.equal(await fallback.locator(".place-panel:visible").count(), 3);
  await noJs.close();
  assert.deepEqual(
    errors,
    [],
    "No browser errors or failed local asset requests",
  );
  console.log(
    "Passed: real WordPress routes, editing/publishing, filters, tools, map, search, keyboard menu, no-JS navigation, 320/390/768/1440px layouts, and axe accessibility checks.",
  );
})()
  .catch((error) => {
    console.error(error);
    process.exitCode = 1;
  })
  .finally(async () => {
    if (browser) await browser.close();
    if (fixtures)
      php(
        `wp_delete_post(${Number(fixtures.post)},true); wp_delete_post(${Number(fixtures.project)},true);`,
      );
  });
