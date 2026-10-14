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
const portfolioOrder = [
  "From ideas to testable concepts",
  "When AI sets the price",
  "A launch moment for Zario",
  "Business insights for packaging innovation",
  "Building with innovation teams",
  "Rethinking the packaging value chain",
];
async function spinChecks(page) {
  const globe = page.getByRole("button", {
    name: "Spin the globe",
    exact: true,
  });
  const image = () =>
    page.locator(".globe").evaluate((canvas) => canvas.toDataURL());
  const finished = () =>
    page.waitForFunction(
      () =>
        !document
          .querySelector(".globe-wrap")
          .classList.contains("is-spinning"),
    );
  await globe.scrollIntoViewIfNeeded();
  await page.getByRole("button", { name: "Switzerland", exact: true }).click();
  const baseline = await image();
  await globe.click();
  assert.equal(
    await globe.evaluate((el) => el.classList.contains("is-spinning")),
    false,
    "Reduced motion disables the spin",
  );
  assert.equal(await image(), baseline);
  await page.emulateMedia({ reducedMotion: "no-preference" });
  const geometry = await globe.boundingBox();
  const start = Date.now();
  await globe.click();
  await page.waitForTimeout(375);
  const firstQuarter = await image();
  await page.waitForTimeout(375);
  const halfTurn = await image();
  await page.waitForTimeout(375);
  const thirdQuarter = await image();
  assert.notEqual(
    firstQuarter,
    baseline,
    "First part of the globe rotation is visible",
  );
  assert.notEqual(
    halfTurn,
    firstQuarter,
    "The globe continues around the world",
  );
  assert.notEqual(
    thirdQuarter,
    halfTurn,
    "The globe returns towards its starting view",
  );
  await finished();
  assert(
    Date.now() - start >= 1200 && Date.now() - start < 1900,
    "A full spin takes about 1.5 seconds",
  );
  assert.equal(
    await image(),
    baseline,
    "The globe returns to exactly its original orientation",
  );
  assert.deepEqual(
    await globe.boundingBox(),
    geometry,
    "Spin does not change layout",
  );
  assert.equal(
    await page
      .locator('[data-place="switzerland"]')
      .getAttribute("aria-pressed"),
    "true",
  );
  await globe.focus();
  await page.keyboard.press("Enter");
  assert.equal(
    await globe.evaluate((el) => el.classList.contains("is-spinning")),
    true,
    "Enter starts a spin",
  );
  await finished();
  await page.keyboard.press("Space");
  await page.waitForTimeout(200);
  await globe.click();
  await globe.click();
  await page.waitForTimeout(1500);
  assert.equal(
    await globe.evaluate((el) => el.classList.contains("is-spinning")),
    true,
    "Clicks during a spin queue another complete turn",
  );
  await finished();
  assert.equal(
    await image(),
    baseline,
    "Repeated clicks leave a settled, reusable globe",
  );
  await globe.click();
  await page.getByRole("button", { name: "India", exact: true }).click();
  await finished();
  await page.waitForTimeout(500);
  assert.equal(
    await page.locator('[data-place="india"]').getAttribute("aria-pressed"),
    "true",
  );
  assert.equal(
    await page.getByRole("heading", { name: "Coimbatore, India." }).isVisible(),
    true,
    "Country selection works during a spin",
  );
  const india = await image();
  await globe.click();
  await finished();
  assert.equal(
    await image(),
    india,
    "A spin preserves the newly selected country and camera",
  );
  await globe.click();
  await page.emulateMedia({ reducedMotion: "reduce" });
  await finished();
  assert.equal(
    await image(),
    india,
    "Changing the motion preference cancels the spin cleanly",
  );
  await accessibility(page, "Interactive globe");
}
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
  console.log(
    php(
      fs
        .readFileSync(path.join(__dirname, "verify-messaging.php"), "utf8")
        .replace(/^<\?php\s*/, ""),
    ).trim(),
  );
  assert.equal(
    Number(php("echo adi_content_import();").trim()),
    0,
    "Importer is idempotent",
  );
  assert.equal(
    php(
      "$before=serialize(get_posts(array('post_type'=>array('page','adi_project','adi_publication'),'post_status'=>'any','numberposts'=>-1))); adi_content_update_messaging(); echo $before===serialize(get_posts(array('post_type'=>array('page','adi_project','adi_publication'),'post_status'=>'any','numberposts'=>-1))) ? 'unchanged' : 'changed';",
    ).trim(),
    "unchanged",
    "Messaging upgrade is idempotent",
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
  page.on("console", (message) => {
    if (
      message.type() === "warning" ||
      (message.type() === "error" &&
        !message.text().startsWith("Failed to load resource"))
    )
      errors.push(message.type() + ": " + message.text());
  });
  page.on("response", (response) => {
    if (
      response.url().startsWith(base) &&
      response.request().resourceType() !== "document" &&
      response.status() >= 400
    )
      errors.push(response.url() + ": HTTP " + response.status());
  });
  page.on("requestfailed", (request) => {
    if (request.url().startsWith(base))
      errors.push(request.url() + ": " + request.failure().errorText);
  });
  await load(page, "/");
  assert.equal(await page.locator(".project-card").count(), 6);
  assert.equal(await page.locator(".project-card:visible").count(), 6);
  assert.deepEqual(
    await page.evaluate(() =>
      [...document.querySelectorAll('a[href^="#"]')]
        .map((a) => a.getAttribute("href"))
        .filter(
          (hash) => hash.length > 1 && !document.getElementById(hash.slice(1)),
        ),
    ),
    [],
    "Every homepage anchor has a destination",
  );
  assert.equal(
    await page.locator("h1").textContent(),
    "Validate with consumers before you build.",
  );
  assert.equal(
    await page.locator(".intro-descriptor").textContent(),
    "Consumer Behaviour & AI Researcher",
  );
  assert.equal(
    await page
      .getByRole("link", { name: "Let's collaborate" })
      .getAttribute("href"),
    "#contact",
  );
  assert.equal(
    await page.locator(".intro-secondary").getAttribute("href"),
    "#contributions",
  );
  assert.deepEqual(
    await page.locator(".project-card h3").allTextContents(),
    portfolioOrder,
  );
  assert.equal(
    await page.locator(".contact-top h2").textContent(),
    "Have a startup idea worth testing?",
  );
  assert.equal(
    await page.getByRole("link", { name: "Let's talk." }).getAttribute("href"),
    "mailto:adithya.kamaraj@gmail.com",
  );
  for (const [name, count] of [
    ["Research", 2],
    ["Technology & AI", 2],
    ["Business & Innovation", 2],
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
  assert.equal(await page.locator(".playground").count(), 0);
  assert.equal(await page.locator(".venn-diagram circle").count(), 4);
  assert.equal(
    await page
      .getByRole("img", { name: "Research, Technology, and Business" })
      .isVisible(),
    true,
  );
  const order = await page
    .locator("main > section")
    .evaluateAll((nodes) =>
      nodes.map((node) =>
        [...node.classList].find((name) =>
          [
            "intro-hero",
            "spaces-section",
            "publications",
            "work-section",
            "biography",
            "writing-section",
          ].includes(name),
        ),
      ),
    );
  assert.deepEqual(order, [
    "intro-hero",
    "spaces-section",
    "publications",
    "work-section",
    "biography",
    "writing-section",
  ]);
  assert.deepEqual(await page.locator(".space-card h3").allTextContents(), [
    "Research",
    "Technology & AI",
    "Business & Innovation",
  ]);
  assert.equal(await page.locator("#contributions .publication").count(), 2);
  assert.equal(
    await page
      .getByRole("heading", {
        name: "Engineering, entrepreneurship, and a lot of questions.",
      })
      .isVisible(),
    true,
  );
  const homeTab = page
    .locator(".view-switch")
    .getByRole("link", { name: "Home", exact: true });
  assert.equal(await homeTab.getAttribute("aria-current"), "page");
  await accessibility(page, "Homepage");

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
  await accessibility(page, "Home geography");
  await spinChecks(page);
  await load(page, "/about/");
  assert.equal(
    page.url(),
    base + "/#story",
    "Older About links lead to the biography on Home",
  );
  await page
    .locator(".view-switch")
    .getByRole("link", { name: "Projects", exact: true })
    .click();
  assert.equal(page.url(), base + "/projects/");
  assert.equal(
    await page
      .locator(".view-switch")
      .getByRole("link", { name: "Projects", exact: true })
      .getAttribute("aria-current"),
    "page",
  );
  assert.equal(await page.locator(".project-card").count(), 7);
  assert.deepEqual(
    (await page.locator(".project-card h3").allTextContents()).slice(0, 6),
    portfolioOrder,
  );
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
  for (const [slug, text] of [
    [
      "from-ideas-to-testable-concepts",
      "more than 20 hardware product innovations",
    ],
    ["a-launch-moment-for-zario", "#1 Product of the Day"],
    ["business-insights-for-packaging-innovation", "competitive analysis"],
    ["building-with-innovation-teams", "more than ten innovation teams"],
    [
      "rethinking-the-packaging-value-chain",
      "circular plastic packaging value chain",
    ],
  ]) {
    await load(page, "/projects/" + slug + "/");
    assert(
      (await page.locator(".entry-content").textContent()).includes(text),
      "Existing case content: " + slug,
    );
    assert.equal(
      await page
        .getByRole("heading", { name: "Have a startup idea worth testing?" })
        .count(),
      1,
      "Shared invitation on each case",
    );
  }
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
  const touchContext = await browser.newContext({
    viewport: { width: 390, height: 844 },
    isMobile: true,
    hasTouch: true,
  });
  const touchPage = await touchContext.newPage();
  touchPage.on("pageerror", (error) => errors.push(error.message));
  await load(touchPage, "/");
  await touchPage.waitForSelector(".globe-wrap.is-rendered");
  const touchGlobe = touchPage.getByRole("button", {
    name: "Spin the globe",
    exact: true,
  });
  await touchGlobe.tap();
  await touchPage.waitForFunction(() =>
    document.querySelector(".globe-wrap").classList.contains("is-spinning"),
  );
  await touchPage.waitForFunction(
    () =>
      !document.querySelector(".globe-wrap").classList.contains("is-spinning"),
  );
  await touchPage.getByRole("button", { name: "Germany", exact: true }).tap();
  assert.equal(
    await touchPage
      .getByRole("heading", { name: "Aachen, Germany." })
      .isVisible(),
    true,
  );
  await accessibility(touchPage, "Touch globe");
  await touchContext.close();
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
    "Passed: real WordPress routes, messaging, curated portfolio order, idempotent updates, editing/publishing, navigation, filters, globe rotation and timing, repeated clicks, country changes, touch, keyboard, reduced motion, no spin layout shifts, search, no-JS fallback, 320/390/768/1440px layouts, and axe accessibility checks.",
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
