'use strict';

const MarkdownIt = require( 'markdown-it' );

class Converter {
	constructor( options = {} ) {
		this.markdown = new MarkdownIt( {
			breaks: Boolean( options.simpleLineBreaks ),
			html: true,
			linkify: false,
		} );
	}

	makeHtml( text ) {
		return normalizeHtml( this.markdown.render( String( text ?? '' ) ) );
	}
}

function normalizeHtml( html ) {
	return html
		.trim()
		.replace( /<br>\n/g, '<br />\n' )
		.replace(
			/<pre><code(?: class="language-([^"]+)")?>([\s\S]*?)\n<\/code><\/pre>/g,
			( _match, language, code ) => {
				if ( language ) {
					return `<pre><code class="${ language } language-${ language }">${ code }</code></pre>`;
				}

				return `<pre><code>${ code }</code></pre>`;
			}
		);
}

module.exports = {
	Converter,
};
