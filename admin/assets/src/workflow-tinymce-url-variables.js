/**
 * Let AutomateWoo variable tokens be used as link URLs in the visual editor.
 *
 * When a merchant inserts a link whose URL is an AutomateWoo variable
 * (e.g. `{{ customer.unsubscribe_url }}`), WordPress' link dialog
 * (`window.wpLink`) prepends a protocol because the value does not look like a
 * URL. That turns `{{ customer.unsubscribe_url }}` into
 * `http://{{ customer.unsubscribe_url }}`, which is then rendered as a broken
 * link and mangles the variable at send time.
 *
 * WordPress has two link UIs and each prepends a protocol in its own place:
 *
 *   1. The advanced link modal (`window.wpLink`, opened from the inline
 *      toolbar's gear button) corrects the URL in `wpLink.correctURL()`.
 *   2. The inline link toolbar (the `wplink` TinyMCE plugin used by default in
 *      the visual editor) prepends `http://` inside its `wp_link_apply` editor
 *      command and then flags the link as invalid via `checkLink()`. This path
 *      never touches `wpLink.correctURL()`.
 *
 * To cover both, this module:
 *
 *   1. makes `wpLink.correctURL()` a no-op for variable-only URLs on AutomateWoo
 *      editors (the modal path), and
 *   2. post-corrects the inline-toolbar path: after a link is applied on an
 *      AutomateWoo editor it strips the `http://`/`https://` that the plugin
 *      prepended to a variable-only token, fixes the matching link text, and
 *      clears the `data-wplink-url-error` flag.
 *
 * Both paths are scoped to AutomateWoo editors and to values composed solely of
 * AutomateWoo variable tokens, so behaviour for every other editor and for any
 * value that is not an AutomateWoo variable token is unchanged.
 *
 * @since x.x.x
 */

/**
 * Selectors that identify a container belonging to an AutomateWoo editor.
 *
 * `.automatewoo-field-wrap` wraps the editor in a workflow action field;
 * `.automatewoo-page` wraps the settings screens that render a TinyMCE field.
 * (The workflow post-edit screen is matched separately via its body class.)
 */
const AW_EDITOR_CONTAINER_SELECTOR =
	'.automatewoo-field-wrap, .automatewoo-field, .automatewoo-page';

/**
 * Body class present on the workflow post-edit screen, where the email-content
 * editor lives.
 */
const AW_WORKFLOW_BODY_CLASS = 'post-type-aw_workflow';

/**
 * Matches a URL value made up solely of AutomateWoo variable tokens, e.g.
 * `{{ customer.unsubscribe_url }}` or `{{ order.id | fallback: '#' }}`.
 *
 * AutomateWoo variables always use a dotted `datatype.variable` name and may
 * carry trailing modifiers after a pipe. Requiring that shape keeps the guard
 * specific to AutomateWoo tokens rather than any `{{ ... }}` placeholder.
 *
 * @param {string} value The URL field value.
 * @return {boolean} True if the value is an AutomateWoo variable token URL.
 */
function isVariableUrl( value ) {
	if ( typeof value !== 'string' ) {
		return false;
	}

	const trimmed = value.trim();

	// A single AutomateWoo token: `{{ word.word[.word…] [| modifiers] }}`.
	const token = '\\{\\{\\s*\\w+(?:\\.\\w+)+\\s*(?:\\|[^{}]*)?\\}\\}';

	// The whole value must be one or more such tokens (and nothing else), so we
	// never skip correction for a value that also contains a real URL fragment.
	return new RegExp( `^(?:\\s*${ token }\\s*)+$` ).test( trimmed );
}

/**
 * Whether a given element belongs to an AutomateWoo editor.
 *
 * True on the workflow post-edit screen (an AutomateWoo-only screen) or when the
 * element lives inside an AutomateWoo editor container, so corrections never
 * affect editors that are not AutomateWoo's.
 *
 * @param {?Element} element An element associated with the editor (or null).
 * @return {boolean} True if the element belongs to an AutomateWoo editor.
 */
function isAutomateWooEditorElement( element ) {
	// The workflow post-edit screen (where the email-content editor lives) is
	// an AutomateWoo-only screen, identified by its body class.
	if ( document.body.classList.contains( AW_WORKFLOW_BODY_CLASS ) ) {
		return true;
	}

	return !! (
		element &&
		element.closest &&
		element.closest( AW_EDITOR_CONTAINER_SELECTOR )
	);
}

/**
 * Whether the editor that opened the link dialog is an AutomateWoo editor.
 *
 * WordPress stores the id of the active editor on `window.wpActiveEditor`. We
 * resolve its wrapper element and check it lives inside an AutomateWoo
 * container, so the patch never affects link dialogs opened from other editors.
 *
 * @return {boolean} True if the active editor belongs to AutomateWoo.
 */
function isAutomateWooEditorActive() {
	const activeId = window.wpActiveEditor;

	const wrap = activeId
		? document.getElementById( `wp-${ activeId }-wrap` ) ||
		  document.getElementById( activeId )
		: null;

	return isAutomateWooEditorElement( wrap );
}

/**
 * Patch `window.wpLink.correctURL` so it leaves AutomateWoo variable URLs alone.
 *
 * @return {boolean} True if the patch was applied (or already present).
 */
function patchWpLink() {
	const wpLink = window.wpLink;

	if ( ! wpLink || typeof wpLink.correctURL !== 'function' ) {
		return false;
	}

	if ( wpLink.__awVariableUrlPatched ) {
		return true;
	}

	const originalCorrectURL = wpLink.correctURL;

	wpLink.correctURL = function () {
		// `wpLink` keeps the URL <input> in a private closure; read it from the
		// DOM so we can inspect the current value before correction runs.
		const urlInput = document.getElementById( 'wp-link-url' );

		if (
			urlInput &&
			isAutomateWooEditorActive() &&
			isVariableUrl( urlInput.value )
		) {
			// Skip protocol prepending for AutomateWoo variable-only URLs.
			return;
		}

		return originalCorrectURL.apply( this, arguments );
	};

	wpLink.__awVariableUrlPatched = true;

	return true;
}

/**
 * Strip the protocol the inline link toolbar prepended to variable-only links.
 *
 * The `wplink` TinyMCE plugin turns a `{{ datatype.var }}` URL into
 * `http://{{ datatype.var }}` (and flags it invalid) inside its `wp_link_apply`
 * command. We can't intercept that closure, so we correct the result: any link
 * in the editor whose URL is `http(s)://` followed by a variable-only token has
 * the protocol removed and its error flag cleared. When the plugin used the
 * prepended URL as the link text (links inserted without selected text), the
 * text is corrected too.
 *
 * TinyMCE caches a link's URL in `data-mce-href` and serializes `getContent()`
 * (used when switching to the Text/Code view and when saving) from that cached
 * value, not the live `href`. So both attributes must be corrected: `href` keeps
 * the inline edit dialog accurate, `data-mce-href` keeps the serialized output
 * (and therefore the saved workflow) clean.
 *
 * @param {Object} editor The TinyMCE editor instance.
 */
function fixVariableLinks( editor ) {
	editor.$( 'a[href]' ).each( function ( i, element ) {
		const $link = editor.$( element );
		// `data-mce-href` is the raw value TinyMCE serializes from; fall back to
		// the live `href` when it is absent.
		const url = $link.attr( 'data-mce-href' ) || $link.attr( 'href' );
		const stripped =
			typeof url === 'string' ? url.replace( /^https?:\/\//i, '' ) : url;

		if ( stripped === url || ! isVariableUrl( stripped ) ) {
			return;
		}

		if ( $link.text() === url || $link.text() === $link.attr( 'href' ) ) {
			$link.text( stripped );
		}

		$link.attr( 'href', stripped );

		if ( $link.attr( 'data-mce-href' ) !== undefined ) {
			$link.attr( 'data-mce-href', stripped );
		}

		$link.removeAttr( 'data-wplink-url-error' );
	} );
}

/**
 * Attach the inline-toolbar correction to a single editor (once).
 *
 * @param {Object} editor The TinyMCE editor instance.
 */
function hookEditor( editor ) {
	if ( ! editor || editor.__awVariableUrlHooked ) {
		return;
	}

	if (
		! isAutomateWooEditorElement( editor.getElement && editor.getElement() )
	) {
		return;
	}

	editor.__awVariableUrlHooked = true;

	// `ExecCommand` fires after the command runs, so the link node already has
	// the prepended href and the error flag by the time we correct it.
	editor.on( 'ExecCommand', function ( event ) {
		if (
			event.command === 'wp_link_apply' ||
			event.command === 'mceInsertLink'
		) {
			fixVariableLinks( editor );
		}
	} );
}

/**
 * Hook every AutomateWoo TinyMCE editor, current and future.
 *
 * @return {boolean} True once `window.tinymce` is available and hooks are set.
 */
function hookEditors() {
	const tinymce = window.tinymce;

	if ( ! tinymce ) {
		return false;
	}

	( tinymce.editors || [] ).forEach( hookEditor );

	tinymce.on( 'AddEditor', function ( event ) {
		hookEditor( event.editor );
	} );

	return true;
}

/**
 * `window.wpLink` is set up by WordPress' `wplink` script, which may load
 * before or after this module. Try immediately, and if it is not ready yet,
 * poll briefly until it appears.
 *
 * A previous version only retried on `DOMContentLoaded` (and, once the document
 * was already past the `loading` phase, retried a single time synchronously).
 * When this bundle ran before `wplink` with the document already interactive,
 * that single retry executed while `window.wpLink` was still undefined and
 * never tried again, so `correctURL` stayed unpatched. Polling guarantees we
 * patch as soon as `wpLink` exists. The patch is a property on `wpLink`, which
 * WordPress reads at call time, so applying it before the link dialog is used
 * is sufficient.
 */
function init() {
	let wpLinkDone = patchWpLink();
	let editorsDone = hookEditors();

	if ( wpLinkDone && editorsDone ) {
		return;
	}

	let attempts = 0;
	const maxAttempts = 100; // ~10s at 100ms intervals.
	const timer = setInterval( function () {
		attempts += 1;

		if ( ! wpLinkDone ) {
			wpLinkDone = patchWpLink();
		}

		if ( ! editorsDone ) {
			editorsDone = hookEditors();
		}

		if ( ( wpLinkDone && editorsDone ) || attempts >= maxAttempts ) {
			clearInterval( timer );
		}
	}, 100 );
}

init();
