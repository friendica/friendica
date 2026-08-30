// SPDX-FileCopyrightText: 2010-2026 the Friendica project
//
// SPDX-License-Identifier: AGPL-3.0-or-later

import js from "@eslint/js";
import globals from "globals";

/**
 * Globals that Friendica's own scripts legitimately rely on but cannot declare
 * themselves. Anything not listed here is reported by `no-undef`, which is what
 * catches typos and accidental implicit globals.
 */

// Third-party libraries pulled in through <script> tags in the head templates.
const vendorGlobals = {
	autosize: "readonly",   // view/theme/frio/frameworks/autosize/autosize.min.js
	DOMPurify: "readonly",  // view/asset/dompurify/dist/purify.min.js
	Dropzone: "readonly",   // vendor/enyo/dropzone/dist/min/dropzone.min.js
	moment: "readonly",     // view/asset/moment/min/moment-with-locales.min.js
	up: "readonly",         // view/asset/unpoly/unpoly.min.js
};

// Page variables injected by PHP or by inline <script> blocks in the templates.
const injectedGlobals = {
	acl: "writable",
	aActErr: "readonly",
	aErrType: "readonly",
	aStr: "readonly",
	baseurl: "readonly",
	calendar_api: "readonly",
	dzStrings: "readonly",
	event_api: "readonly",
	infinite_scroll: "readonly",
	localUser: "readonly",
	netargs: "writable",
	profile_uid: "readonly",
	spaEnabled: "readonly", // view/templates/head.tpl, view/theme/frio/templates/head.tpl
	theme: "writable", // reassigned by previewTheme() in view/js/main.js
};

// Friendica's own scripts share one global namespace across files; these are
// declared in one file and used from another.
const friendicaGlobals = {
	addToModal: "readonly",
	bin2hex: "readonly",
	cleanContactUrl: "readonly",
	closeMenu: "readonly",
	commentBusy: "writable",
	commentClose: "readonly",
	commentCloseUI: "readonly",
	hideLoading: "readonly",
	htmlToText: "readonly",
	initInfiniteScroll: "readonly", // view/js/main.js
	insertBBCodeInTextarea: "readonly",
	jotShow: "readonly",
	NavUpdate: "readonly",
	openMenu: "readonly",
	originalTitle: "writable",
	scrollToItem: "readonly",
	showFetching: "readonly",
	showPosting: "readonly",
	showProcessing: "readonly",
	showReceiving: "readonly",
	timer: "writable",
	unpause: "readonly",
	updateContent: "readonly",
	updateItem: "readonly",
};

export default [
	{
		ignores: [
			"addon/**",
			"local/**",
			"node_modules/**",
			"vendor/**",
			"**/.venv/**",
			// Vendored front-end code
			"view/asset/**",
			"view/theme/frio/frameworks/**",
			"**/*.min.js",
			// Vendored libraries living outside the directories above; each of
			// these carries a foreign SPDX-FileCopyrightText header.
			"view/js/ajaxupload.js",
			"view/js/autocomplete.js",
			"view/js/country.js",
			"view/js/fancybox/**",
			"view/js/friendica-tagsinput/**",
			"view/js/hls/**",
			"view/js/jquery.textinputs.js",
			"view/js/linkPreview.js",
			"view/js/modernizr.js",
			"view/js/vanillaEmojiPicker/**",
			"view/js/videojs/**",
		],
	},
	js.configs.recommended,
	{
		files: ["view/**/*.js", "view/**/*.mjs", "mods/**/*.js"],
		languageOptions: {
			ecmaVersion: 2022,
			sourceType: "script",
			globals: {
				...globals.browser,
				...globals.jquery,
				...vendorGlobals,
				...injectedGlobals,
				...friendicaGlobals,
			},
		},
		rules: {
			// Top-level functions in these scripts are globals invoked from
			// onclick= attributes in the Smarty templates, so only locals can be
			// judged unused. Unused function arguments are common in jQuery
			// callbacks and carry no signal either.
			"no-unused-vars": ["error", {
				vars: "local",
				args: "none",
				caughtErrors: "none",
				varsIgnorePattern: "^_",
			}],

			// The globals above are declared in one file and used in another, so
			// the declaring file must not count as a redeclaration.
			"no-redeclare": ["error", { builtinGlobals: false }],

			// Bug classes beyond the recommended set.
			eqeqeq: ["error", "smart"],
			"no-await-in-loop": "error",
			"no-constant-binary-expression": "error",
			"no-constructor-return": "error",
			"no-promise-executor-return": "error",
			"no-self-compare": "error",
			"no-template-curly-in-string": "error",
			"no-unmodified-loop-condition": "error",
			"no-unreachable-loop": "error",
			"no-use-before-define": ["error", { functions: false, classes: false }],
			"require-atomic-updates": "error",
		},
	},
	{
		files: ["view/js/spa/**/*.js"],
		languageOptions: {
			sourceType: "module",
		},
	},
];
