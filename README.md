# Paulus

The theme for **Apostle of Doom**, a Twenty Twenty-Five child theme for a site presenting the Islamic case against Paul of Tarsus. The written content is drawn from *Paulus: Perosak Risalah Al-Masih* by Mohd Elfie Nieshaem Juferi (Langgam Fikir, 2025; ISBN 978-629-96135-0-3).

## Requirements

WordPress 6.7 or later, PHP 8.0 or later, and the Twenty Twenty-Five parent theme, version 1.5 or later. Paulus is a child theme (`Template: twentytwentyfive`) and cannot run without the parent: it inherits every template, pattern and style it does not override itself.

## Installation

1. Upload `paulus-2.48.0.zip` under Appearance, Themes, Add New, and activate it.
2. Select **Install site content** from the prompt shown on the dashboard and, to administrators, on the front page (the same button is under Appearance, Theme Options, Content). This creates the sections, articles, pages, menus, featured images and site icon, and sets word-based permalinks if they are still plain.
3. Review the Book and Publisher tabs. The order link points to the book's page at Langgam Fikir by default; clear it and the button emails the publisher instead.
4. On later updates, upload the new zip and open any page as an administrator; the structure brings itself up to date. Read UPGRADING.md first: it lists what an update changes on a live site and what it leaves to you.

## Site architecture

The site is arranged as a case file against Paul. Three sections (WordPress categories ordered by the term meta `paulus_order`) hold 33 articles, the English chapters of the book in parts; each article carries `_paulus_order` (reading order), `_paulus_label` (for example "Count·III", "The vision") and, for parts of a multi-part chapter, `_paulus_series`, `_paulus_series_title`, `_paulus_part_no` and `_paulus_parts`.

```
/                                  Front page: hero, the six counts, the man, answers, the witnesses
/category/the-man/                 Profile, The Roman, The vision (3 parts), The purse, The mind (2 parts), The character (3 parts)
/category/the-charges/             Count·I (1), Count·II (2), Count·III (3), Count·IV (2), Count·V (3), Count·VI (3)
/category/the-witnesses/           The early church, The brother, The Islamic tradition (3 parts), The revelation, Testimony, The region
/answers/                          Short answers to common missionary claims, each linked to its article
/the-verdict/                      The epilogue in full
/reference/                        Timeline, glossary, study guide, sources, Paul in the churches
/the-book/                         The book: details, contents, order link, publisher, author
/site-map/                         Every article and page in one list
```

The installer creates the header menu (The man, The charges, The witnesses, Answers, The book) and rebuilds it when a new version of the theme changes the structure. The footer menu is built from the site structure by `[paulus_footer_nav]`: "The case" (the three sections and The verdict) and "Reference" (Timeline, Glossary, Study guide, Sources). Every article opens on a title panel with its illustration, a breadcrumb to its section and part, the standfirst, the byline and, for parts of a series, the other parts. Below the text come the author note, the previous and next article in reading order ("Previous part" and "Next part" inside a series) with a link to the article's questions in the study guide, "More from" the section, and the book teaser. The parts of a series are listed in the title panel below 1240px and in the side rail above it, so no list of parts repeats after the text. Every article reaches a set of study questions, its own or its series'. On screens 1240px and wider a side rail beside the text lists the parts in Roman numerals and jump links to the references, the reading order and the section.

The structure keeps itself current. The manifest carries a `content_version`; whenever an administrator loads any page and that version differs from the one recorded on the site, the installer runs and adds new articles, labels, descriptions, images and menu entries. Text is refreshed only where a post still matches a version the theme shipped (`prior_hashes` in the manifest, and the hashes recorded at install); anything edited by hand is left alone.

Running the installer again is safe. It leaves existing text alone, moves articles into their current sections, restores reading order, and moves pages from earlier versions under Reference. Sections, articles and pages that a newer version has dropped stay on the site but drop out of the menu and the reading order; delete them by hand if you do not need them.

## Theme Options

| Tab | Controls |
| --- | --- |
| Book | Titles in Malay and English, bibliographic data (ISBN, OCLC, extent, format, printings), description, price, order link; the author note (links allowed), the author's website, YouTube channel, social profiles (one URL per line, platform detected from the address) and other books; the image licence used in structured data |
| Catalogue record | The book's library record for the book page: title and statement of responsibility, physical description, language, notes (one per line), subject headings (one per line), ISBNs as catalogued (one per line) and Bib ID. Edition, imprint and OCLC number come from the Book tab |
| Publisher | Name, registration number, email and website (shown on the book page); address and telephone, published in the structured data only |
| Colors | First-century ornament switch (on by default). Named scheme: Ochre (book cover, default), Vellum, Oxblood, Ink, Paper, Graphite |
| Reading | Five reading aids, each on by default: a progress bar across the top of articles; the left and right arrow keys for the previous and next article in the reading order (ignored in form fields and while the image viewer is open); a resume prompt on long articles and pages, which offers to return a reader to the section where they stopped and never moves the page by itself (position kept only in the reader's browser, for sixty days, and cleared on reaching the end); a copy-link button in the share row; and a back-to-top button. All respect reduced motion and are left out of print |
| Front page | Search title (shown before "\| Apostle of Doom"), meta description, kicker, heading, subheading, introduction, hero image, footer description and footer badges (links and images) |
| Content | Site structure installer, with the structure version on the site beside the one shipped |

The screen is styled after the site: a masthead with the site icon, the site name and theme version in the label face and "Theme Options" in Cinzel capitals, rounded cards, and the colours of the active scheme, which the screen previews as soon as another scheme is picked (`assets/js/admin.js`); the fields themselves stay on light cards in every scheme. A sticky bar keeps "Save changes" in reach. On this screen only, the admin footer reads "Paulus, built by MENJ for Apostle of Doom", with MENJ linking to https://github.com/menj (the theme header's Author and Theme URI), in place of WordPress's line, and shows the theme version in place of WordPress's.

Defaults live in `paulus_defaults()` in `inc/defaults.php`. The fields themselves, their types and their sanitising are in `inc/options-fields.php`; the tabbed screen that renders them is in `inc/options-render.php`.

## Structure

```
paulus/
  assets/css/     theme.css, schemes.css, ornament.css, fonts.css, print.css, admin.css
  assets/js/      admin.js, lightbox-init.js (editor images into the lightbox), reader.js (section marking in the article rail), print.js (Print and Save as PDF), vendor/html2pdf.bundle.min.js (0.14.0, MIT)
  assets/fonts/   WOFF2 fonts
  assets/icons/   share marks from the Minimalist Social Icons pack (facebook, x, whatsapp, telegram), the theme's email, print and search marks, a Save as PDF mark (a page with a folded corner and a download arrow, drawn in the text colour like the rest), and social/ with the full pack (45 platforms) for the footer
  assets/images/  cover, publisher logo, author portrait, seven illustrations and their variants, site icon
  build.py        builds content/articles from content/src
  UPGRADING.md    what each update changes on a live site, and what it leaves to you
  content/        manifest.php, src/ (chapter sources), articles/ (built HTML)
  inc/            defaults, options-fields and options-render, shortcodes, structure, importer, seo, figures (church photographs and their credits), search (header search, results)
  licenses/       vendor and font licence files, kept out of the served asset folders
  parts/          header, footer
  templates/      front-page, single, page, category, page-book, search, 404
```

Color schemes are pure CSS: the active scheme is written as a body class, and `schemes.css` remaps the theme.json preset variables. No inline CSS or JavaScript is emitted.

## Shortcodes

| Shortcode | Output |
| --- | --- |
| `[paulus_hero]` | Front-page hero: kicker, heading, subheading, the author's name alone, under the subtitle as on a book cover (from the Book tab), lede, buttons, portrait cutout |
| `[paulus_setup_prompt]` | Install prompt on the front page, administrators only, until content exists |
| `[paulus_parts]` | Sections with their articles; `part="the-charges"` for one, `part="current"` on section pages; `style="cards"`, `"counts"`, `"text"` or `"list"`. Overview pages show one card per series |
| `[paulus_answers_teaser count="6"]` | FAQ accordion of the first questions from the Answers page |
| `[paulus_page_hero]` | Title panel for articles, pages and section pages; on articles the byline sets the author's name alone in Cinzel capitals as a signature, with the reading time in the label face beside it, and the panel carries the share links (Facebook, X, WhatsApp, Telegram, email, plain links; marks from the Minimalist Social Icons pack, inlined by `paulus_icon()`, which accepts only plain slugs as icon names and never passes them through `sanitize_file_name()`, since that rewrites a name such as "pdf" to "unnamed-file.pdf") |
| `[paulus_article_rail]` | Side rail, sticky, 1240px and wider: the article's sections with the current one marked, the parts of the chapter, and apparatus links |
| `[paulus_series_nav]` | "This series" list of a chapter's parts. No template places it since 2.26.5; available for manual use |
| Share row (`paulus_share_links()`, in the title panel) | Not a shortcode. Print, Save as PDF, copy link (Theme Options, Reading), then the networks and email |
| `[paulus_article_nav]` | Full-width limestone band under the meander: previous and next in reading order, and the study-questions link as a pill (a question-mark roundel, the label, and the number of questions in the set, counted from the Study guide page) that fills with the accent colour on hover |
| `[paulus_related]` | "More from" the article's section |
| `[paulus_about_author]` | Nothing; retained so older customised templates neither duplicate the author card nor print the raw shortcode. The card renders automatically above the footer on every page |
| `[paulus_book]` | Book panel; `full="1"` adds the publisher's name, registration number, email and website, and makes the title the page's h1. The order button reads "Order the book" |
| `[paulus_book_teaser]` | Book panel at the foot of the front page and every article: cover, kicker, Malay title and English title, one-paragraph case for the book, format, pages, publisher, ISBN, an Order button with the price, and a link to the book page |
| `[paulus_catalogue]` | The book's library catalogue record as a list of label and value (title, edition, author, publication, physical description, language, notes, subjects, ISBN, Bib ID, OCLC), on the book page under "Catalogue record"; the contents are left out, since the page lists them |
| Answers page navigation | Not a shortcode: a `the_content` filter (`paulus_answers_navigation()`) builds, from the question headings as the page renders, a numbered index of the questions (thirteen) under the introduction (`#questions`), a link mark (#) on each question shown on hover or focus, and "All questions ↑" after each answer. The question anchors are the heading ids in the page, for example `/answers/#was-paul-an-apostle` |
| `[paulus_download file="…"]Label[/paulus_download]` | A download link to a document bundled in `assets/docs/` (plain file names only). The archive of Dale B. Martin's notes uses it for the original PDF |
| `[paulus_chart id="…"]` | A data chart from `inc/charts.php`, drawn as labelled HTML bars in the scheme's accent, values written out, with its source linked beneath. Three kinds: paired bars (two years per row), diverging bars (losses left of a centre line, gains right) and single bars on a scale with zero and marker lines. Rows the text discusses are drawn at full strength. In chapter sources, `{{chart:id}}` places one. Four charts from the Pew Research Center, on *The verdict*: share of the world's population 2010 and 2050, net switching 2010–2050, where the world's Christians live 2010 and 2050, and change in population size 2015–2060 |
| `[paulus_children]` | Child pages with summaries (Reference) |
| `[paulus_page_list]` | Every page with its summary (Sitemap page) |
| `[paulus_404_quip]` | The 404 page's heading: one of eight excuses for the missing page in Paul's own manner, chosen at random, with its source and a "Hear another excuse" link |
| `[paulus_404_links]` | Section and page links for the 404 template |
| `[paulus_link slug="..." /]` | Link to an article or page by slug, labelled with its current title. Enclose text (`[paulus_link slug="..."]text[/paulus_link]`) to set the label; add `anchor="s-section-slug"` to open a section (section ids are `s-` plus the heading, sanitised) |
| `[paulus_footer_brand]` | Footer brand block: wordmark, site description, publisher credit, social profiles |
| `[paulus_footer_social]` | The social profiles from Theme Options as a row of marks |
| `[paulus_footer_nav]` | Footer menu: The case, Reference |
| `[paulus_colophon]` | Footer credit in the tablet: a statement that the site's English text is drawn from the Malay original, then author, title, edition, imprint, ISBN and OCLC number, in three or four lines; edition and imprint follow the library record (Book tab) |
| `[paulus_figure name="…"]Caption[/paulus_figure]` | A bundled photograph as a captioned figure with its credit line, opening in the lightbox |
| `[paulus_search_toggle]`, `[paulus_search_form]`, `[paulus_search_results]` | Header search button and panel, the 404 page's form, and the results list |
| `[paulus_footer_badges]` | The badges from Theme Options, in the footer base row |
| `[paulus_article_meta]`, `[paulus_gallery]` | Retained from earlier versions |

The glossary page gains a letter index and per-letter anchors through a content filter; the footnotes list gains the `paulus-references` anchor the rail links to.

## Author card and footer

Two blocks are built from Theme Options, so they read the same wherever they appear: the author card, on articles and the book page, and the footer, on every page.

The author card: the author's portrait in a medallion (`assets/images/author-portrait.webp`, on the scheme's accent colour, or bronze with the ornament on; a monogram of his initials stands in if the file is missing), his name as a plain heading (the link to his website sits in the bio, where the first mention of his name carries it), the bio, and his other books behind a disclosure. The bio accepts links and light emphasis (`a`, `em`, `strong`, `cite`), filtered through `wp_kses`, and its links sit inside the text. A bio saved as plain text by an earlier version gets them added in place: the first mention of the author's name, his apologetics site, his YouTube channel and the publisher each link to the address set for it in Theme Options, and a bare "bismikaallahuma.org" is shown as "Bismika Allahuma". The card appears on articles and on the book page, the author's own writing, and nowhere else: listings, reference pages, search and the 404 page have nothing to attribute. It follows the text directly, after its references and before the series list, reading-order band, related links and book panel, which is where readers look for who wrote what they have just read. It is attached to the post-content block as that renders, so it also appears on templates customised in the Site Editor, which are stored in the database and never see changes to the theme's template files.

The footer itself is a brand block (the site icon and wordmark, the site description, the publisher credit and the social profiles) beside the link columns, filling the width. The wordmark is the site title from Settings → General, with the icon set under Site Identity beside it (outside the link, so only the wordmark is linked), or the bundled `site-icon-96.webp` when none is set; the description is the Theme Options footer description or, when that is empty, the WordPress tagline from Settings → General, followed in the same paragraph by the publisher credit, "Published by Langgam Fikir (Seri Kembangan, Selangor: 2025).", the place taken from the imprint on the Book tab and the year from "First published". The book is promoted by the header's The book button and the book banner under each article, so the footer carries no card of its own. The header masthead is a wordmark alone, set in the muted ink on the front page so the hero title leads.

Social profiles are one field: paste one profile URL per line and the platform is read from the address, with its mark drawn from the bundled Minimalist Social Icons pack (45 platforms, in `assets/icons/social/`, loaded only when used). A platform the pack does not cover is still linked, by name. The marks are drawn in the text colour, so they follow the scheme; a few in the pack hard-code black, and `paulus_icon()` converts that as it loads them. The same URLs join the author's `sameAs` in the structured data.

## Responsive behavior

Checked at 1440, 1240, 1024, 861, 768 and 390 pixels on seventeen page types: no horizontal scrolling at any width. The comparison tables scroll inside their own frame on narrow screens.

| Width | Layout |
| --- | --- |
| 1240px and above | Side rail beside article text |
| 1025px and above | Front-page hero sized to the screen's height as well as its width, so it and its meander border fit the first screen (checked from 1280×720 to 1727×978) |
| 861px and above | Title panel in two columns, image under the arch, minimum 560px tall; colonnade hairlines between listing and footer columns; footnotes in two columns; sources in two columns |
| 681 to 860px | Full header menu; title panel stacks, image in a 16:9 frame; hero and book panel in two columns down to 701px |
| 600 to 680px | Menu button replaces the header menu |
| 561 to 700px | Hero stacks with the portrait first; book panel stacks; cards in two columns |
| 560px and below | Single column throughout; opening paragraph set ragged with a smaller drop cap; series list stacks |

On touch screens and below 861px, small inline links (breadcrumbs, series parts, "About the book", "Full evidence", footer links) get a 44-pixel tap area without changing the layout.

## Content

The articles are full English chapters of the book, with every quotation the book gives. Long chapters are split into parts (`series`, `series_title`, `part_no`, `parts` in the manifest). Overview pages show one card per series with the number of parts; section pages list every part; the reading order runs part by part, and each article's breadcrumb shows its place in the series.

Every New Testament passage is quoted in the Greek of the SBL Greek New Testament (Holmes, 2010; text in `/sblgnt/`, apparatus sigla stripped) with the King James Version beneath it, both taken verbatim at build time and cited together in one footnote; Old Testament passages are quoted in the King James Version alone. Rabbinic, Arabic and Greek sources are given in the original script with transliteration and translation. Where the book quotes an English source in Malay translation, the footnote says "as rendered in the Malay edition of the book."

Chapter sources live in `content/src/*.txt` and are built into `content/articles/*.html` by `build.py` (footnotes numbered automatically in reading order; every bare web address linked by `linkify()`, which leaves addresses already in a tag or link alone, keeps trailing punctuation outside the link, and opens external links in a new tab with `rel="noopener"`; `{{kjv:Book c:v-v}}` expands to the passage in Greek and English, the Greek with all SBLGNT apparatus marks, ⸀ to ⸇, stripped; `{{fig:name|Caption}}` places a registered photograph). Never edit a built article by hand: the next build overwrites it. The Answers, Study guide and other reference pages have no source and are edited as HTML, with their notes numbered by hand in reading order. `build.py` resolves its paths relative to the checkout, so it runs from anywhere, and stops with a message when its sources are missing. Edit the source, run the build, and ship.

### Editorial conventions

- **Two Pauls.** A charge against Paul himself rests on the seven letters scholars accept as his (Romans, 1 and 2 Corinthians, Galatians, Philippians, 1 Thessalonians, Philemon), with Acts as a later and weaker witness. A charge against the religion may draw on all thirteen letters, because the church received them as his. Any other letter is introduced as "written in his name"; Hebrews is never called Paul's. The method is set out on the site in *How Paul came to own the New Testament*, section "Two Pauls".
- **Cross-references.** Any mention of another part of the site in running text ("set out under Count·II", "treated in The Islamic tradition", the book's title) is a `[paulus_link]`, pointing at the article, or the section, that holds the material.
- **Verification.** Every verse is taken from the KJV and SBLGNT at build time; every scholarly or historical claim added to the site is checked against a live source first, and figure licences against their Commons page.
- **Figures.** Roughly one figure for every 700 to 800 words (two from about 1,200 words, three from about 2,000), one per section at most, none in the first screen or in the closing section, and no work shown twice anywhere on the site. Each must document the claim of the section it sits in.

The installer protects hand edits field by field, against a record of what the site last received: body, title and excerpt each keep their own baseline, as do an article's category and a page's menu order and template. An edit to one never blocks an update to another, and an edited field stays edited through later releases. Where no baseline has been recorded yet, as on a site upgrading from an earlier version, a value that differs from the manifest is left alone and is never assumed stale. A sync that fails part way is not recorded as complete, so the next page load retries it.

## Search engines

The theme covers the on-page basics itself and gives way to Yoast, Rank Math, All in One SEO, SEOPress or The SEO Framework when one is active.

- Titles: every page is titled "<page title> | Apostle of Doom" in at most 50 characters (`PAULUS_TITLE_MAX`), leaving 32 for the page's own part. Each article, page and section ships with a short search title (`seo_title` in the manifest, `_paulus_seo_title` on posts, `paulus_seo_title` term meta on sections); where none is set the page title is cut at a word boundary. Search results read "Search: <terms>", the 404 page "Page not found".
- Meta descriptions: every article, page and section ships with one of 120 to 130 characters, specific to the page and closing on a quiet invitation to read on. Output is capped at 130 (`PAULUS_META_MAX`) however a description was entered. Edit both fields in the "Search title and description" box in the editor sidebar; the front page's are under Theme Options, Front page. On sync, a shipped title or description is written only where the field is empty or still holds a value the theme shipped earlier (`prior_meta` in the manifest).
- With Rank Math or Yoast active, the theme supplies its title and description to the plugin through the plugin's own filters, including the Open Graph and X tags, so the format holds on every page. Values typed into the plugin's own title and description fields are replaced.
- Open Graph and Twitter card tags on every page, with the article's illustration or the portrait.
- Canonical links on category archives and search results, which WordPress core covers only for single posts and pages.
- Structured data, following Google's list of supported types: Organization (the publisher, with its own logo) and WebSite on every page; Person for the author, with his portrait as `image` and `sameAs` for his sites and social profiles; BreadcrumbList on every page below the front page, section pages included; Article on articles, with publisher, word count, section, series position and image metadata (caption, creator, credit and a licence, the illustrations being public domain); Book on the book page, with ISBN, OCLC as a typed identifier, extent, format, language and offer, all read from Theme Options. Language follows the site's own locale. Types Google lists for other kinds of site (Recipe, Event, Job, Product, Video, Q&A forum and the rest) do not apply and are left out.
- With Rank Math active the theme adds only the types missing from Rank Math's own graph for that request, through its `rank_math/json_ld` filter, so Rank Math owns everything it manages and nothing is duplicated; in practice this means the Book, which Rank Math has no type for, a section's ItemList, the Answers FAQPage and the ImageObjects of the photographs in the text, each added as its own node. If Rank Math is publishing no graph at all, the theme prints its own. The other four plugins cannot be inspected the same way, so alongside them the theme adds the Book only.
- Word-based URLs: the installer sets `/%category%/%postname%/` if permalinks are still plain.
- Descriptive alt text on every bundled image.
- A 404 page with a random excuse in Paul's manner (see `[paulus_404_quip]`), links to every section and a search box, and an HTML sitemap at `/sitemap/` (`/site-map/` before 2.25.4, now a permanent redirect). WordPress supplies the XML sitemap at `/wp-sitemap.xml`.

## Search

A magnifier on a bronze prutah beside the header menu opens a search panel under the header on every page; it is built on `<details>`, so it works without JavaScript and on phones beside the menu button. Results (`templates/search.html`, `[paulus_search_results]`) cover articles and pages, not attachments, twenty to a page, each numbered with a Roman numeral on a prutah and carrying its section and label, its title, and an excerpt around the first match with the search terms marked; a search with no results offers the section links. The 404 page uses the same form. WordPress marks search results `noindex`.

## Structured data

The theme publishes one linked graph per page: Organization (the publisher, with a structured PostalAddress and an international telephone number, which appear in the structured data only), Person (the author, with portrait and sameAs), WebSite (with a SearchAction), BreadcrumbList, and a page node typed by the page: Article on chapters (with its illustration, series position and the book it is based on), CollectionPage with an ItemList of chapters on sections, FAQPage on Answers (every question with its accepted answer and its own `url`, the page address with the question's anchor), ItemPage with the Book on the book page, SearchResultsPage on search, WebPage elsewhere. Every photograph in the text is an ImageObject carrying its licence, credit, creator and the Commons page as `acquireLicensePage`. With Rank Math active the theme adds only what Rank Math's graph lacks, inside that graph; with other SEO plugins it adds only the Book.

## Print and PDF

`assets/css/print.css` loads with `media="print"` on every page. It removes the header, footer, rail, share row, series lists, reading-order band, related links, book panel and author note; sets black Garamond on white at 11pt with A4 margins; keeps the breadcrumb, title, standfirst and byline as a ruled head; prints the Greek and the King James text together; keeps quotations, headings and footnote entries from breaking across pages; prints external link addresses after their text; and sets the references in one column under a rule.

Every article's share row has a Print button, which opens the browser's print dialog, and a Save as PDF button. The PDF button loads html2pdf.js (bundled, 0.14.0, MIT) on first use, builds an off-screen sheet from the title panel and the text, styled by the `.paulus-printsheet` rules in theme.css, and saves it as `<slug>.pdf` on A4 at two-times resolution. html2pdf.js renders through a canvas, so the PDF is an image of the page; for selectable text, the Print button with "Save as PDF" as the printer gives a text PDF. If the library fails to load, the button falls back to the print dialog. html2canvas cannot read `color-mix()`, so the sheet rules use plain colours where the screen rules use it.

## Performance

What the theme does: fonts are subset to the Latin, transliteration, punctuation and Greek ranges the site uses (Arabic fonts untouched), which cuts the seven first-paint fonts from 665 KB to about 390 KB; the three fonts painted above the fold are preloaded so they load alongside the stylesheets; the hero portrait is served at 480, 720 and 962 pixels through `srcset`; every other image is lazy-loaded; the only front-end script is `assets/js/reader.js` (about 1 KB, deferred, articles only), which marks the current section in the rail.

What only the server can do: send `Cache-Control` headers for `/wp-content/themes/paulus/assets/`, and enable gzip or Brotli compression. Asset URLs carry the theme version as a `?ver=` query string (from `wp_enqueue_style`/`wp_enqueue_script`'s own versioning, not the file name), so a year-long lifetime only stays correct for caches and CDNs that include the query string in their cache key (most do by default; check a CDN that strips query strings from static assets). Third-party scripts (Google Tag Manager and analytics plugins) load outside the theme and weigh on the score independently of it.

## Accessibility

Text colors meet WCAG AA against the Ochre ground: ink 6.2:1, muted text 4.7:1, and small accent text (labels, breadcrumbs, links, footer headings) uses a deeper oxblood at 4.5:1 through `--t-accent-deep`; headings keep the lighter oxblood, which clears the 3:1 requirement for large text. Form fields take their background from the active scheme's own surface colour, so the text in them stays legible in the dark schemes as well. Interactive links on touch screens have 44-pixel tap areas.

## Illustrations

Seven illustrations are bundled: the cover portrait (redrawn in 2.39.0 as an engraving with a Roman gladius, the short sword of Paul's century, in place of the medieval sword with a crossguard), the decayed saint, the horned figure, the dunce, the "bastard" cap, the horned figure in the cap, and the grinning horned figure. Each carries a reading (the man himself, false sanctity, deception, folly, illegitimacy, disguise, the deceiver triumphant), and 32 variants are made from them in the same style: close-ups of the face, close-ups of the hands and scroll, dark ink and oxblood duotones, mirrored versions of the three images without lettering, and limestone duotones. Every article and page has a featured image chosen to fit its topic, and so does each section page, from the `image` key of its entry in the manifest (The man, the mirrored portrait in limestone; The charges, the BASTARD cap in limestone; The witnesses, the horned figure in limestone); the full table of pairings is in CHANGELOG.md under 2.8.1. Each image has descriptive alt text (`paulus_image_alts()`). Illustrations are copied to the media library once; the MD5 of each bundled file is recorded (`paulus_attachment_hashes`), and when a bundled file changes, the next structure sync copies the new file in under a fresh name and points the same attachment at it, so featured images update without new attachments (`paulus_refresh_attachment()`), and the installer copies them to the media library once and leaves featured images an editor sets by hand.

The front-page hero uses a cutout of the portrait with its background removed (`paul-portrait-cutout.webp`) so the figure sits on the page background in every color scheme; any image with a matching `-cutout.webp` file is used the same way when chosen as the hero image. The book page shows the cover. The site icon is a crop of the portrait's head.

## Photographs

Sixty-one photographs and reproductions (churches, paintings, manuscripts, inscriptions, sites, and a portrait of Isma'il R. al Faruqi), sixty of them from Wikimedia Commons, are bundled in `assets/images/church/` at up to 1,400 pixels with 720-pixel versions for `srcset`, with the Commons metadata in `commons-meta.json`. Fetch Commons files through the standard thumbnail widths (for example `1280px-`); Wikimedia refuses bulk requests for originals. `inc/figures.php` registers each with its alt text, author, licence and source page, and `[paulus_figure name="…"]Caption[/paulus_figure]` renders it as a captioned figure with a credit line linking the licence and the Commons page. In the chapter sources the syntax is `{{fig:name|Caption}}`. They appear in every article and on the timeline and the reference page "Paul in the churches"; the woodcut illustrations stay as the featured images. Figures fill the text column; images taller than they are wide are held to 80 per cent of the screen height, and the frame and caption shrink to the image, so no frame ever shows empty space, and each opens in a Lightbox2 viewer (bundled in `assets/vendor/lightbox2/`, 2.12.0, MIT; licence in `licenses/`), styled in `assets/css/lightbox.css`: the caption and credit beneath, "Figure 3 of 8", arrow and keyboard navigation between the figures of a page. Lightbox2 and jQuery load only on pages that contain a figure or an image linked to its file; images added in the editor (Image blocks linked to the media file) get the same frame-fits-image styling and open in the same viewer, with their caption. Without JavaScript the link opens the full-size file. Only public-domain, CC0 and Creative Commons attribution (including share-alike) licences are used, and every figure carries its credit. A figure supplied from outside Commons takes an empty `source`: its credit line then gives author and licence only, and its structured data omits `acquireLicensePage`. To add one: bundle the WebP files, add the entry to `paulus_figures()` and `commons-meta.json`, and place it with `{{fig:name|Caption}}` in the chapter source.

## The Journal

Dated entries (replies to missionary claims, notes on new sources, news of the book) live in their own content type, `paulus_journal` (`inc/journal.php`), kept apart from the case articles so they never enter the reading order, the counts or the sections. Write them under Journal in the admin menu; each takes a title, text, an optional excerpt and an optional featured image.

Addresses carry the date: an entry at `/journal/2026/09/slug/` (year and month of publication), the Journal at `/journal/`, a year at `/journal/2026/`, a month at `/journal/2026/09/`, each with `/page/2/` and so on, and the feed at `/journal/feed/`. The year and month views are Journal archives filtered by the theme's own query variables (`journal_year`, `journal_monthnum`), so WordPress does not treat them as date archives and an SEO plugin that switches date archives off leaves them working; WordPress's own date archives (`/2026/09/`) are unaffected. The address rules refresh themselves once after each theme update.

The Journal page (`archive-paulus_journal.html`) lists entries ten to a page with date, title and excerpt, then an archive of the months that have entries, then the feed; its title panel carries the Greek inscription ΤΑΥΤΑ ΓΡΑΦΩ ΥΜΙΝ ("these things write I unto you", 1 John 2:1). An entry (`single-paulus_journal.html`) shows the trail Home · Journal · month and year, the date in the byline, the share row, the text, and a band with the older and newer entries (which the arrow keys follow) and the way back to the Journal. Entries appear in site search. Theme Options, Journal, holds the title, the introduction and the search description; year and month pages have their own descriptions within 120 to 130 characters.

The theme can ship entries: those listed under `journal` in `content/manifest.php` (text in `content/articles/`, a featured image from the illustrations, a date, a search title and description) are created once, on the first sync after they appear, and their slugs recorded in `paulus_journal_shipped`, so an entry the owner edits keeps the edits and one the owner deletes is not made again. The first, "Dale B. Martin's notes on Luke and Paul, published", is dated 24 September 2026 and carries `paul-gladius-painted.jpg`.

The Journal has its own block in the header, set apart from the menu (`[paulus_journal_canton]` in `parts/header.html`, between the menu and the search): an outlined companion to the book button, the same shape, height and lettering, with a quill mark and the word Journal, and a dot while the latest entry is under a fortnight old. Below 1200 pixels it takes a round form matching the search button, and it stays in view on phones when the menu collapses. It appears once the Journal has a published entry, and it is marked as current on Journal pages. A header part customised in the Site Editor keeps its own layout; add the shortcode there to show the canton. The header menu is rebuilt for it only while it still holds exactly the links the theme placed there; a menu edited in the Site Editor is kept, and the dashboard widget then asks for the link to be added by hand.

## Dashboard widget

The WordPress dashboard carries a status panel for users who can change the theme (`inc/dashboard.php`). It gives the theme version; whether the site's content is up to date with the theme or a structure sync is pending; the number of shipped articles and pages; and every shipped article or page edited by hand, with an edit link. A page counts as edited by hand when its content differs from the shipped file and from the baseline the sync recorded, which is exactly the condition under which the sync leaves it alone.

## Where each fact lives

Each fact has one file of record. When two places disagree, this file wins and the other is stale.

| Fact | File of record | Notes |
| --- | --- | --- |
| Theme version | `style.css` header | `PAULUS_VERSION` reads it (`functions.php`); mirror it to `readme.txt` (Stable tag), the zip name in this README and `CHANGELOG.md` |
| Content version | `content/manifest.php`, `content_version` | Raise it with any change to shipped content, so the structure sync runs |
| Reading order, sections, counts and series | `content/manifest.php`, `posts` (array order) | Position in the array sets the reading order; `label`, `series`, `series_title`, `part_no` and `parts` set the counts |
| Article text | `content/src/*.txt`, built by `build.py` | Never edit `content/articles/*.html` for a built article |
| Hand-kept pages | `content/articles/*.html` without a source | Answers, Study questions, the archive of Martin's notes (verbatim, exempt from all tooling) and others |
| Earlier shipped texts | `content/manifest.php`, `prior_hashes` and `prior_meta` | Record the old hash or description whenever shipped content changes |
| Theme Options fields and defaults | `inc/options-fields.php`, `inc/defaults.php` | |
| Figures and their licences | `inc/figures.php`, `assets/images/church/commons-meta.json` | |
| Charts | `inc/charts.php` | |
| Koine Greek inscriptions | `paulus_greek_marks()` in `inc/structure.php` | |

## The notes of Dale B. Martin

Two Appendices pages carry Dale B. Martin's notes on "Luke" and Paul, sent to the site's author in early 2023 (Martin died in November 2023) and published with his permission:

- **The archive** (`dale-b-martin-luke-versus-paul.html`) reproduces the notes exactly as he left them: his headings, footnotes, outline of the signs in Acts, italics and bold labels, and his own slips of spelling and reference. The page is hand-kept and must never pass through `build.py`, the writing-style pass, footnote renumbering or `linkify()`. It opens with the provenance and the owner's covering note of 19 February 2024, set apart from Martin's text; his text sits in `.paulus-archive`; three editor's notes after his footnotes flag references that point to the wrong verse (Galatians 1:20 for 1:18, 1 Thessalonians 1:9 for 1:8, 2 Corinthians for "1 Cor" in note 3) without altering his words. His headings carry fixed anchors (`#m-chronology`, `#m-missionary`, `#m-rhetorician`, `#m-converted`, `#m-signs`, `#m-question`) for citation. The original PDF is bundled in `assets/docs/`.
- **The continuation** (`why-luke-does-not-know-pauls-letters`, built from `content/src/p-why-luke.txt`) answers the question on which the notes end, "Why does Luke not seem to know Paul's letters?", from the Greek of Acts, the three positions in published scholarship (Vielhauer; Pervo; Tyson) and Martin's own evidence.

Articles cite the archive by section. In the footer's Appendices column the two pages appear as "Dale B. Martin on Acts" and "Why Acts ignores the letters".

## Appendices

Material set beside the case sits under its own page, Appendices (`/appendices/`, an index of its children like Reference): *Paul in the churches*, the archive of Dale B. Martin's notes and the continuation that answers his question. These three moved from Reference in 2.47.0; the manifest records `was_parent`, the sync moves each existing page in place (content, edits and ID kept), and the old `/reference/…` addresses redirect permanently (301). The footer has three link columns, The case, Reference (with the sitemap) and Appendices, each headed and ruled alike, sized to their content and set as one group against the right margin, while the brand block takes the free width, so the gap from the brand to the first rule and between any two columns is the same; the Journal is linked from the header menu.

## Koine Greek

A few New Testament words are set as small inscriptions in uncials, without accents, in EB Garamond (Cinzel has no Greek). They are ornament: each sits above an English heading that stays, is marked `lang="grc"`, and carries a tooltip with its meaning and verse. None appears in navigation, buttons or the footer. The words, in `paulus_greek_marks()`:

| Where | Greek | Meaning |
| --- | --- | --- |
| Search field label | ΖΗΤΕΙΤΕ | "Seek", Matthew 7:7 |
| The man (front page and section page) | ΣΑΥΛΟΣ Ο ΚΑΙ ΠΑΥΛΟΣ | "Saul, who is also Paul", Acts 13:9 |
| The charges | ΚΑΤΗΓΟΡΙΑ | "Accusation", John 18:29 |
| The witnesses | ΟΙ ΜΑΡΤΥΡΕΣ | "The witnesses", Acts 7:58 |
| Answers (front page and page) | ΑΠΟΛΟΓΙΑ | "Defence", Acts 22:1 |
| The verdict | ΚΡΙΣΙΣ | "Judgment", John 5:22 |
| 404 page, beside "Error 404" | ΑΠΟΛΩΛΩΣ | "Lost", Luke 15:24 |

Each word was checked against the SBL Greek New Testament.

## Ornament

`assets/css/ornament.css` loads when the Ornament switch is on. It gives the site its first-century Roman Judaea character without touching colors, type or layout: parchment grain on the ground and limestone on the header and footer; Herodian meander bands in place of the plain rules; six-petal ossuary rosettes under section titles and around each article's first letter; the six counts on bronze prutah roundels above wave-scroll borders; article titles on an inscribed tablet with interpunct breadcrumbs; an oil-lamp flame on the FAQ; the footer credit in a tabula ansata; hairline colonnade rules between columns; and, on wide screens, the title-panel illustration under a Roman arch with a stone surround. Everything is drawn in CSS; no image files are involved.

## Fonts

All fonts are bundled in `assets/fonts/` as WOFF2 and declared in `assets/css/fonts.css`. Nothing loads from a third-party server. Font and vendor licence files live in `licenses/`, so only runtime assets sit under `assets/`.

| Font | Role |
| --- | --- |
| Cinzel | Roman inscriptional capitals: site title, kicker, front-page headline, section titles, count numerals, rail numerals |
| Dubidam | Article titles, FAQ questions, buttons, series and footer links |
| Sabon Next LT | Body text and the menu |
| EB Garamond | Quotations, footnotes and the book synopsis, plus the ʿ, ʾ and dotted transliteration letters Sabon Next lacks |
| Special Elite | Labels: chapter numbers, breadcrumbs, bylines, data labels, timeline dates, footer credit |
| Arslan Wessam | Arabic script (style A for regular, style B for bold) |

The free edition of Dubidam replaces its digits and most punctuation with a watermark. Its `@font-face` rules therefore cover letters only, and digits and punctuation in headings come from EB Garamond.

### Licensing

EB Garamond and Cinzel (SIL Open Font License) and Special Elite (Apache License 2.0) are free for web use; their licences ship in `licenses/`. Check the others before the site goes public:

- **Sabon Next LT** is a Monotype font. A desktop license does not cover web embedding; a web font license is required.
- **Dubidam** is the free personal-use edition from NamelaType. A site that sells a book needs the commercial license, which also removes the watermarked glyphs.
- **Arslan Wessam** is distributed through Dev-Point.com with no license file. Its embedding flag permits web use, but confirm the terms with the author.
