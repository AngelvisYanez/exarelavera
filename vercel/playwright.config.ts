import { defineConfig, devices } from "@playwright/test";
import dotenv from "dotenv";
import path from "path";

/**
 * Read environment variables from file.
 * https://github.com/motdotla/dotenv
 */
dotenv.config({ path: path.resolve(__dirname, ".env") });

/**
 * Playwright configuration for synthetic monitoring.
 * Optimizes execution for production checks and captures debugging resources on failure.
 */
export default defineConfig({
  testDir: "./tests/monitoring",
  /* Run tests in files sequentially to prevent overloading production databases/APIs */
  fullyParallel: false,
  /* Fail the build on CI if you accidentally left test.only in the source code. */
  forbidOnly: !!process.env.CI,
  /* Retry once on failure to eliminate false alarms due to transient network latency */
  retries: process.env.CI ? 1 : 0,
  /* Opt out of parallel tests on monitoring */
  workers: 1,
  /* Reporter to use. See https://playwright.dev/docs/test-reporters */
  reporter: [
    ["html", { outputFolder: "public/report", open: "never" }],
    ["json", { outputFile: "public/report/results.json" }],
  ],
  /* Shared settings for all the projects below. See https://playwright.dev/docs/api/class-testoptions. */
  use: {
    /* Base URL to use in actions like `await page.goto('/')`. */
    baseURL:
      process.env.MONITOR_TARGET_URL || "https://tu-sistema-produccion.com",

    /* Collect trace, screenshots and video only when a test fails. */
    screenshot: "only-on-failure",
    video: "retain-on-failure",
    trace: "retain-on-failure",

    /* Timeout for each individual action (like click, type, etc.) */
    actionTimeout: 15000,
    /* Timeout for page navigation */
    navigationTimeout: 30000,
  },

  /* Configure projects for major browsers */
  projects: [
    {
      name: "chromium",
      use: { ...devices["Desktop Chrome"] },
    },
  ],
});
