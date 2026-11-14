// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

export default {
	extends: "stylelint-config-recommended",

	/**
	 * Third-party CSS we ship but do not maintain.
	 * Everything else under view/ is ours and is linted.
	 */
	ignoreFiles: [
		"addon/**",
		"node_modules/**",
		"vendor/**",
		"view/asset/**",
		"view/theme/frio/frameworks/**",
		"**/*.min.css",
		"**/*.min.*.css",
		"view/fonts/**",
		"view/js/fancybox/**",
		"view/js/friendica-tagsinput/**",
		"view/js/videojs/**",
		"view/theme/frio/font/**",
	],

	rules: {
		/**
		 * 767 findings that would need thousands of rules reordered.
		 * Off until someone takes that on as its own change.
		 */
		"no-descending-specificity": null,

		/**
		 * remixicon is an icon font: its glyphs live in the private use area,
		 * so a generic fallback would render tofu boxes instead of an icon.
		 * Leaving the fallback out is deliberate, every other family needs one.
		 */
		"font-family-no-missing-generic-family-keyword": [true, {
			ignoreFontFamilies: ["remixicon"],
		}],

		/**
		 * Consecutive duplicates with different values are the usual
		 * fallback-then-override pattern, not a mistake.
		 */
		"declaration-block-no-duplicate-properties": [true, {
			ignore: ["consecutive-duplicates-with-different-values"],
		}],
	},

	overrides: [
		{
			/**
			 * These files are not plain CSS, style.php substitutes the
			 * $placeholder values at request time. Only those values are
			 * exempt from value checking, the rest of the file is linted.
			 */
			files: [
				"view/theme/frio/css/*.css",
				"view/theme/frio/scheme/*.css",
			],
			rules: {
				"declaration-property-value-no-unknown": [true, {
					ignoreProperties: { "/.*/": ["/\\$[a-z_]+/"] },
				}],
			},
		},
	],
};
