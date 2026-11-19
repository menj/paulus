/**
 * Cite Plugin - Gutenberg Block
 * Version: 2.8.0
 * Dynamic block — rendered server-side via render_callback.
 *
 * Live editor preview: uses wp.data.withSelect (HOC) to subscribe to the
 * post store, so the citation updates live as the editor types.
 */
const { registerBlockType }         = wp.blocks;
const { InspectorControls }         = wp.blockEditor;
const { PanelBody, SelectControl,
        ToggleControl, TextControl } = wp.components;
const { __ }                        = wp.i18n;
const { withSelect }                = wp.data;
const el                            = wp.element.createElement;

// useBlockProps was added in WP 5.6 – fall back gracefully on older installs
const useBlockProps = (wp.blockEditor && wp.blockEditor.useBlockProps)
    ? wp.blockEditor.useBlockProps
    : () => ({ className: 'citepress-citation-block-editor' });

// Data passed from PHP via wp_localize_script
const blockData      = window.citepressBlockData || {};
const formatOptions  = blockData.formats || [{ label: 'APA', value: 'apa' }];
const citationStyles = blockData.styles  || {};

// Month names for human-readable date formatting
const MONTHS = [
    'January','February','March','April','May','June',
    'July','August','September','October','November','December'
];

const modeOptions = [
    { label: __('Box (default)', 'cite'),         value: 'box' },
    { label: __('Inline parenthetical', 'cite'),  value: 'inline' },
    { label: __('Bibliography footnote', 'cite'), value: 'bibliography' },
];

// ─────────────────────────────────────────────────────────────────────────────
// Date helpers
// ─────────────────────────────────────────────────────────────────────────────

function parseDate(isoString) {
    // Use fallback to avoid timezone issues in older browsers
    var d = isoString ? new Date(isoString) : new Date();
    if (isNaN(d.getTime())) d = new Date();
    return d;
}

function formatDMY(d) {
    return d.getDate() + ' ' + MONTHS[d.getMonth()] + ' ' + d.getFullYear();
}

function formatLong(d) {
    return MONTHS[d.getMonth()] + ' ' + d.getDate() + ', ' + d.getFullYear();
}

// ─────────────────────────────────────────────────────────────────────────────
// Citation builders  (mirror frontend.js exactly)
// ─────────────────────────────────────────────────────────────────────────────

function htmlEsc(s) {
    return String(s || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

/**
 * Return the rendered citation HTML for a given style key and post data object.
 * Mirrors the token-replacement done in frontend.js updateCitation().
 */
function buildCitationHTML(styleKey, data) {
    var template = citationStyles[styleKey];
    if (!template) return '<em>' + __('Unknown citation style', 'cite') + '</em>';

    if (styleKey === 'wikipedia') {
        var wikiAuthors = (data.authors && data.authors.length) ? data.authors : [data.author];
        var parts = ['{{cite web'];
        parts.push(' |url='         + data.permalink);
        parts.push(' |title='       + data.title);
        if (wikiAuthors.length === 1) {
            parts.push(' |author=' + wikiAuthors[0]);
        } else {
            wikiAuthors.forEach(function(a, i) {
                parts.push(' |author' + (i + 1) + '=' + a);
            });
        }
        parts.push(' |date='        + (data.publicationDateDMY || data.publicationDate));
        parts.push(' |website='     + data.siteName);
        parts.push(' |language='    + (data.language || 'en'));
        parts.push(' |access-date=' + (data.dateAccessedDMY || data.dateAccessed));
        parts.push('}}');
        return parts.join('');
    }

    var permalinkHTML = '<a href="' + htmlEsc(data.permalink) + '">'
                      + htmlEsc(data.permalink) + '</a>';

    var citation = template
        .replace('{author}',           htmlEsc(data.author))
        .replace('{sitename}',         htmlEsc(data.siteName))
        .replace('{title}',            htmlEsc(data.title))
        .replace('{date}',             htmlEsc(data.dateAccessed))
        .replace('{publication_date}', htmlEsc(data.publicationDate))
        .replace('{publication_year}', htmlEsc(data.publicationYear))
        .replace('{permalink}',        permalinkHTML);

    return citation
        .replace(/\s\./g, '.')
        .replace(/\s,/g, ',')
        .replace(/\s+/g, ' ')
        .trim();
}

/**
 * Return a parenthetical inline citation string (no HTML – used as text node).
 */
function buildInlineText(styleKey, data, page) {
    var nameParts = String(data.author || 'Author').trim().split(/\s+/);
    var surname   = nameParts[nameParts.length - 1];
    var year      = data.publicationYear || String(new Date().getFullYear());
    var pg        = page ? ', p.\u00a0' + page : '';

    var tpl = {
        apa: '(' + surname + ', ' + year + ')',
        mla: '(' + surname + pg + ')',
        chicago: '(' + surname + ' ' + year + pg + ')',
        harvard: '(' + surname + ' ' + year + ')',
        vancouver: '(1)', ieee: '[1]', ama: '(1)',
        asa:      '(' + surname + ' ' + year + ')',
        turabian: '(' + surname + ' ' + year + pg + ')',
        wikipedia:'(' + surname + ' ' + year + ')',
        acs: '(1)', aip: '1', nlm: '(1)',
        aaa:     '(' + surname + ' ' + year + ')',
        apsa:    '(' + surname + ' ' + year + ')',
        oscola: '(n\u00a01)', nature: '1', acm: '[1]',
        bluebook:'(' + surname + ' ' + year + ')',
        iso690:  '(' + surname + ', ' + year + ')',
    };
    return tpl[styleKey] || '(' + surname + ', ' + year + ')';
}

// ─────────────────────────────────────────────────────────────────────────────
// SVG icon helpers  (return React elements, no JSX)
// ─────────────────────────────────────────────────────────────────────────────

function QuoteIcon() {
    return el('svg', { className:'citepress-cite-icon', width:'20', height:'20',
        viewBox:'0 0 20 20', fill:'currentColor', 'aria-hidden':'true' },
        el('path', { d:'M8.5 5.5C8.5 4.12 7.38 3 6 3S3.5 4.12 3.5 5.5c0 1.03.63 1.92 1.52 2.3-.15 1.52-.78 2.87-1.87 3.93a.5.5 0 0 0 .35.85c2.14 0 3.92-1.15 4.67-2.85.23-.53.33-1.1.33-1.73V5.5zm8 0C16.5 4.12 15.38 3 14 3s-2.5 1.12-2.5 2.5c0 1.03.63 1.92 1.52 2.3-.15 1.52-.78 2.87-1.87 3.93a.5.5 0 0 0 .35.85c2.14 0 3.92-1.15 4.67-2.85.23-.53.33-1.1.33-1.73V5.5z' })
    );
}
function CopyIcon() {
    return el('svg', { width:'16', height:'16', viewBox:'0 0 16 16', fill:'none' },
        el('path', { fill:'currentColor', d:'M13.5 2H6.5C5.67 2 5 2.67 5 3.5V10.5C5 11.33 5.67 12 6.5 12H13.5C14.33 12 15 11.33 15 10.5V3.5C15 2.67 14.33 2 13.5 2ZM13.5 10.5H6.5V3.5H13.5V10.5ZM2.5 6H4V7.5H2.5V12.5H9.5V11H11V12.5C11 13.33 10.33 14 9.5 14H2.5C1.67 14 1 13.33 1 12.5V7.5C1 6.67 1.67 6 2.5 6Z' })
    );
}
function ExportIcon() {
    return el('svg', { width:'16', height:'16', viewBox:'0 0 16 16', fill:'none' },
        el('path', { fill:'currentColor', d:'M8 12L3 7L4.4 5.6L7 8.2V0H9V8.2L11.6 5.6L13 7L8 12ZM2 16C1.45 16 0.979 15.804 0.587 15.412C0.195 15.02 0 14.55 0 14V11H2V14H14V11H16V14C16 14.55 15.804 15.021 15.412 15.413C15.02 15.805 14.549 16 14 16H2Z' })
    );
}
function ChevronIcon() {
    return el('svg', { className:'toggle-icon', width:'16', height:'16', viewBox:'0 0 16 16', fill:'none' },
        el('path', { fill:'currentColor', d:'M8 11L3 6L4.4 4.6L8 8.2L11.6 4.6L13 6L8 11Z' })
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Inner edit component
// Receives live post data injected by withSelect as extra props.
// ─────────────────────────────────────────────────────────────────────────────

function CitationBlockEdit(props) {
    var attributes    = props.attributes;
    var setAttributes = props.setAttributes;

    // Live post data injected by withSelect below
    var liveTitle   = props.liveTitle   || __('(Untitled post)', 'cite');
    var liveDate    = props.liveDate    || new Date().toISOString();
    var liveLink    = props.liveLink    || (blockData.siteUrl || '');
    var liveAuthor  = props.liveAuthor  || blockData.currentUserName || __('Author', 'cite');

    // Attributes
    var citationStyle  = attributes.citationStyle  || 'apa';
    var mode           = attributes.mode           || 'box';
    var showExport     = attributes.showExport;
    var showToggle     = attributes.showToggle;
    var showCopy       = attributes.showCopy;
    var showLink       = attributes.showLink;
    var customAuthor   = attributes.customAuthor   || '';
    var formats        = attributes.formats        || '';
    var excludeFormats = attributes.excludeFormats || '';
    var page           = attributes.page           || '';

    var blockProps = useBlockProps({ className: 'citepress-citation-block-editor' });

    // Build the post data object the citation builders expect
    var pubDate    = parseDate(liveDate);
    var today      = new Date();
    var postData   = {
        author:           customAuthor || liveAuthor,
        authors:          [customAuthor || liveAuthor],
        title:            liveTitle,
        siteName:         blockData.siteName || '',
        permalink:        liveLink,
        publicationDate:  formatLong(pubDate),
        publicationDateDMY: formatDMY(pubDate),
        publicationYear:  String(pubDate.getFullYear()),
        dateAccessed:     formatLong(today),
        dateAccessedDMY:  formatDMY(today),
        language:         blockData.language || 'en',
    };

    // Resolved format label
    var fmtObj    = formatOptions.find(function(f) { return f.value === citationStyle; });
    var fmtLabel  = fmtObj ? fmtObj.label : citationStyle.toUpperCase();
    var isWiki    = (citationStyle === 'wikipedia');

    // Build citation text for each mode
    var citationHTML = (mode === 'box')    ? buildCitationHTML(citationStyle, postData) : '';
    var inlineText   = (mode === 'inline') ? buildInlineText(citationStyle, postData, page) : '';

    // ── Sidebar inspector panels ─────────────────────────────────────────────
    var sidebar = el(InspectorControls, null,

        el(PanelBody, { title: __('Citation Settings', 'cite'), initialOpen: true },
            el(SelectControl, {
                label:    __('Display Mode', 'cite'),
                value:    mode,
                options:  modeOptions,
                onChange: function(v) { setAttributes({ mode: v }); },
                help:     __('Box: full widget. Inline: parenthetical. Bibliography: footnote marker.', 'cite'),
            }),
            el(SelectControl, {
                label:    __('Default Citation Style', 'cite'),
                value:    citationStyle,
                options:  formatOptions,
                onChange: function(v) { setAttributes({ citationStyle: v }); },
                help:     __('Select the default citation format to display.', 'cite'),
            }),
            mode === 'inline' && el(TextControl, {
                label:    __('Page Number', 'cite'),
                value:    page,
                onChange: function(v) { setAttributes({ page: v }); },
                help:     __('Optional page number for inline citations (e.g. "42")', 'cite'),
            })
        ),

        el(PanelBody, { title: __('Features', 'cite'), initialOpen: true },
            el(ToggleControl, {
                label:    __('Show Copy Button', 'cite'),
                checked:  showCopy,
                onChange: function(v) { setAttributes({ showCopy: v }); },
                help:     __('Allow users to copy the citation to clipboard', 'cite'),
            }),
            el(ToggleControl, {
                label:    __('Show Export Menu', 'cite'),
                checked:  showExport,
                onChange: function(v) { setAttributes({ showExport: v }); },
                help:     __('BibTeX, RIS, CSL-JSON, CFF, Dublin Core, EndNote', 'cite'),
            }),
            el(ToggleControl, {
                label:    __('Show Toggle Button', 'cite'),
                checked:  showToggle,
                onChange: function(v) { setAttributes({ showToggle: v }); },
                help:     __('Allow users to collapse / expand the citation box', 'cite'),
            }),
            el(ToggleControl, {
                label:    __('Clickable Links', 'cite'),
                checked:  showLink,
                onChange: function(v) { setAttributes({ showLink: v }); },
                help:     __('Render the permalink as a clickable link in inline citations', 'cite'),
            })
        ),

        el(PanelBody, { title: __('Format Filtering', 'cite'), initialOpen: false },
            el(TextControl, {
                label:    __('Include Formats', 'cite'),
                value:    formats,
                onChange: function(v) { setAttributes({ formats: v }); },
                help:     __('Comma-separated IDs to show, e.g. "apa,mla,chicago". Leave empty for all.', 'cite'),
            }),
            el(TextControl, {
                label:    __('Exclude Formats', 'cite'),
                value:    excludeFormats,
                onChange: function(v) { setAttributes({ excludeFormats: v }); },
                help:     __('Comma-separated IDs to hide, e.g. "wikipedia"', 'cite'),
            })
        ),

        el(PanelBody, { title: __('Override Metadata', 'cite'), initialOpen: false },
            el(TextControl, {
                label:    __('Custom Author', 'cite'),
                value:    customAuthor,
                onChange: function(v) { setAttributes({ customAuthor: v }); },
                help:     __('Override the post author (optional)', 'cite'),
            })
        )
    ); // end sidebar

    // ── MODE: inline ─────────────────────────────────────────────────────────
    if (mode === 'inline') {
        return el('div', blockProps,
            sidebar,
            el('div', { className: 'citepress-editor-preview citepress-editor-preview--inline' },
                el('div', { className: 'citepress-editor-preview-label' },
                    __('Inline Citation Preview', 'cite'),
                    el('span', { className: 'citepress-editor-badge' }, fmtLabel)
                ),
                el('p', { className: 'citepress-editor-preview-sample-text' },
                    __('\u2026as noted in recent research', 'cite'),
                    ' ',
                    el('span', { className: 'citepress-inline-preview-citation' }, inlineText),
                    ', ',
                    __('this effect is significant.', 'cite')
                ),
                page && el('p', { className: 'citepress-editor-preview-note' },
                    __('Page:', 'cite') + ' ' + page
                ),
                el('p', { className: 'citepress-editor-live-note' },
                    '\u26a1 ' + __('Updates live as you edit the post title and author.', 'cite')
                )
            )
        );
    }

    // ── MODE: bibliography ───────────────────────────────────────────────────
    if (mode === 'bibliography') {
        return el('div', blockProps,
            sidebar,
            el('div', { className: 'citepress-editor-preview citepress-editor-preview--bibliography' },
                el('div', { className: 'citepress-editor-preview-label' },
                    __('Bibliography Footnote Marker', 'cite')
                ),
                el('p', { className: 'citepress-editor-preview-sample-text' },
                    __('\u2026content of the article', 'cite'),
                    ' ',
                    el('sup', { className: 'citepress-ref-marker' },
                        el('a', { href: '#', onClick: function(e) { e.preventDefault(); } }, '[1]')
                    ),
                    ' ',
                    __('continues here\u2026', 'cite')
                ),
                el('p', { className: 'citepress-editor-preview-note' },
                    __('Add a [cite_bibliography] shortcode or block below to render the full reference list.', 'cite')
                )
            )
        );
    }

    // ── MODE: box (default) ──────────────────────────────────────────────────
    // Faithful replica of the frontend citation box. Interactive controls are
    // disabled; the citation body shows live text from the post store.
    return el('div', blockProps,
        sidebar,

        el('div', { className: 'citepress-citation-box citepress-editor-citation-box' },

            // Header row
            el('div', { className: 'citation-header' },
                el('div', { className: 'citation-title' },
                    el(QuoteIcon),
                    el('span', null, __('Cite This As:', 'cite')),
                    el('span', { className: 'citepress-editor-format-badge' }, fmtLabel),
                    showCopy && el('button', {
                        className: 'copy-button citepress-editor-btn-ghost',
                        disabled: true,
                        title: __('Copy \u2014 active on the frontend', 'cite'),
                    }, el(CopyIcon)),
                    showExport && el('button', {
                        className: 'export-button citepress-editor-btn-ghost',
                        disabled: true,
                        title: __('Export \u2014 active on the frontend', 'cite'),
                    }, el(ExportIcon)),
                    showToggle && el('button', {
                        className: 'toggle-button citepress-editor-btn-ghost',
                        disabled: true,
                    }, el(ChevronIcon))
                )
            ),

            // Citation body — real text, live from the post store
            el('div', { className: 'citation-content' },
                isWiki
                    ? el('div', { className: 'citation-output wikipedia-format' }, citationHTML)
                    : el('div', {
                        className: 'citation-output',
                        dangerouslySetInnerHTML: { __html: citationHTML }
                      })
            ),

            // Live badge
            el('div', { className: 'citepress-editor-live-badge' },
                '\u26a1 ' + __("Live preview \u2014 reflects this post\u2019s title, author & date", 'cite')
            )
        )
    );
}

// ─────────────────────────────────────────────────────────────────────────────
// Wrap the edit component with withSelect so it subscribes to the post store.
// withSelect is available since WordPress 5.0 and never breaks rules of hooks.
// ─────────────────────────────────────────────────────────────────────────────

var EditWithData = withSelect(function(select) {
    var editor    = select('core/editor');
    var core      = select('core');
    var authorId  = editor.getEditedPostAttribute('author');
    var authorObj = (authorId && core.getUser) ? core.getUser(authorId) : null;
    var post      = editor.getCurrentPost ? editor.getCurrentPost() : {};

    return {
        liveTitle:  editor.getEditedPostAttribute('title') || '',
        liveDate:   editor.getEditedPostAttribute('date')  || new Date().toISOString(),
        liveLink:   (post && post.link) ? post.link : (blockData.siteUrl || ''),
        // Author: resolved from user store once the async request returns
        liveAuthor: authorObj ? authorObj.name : (blockData.currentUserName || __('Author', 'cite')),
    };
})(CitationBlockEdit);

// ─────────────────────────────────────────────────────────────────────────────
// Block registration
// ─────────────────────────────────────────────────────────────────────────────

registerBlockType('citepress/block', {
    title:       __('Citation Box', 'cite'),
    description: __('Display a citation box with multiple formats and export options', 'cite'),
    icon:        'book-alt',
    // FIX #4: 'common' was renamed to 'text' in WordPress 5.8 (June 2021).
    category:    'text',
    keywords:    [__('cite','cite'), __('citation','cite'), __('reference','cite'), __('bibliography','cite')],
    supports:    { html: false, multiple: true },

    attributes: {
        citationStyle:  { type: 'string',  default: 'apa'  },
        mode:           { type: 'string',  default: 'box'  },
        showExport:     { type: 'boolean', default: true   },
        showToggle:     { type: 'boolean', default: true   },
        showCopy:       { type: 'boolean', default: true   },
        showLink:       { type: 'boolean', default: true   },
        customAuthor:   { type: 'string',  default: ''     },
        formats:        { type: 'string',  default: ''     },
        excludeFormats: { type: 'string',  default: ''     },
        page:           { type: 'string',  default: ''     },
    },

    example: { attributes: { citationStyle: 'apa', mode: 'box' } },

    edit: EditWithData,

    // Dynamic block: all output comes from the PHP render_callback.
    save: function() { return null; },
});
