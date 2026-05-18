<?php
/**
 * Stops WordPress' canonical redirect from "correcting" a
 * `/<page>/<donor-id>` URL back to the bare page (which would throw away the
 * donor id before it reaches the <head> meta tag).
 *
 * Scoped precisely: it only disengages when our own query var is present,
 * i.e. when one of our rewrite rules actually matched. Canonical redirects on
 * the rest of the site are left untouched.
 *
 * @package Repejo\WpPlugin
 */

declare(strict_types=1);

namespace Repejo\WpPlugin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Canonical {

	public function register(): void {
		add_filter( 'redirect_canonical', array( $this, 'maybe_disable' ) );
	}

	/**
	 * @param string|false $redirect_url
	 * @return string|false
	 */
	public function maybe_disable( $redirect_url ) {
		$id = get_query_var( Rewrite::QUERY_VAR );

		if ( '' !== $id && null !== $id && false !== $id ) {
			return false;
		}

		return $redirect_url;
	}
}
