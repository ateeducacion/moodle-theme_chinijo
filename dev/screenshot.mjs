// Development only: capture the README screenshots from a running Chinijo site seeded with
// dev/seed.php (synthetic users and course). Run through dev/screenshot.sh (make screenshot).
//
// The capture is deterministic: fixed viewport, light colour scheme, reduced motion,
// English locale, and the student's display preferences reset before each shot.
import {chromium} from 'playwright';

const base = process.argv[2] || 'http://localhost:8080';
const out = process.argv[3] || '/out';
const password = 'Chinijo-demo-1234';

const browser = await chromium.launch();
const context = await browser.newContext({
    viewport: {width: 1280, height: 900},
    deviceScaleFactor: 1,
    locale: 'en-GB',
    colorScheme: 'light',
    reducedMotion: 'reduce',
});
const page = await context.newPage();

const login = async(username) => {
    await page.goto(`${base}/login/index.php`);
    await page.fill('#username', username);
    await page.fill('#password', password);
    await Promise.all([page.waitForNavigation(), page.click('#loginbtn')]);
};

const setPreferences = async(values) => {
    // Use the stand-alone page, as a learner without JavaScript would.
    await page.goto(`${base}/theme/chinijo/preferences.php`);
    for (const [name, value] of Object.entries(values)) {
        await page.check(`#region-main input[name="${name}"][value="${value}"]`);
    }
    await Promise.all([page.waitForNavigation(), page.click('#region-main button[data-action="save"]')]);
};

const openCourse = async() => {
    // Moodle finds the course by its short name, whatever its id is.
    await page.goto(`${base}/course/view.php?name=CHINIJO-DEMO`);
    await page.waitForSelector('img.theme-chinijo-pictogram');
    await page.waitForLoadState('networkidle');
};

await login('student1');
await setPreferences({contrast: 'default', fontsize: 'default', font: 'default',
    letterspacing: 'default', wordspacing: 'default', lineheight: 'default', motion: 'default', sound: 'default'});
await openCourse();
await page.screenshot({path: `${out}/chinijo-course.png`});

// Encouragement after marking an activity as done, then back to the previous state.
const toggle = () => page.locator('[data-activityname="Colour the sun"] button[data-action="toggle-manual-completion"]').first();
await toggle().click();
await page.waitForSelector('[data-region="theme_chinijo-cheer-card"]');
await page.screenshot({path: `${out}/chinijo-encouragement.png`});
await page.click('[data-action="theme_chinijo-cheer-close"]');
await toggle().click();
await page.waitForLoadState('networkidle');

// The next activity, opened from "Next".
await openCourse();
await Promise.all([page.waitForNavigation(), page.click('.theme-chinijo-next__start')]);
await page.waitForLoadState('networkidle');
await page.screenshot({path: `${out}/chinijo-activity.png`});

await openCourse();

await page.click('[data-action="theme_chinijo-open-preferences"]');
await page.waitForSelector('.modal.show form[data-region="theme_chinijo-preferences"]');
// The title is a string that may still be loading.
await page.waitForFunction(() => document.querySelector('.modal.show .modal-title')?.textContent.trim());
await page.screenshot({path: `${out}/chinijo-display-settings.png`});
await page.keyboard.press('Escape');

await setPreferences({contrast: 'high', fontsize: 'large', font: 'school'});
await openCourse();
await page.screenshot({path: `${out}/chinijo-course-high-contrast.png`});
await setPreferences({contrast: 'default', fontsize: 'default', font: 'default'});

await browser.close();
console.log(`Screenshots written to ${out}`);
