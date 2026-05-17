<?php
/**
 * Dependency manifest for the build-free editor script (index.js).
 *
 * WordPress' block.json `file:` loader reads this sibling file to enqueue the
 * right core scripts (and in the right order) before index.js runs, so the
 * wp.* globals it uses are guaranteed to exist. No bundler required.
 *
 * @package Repejo\WpPlugin
 */

return array(
	'dependencies' => array(
		'wp-blocks',
		'wp-element',
		'wp-block-editor',
		'wp-components',
		'wp-i18n',
	),
	'version'      => '0.1.0',
);
