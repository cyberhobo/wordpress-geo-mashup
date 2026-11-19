// @ts-check
const { defineConfig, devices } = require( '@playwright/test' );

/**
 * End-to-end tests run against the wp-env site (`yarn e2e:env:start`).
 *
 * @see https://playwright.dev/docs/test-configuration
 */
module.exports = defineConfig( {
	testDir: './e2e/specs',
	fullyParallel: true,
	forbidOnly: !! process.env.CI,
	retries: process.env.CI ? 2 : 0,
	workers: process.env.CI ? 1 : undefined,
	reporter: process.env.CI ? [ [ 'github' ], [ 'html', { open: 'never' } ] ] : 'list',
	use: {
		baseURL: process.env.WP_BASE_URL || 'http://localhost:8888',
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
	},
	projects: [
		{
			name: 'chromium',
			use: { ...devices[ 'Desktop Chrome' ] },
		},
	],
} );
