/**
 * This file is cloned from the WooCommerce repo.
 * See {@link https://github.com/woocommerce/automatewoo/blob/develop/admin/assets/src/upstream/woocommerce-admin-analytics/README.md ../README.md}.
 */
// eslint-disable-next-line @woocommerce/dependency-group -- DEWPed non installable dependency, see https://github.com/woocommerce/woocommerce/issues/35603.
import { getSetting } from '@woocommerce/settings';

export const CURRENCY = getSetting( 'currency' );
