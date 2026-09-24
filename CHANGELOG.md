# Changelog

All notable changes to this theme are documented here. The format follows [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and the project uses [Semantic Versioning](https://semver.org/).

## [2.51.5] - 2026-09-24

### Changed

- `screenshot.png`: with the hero text starting close under the header, the portrait had stood low, with over 150 pixels of empty space above it. It is now drawn larger for the capture (640 pixels wide in place of 569), still standing on the lower border as in the design, so that Paul's head comes level with the headline and the cross rises behind him from the subtitle; the sword stays clear of the text. Centring the portrait instead was tried and rejected, since it left the cut edge of the illustration floating above the border. The site itself is unchanged.

## [2.51.4] - 2026-09-24

### Changed

- `screenshot.png`: the hero text now starts close under the header's border, 26 pixels below it against the design's 30, where the previous capture had centred it in the enlarged hero and left over 100 pixels of space above the kicker. The portrait still stands on the lower border. The site itself is unchanged; the space was only in the capture.

## [2.51.3] - 2026-09-24

### Changed

- `screenshot.png` shows the first screen only: the header and the hero, ending on the hero's meander border, with nothing of the section below. WordPress frames theme screenshots at 4:3 (1200 by 900), and the header and hero at desktop widths are wider than that (1.39 to 1.61 to 1 above the 1025-pixel breakpoint; below it the Journal canton takes its round form), so for the capture the hero is given the frame's height beneath the header, its text column centred and the portrait standing on the lower border as in the design. Nothing is scaled, cropped or distorted. Captured at double resolution from WordPress 7.1.2 running this release with the site's content.

## [2.51.2] - 2026-09-24

### Changed

- `screenshot.png`, the image WordPress shows in Appearance, Themes, is replaced with the front page as it now stands, captured at 1200 by 900 pixels (rendered at double resolution and scaled down) from WordPress 7.1.2 running this release with the site's content: the header menu with The Man, The Charges, The Witnesses and The Verdict, the book button, the Journal canton and search; the hero with the engraved gladius portrait, the author's name and the justified introduction; and the opening of The Charges beneath its inscription ΚΑΤΗΓΟΡΙΑ. The previous screenshot showed the menu of the book's order (with Answers), no Journal and no search, and the portrait before the gladius redrawing.

## [2.51.1] - 2026-09-24

### Changed

- Koine Greek is used consistently for every section and page, from one register (`paulus_greek_marks()`), where the menu, the footer bar and the pages had held separate lists that disagreed. Each section or page now has a single form: The Man ΣΑΥΛΟΣ (its page inscription had read ΣΑΥΛΟΣ Ο ΚΑΙ ΠΑΥΛΟΣ, the menu ΣΑΥΛΟΣ; same verse, Acts 13:9), the Journal ΓΡΑΦΩ (the page had read ΤΑΥΤΑ ΓΡΑΦΩ ΥΜΙΝ; same verse, 1 John 2:1); the others as before. The shorter forms are the ones a menu can carry without widening. Pages that were named in Greek in the menus but carried no inscription now carry it in their title panel: the book (ΒΙΒΛΙΟΝ, in its book panel), Privacy Policy, Terms of Use, DMCA, Contact and Sitemap. The Greek swap now also runs on the footer's The case column, the breadcrumbs and the Sitemap's page links, and the Sitemap's section rows carry their inscriptions. The footer bar's separate swap is folded into the shared one.
- Checked on WordPress 7.1.2 by crawling fifteen pages: twelve sections and pages named in Greek, each with exactly one form wherever it is named, and each the same as its own page's inscription.

### Fixed

- Before release, the check of every page's heading caught a fault introduced in this change: the breadcrumb tooltip reused the variable holding the page's heading, which emptied the section pages' headings and would have put Greek in the articles'. Fixed; every kind of page shows its own heading.

## [2.51.0] - 2026-09-24

### Added

- Main menu: on hover or keyboard focus each term is replaced at once, in the same place, by Koine Greek from the New Testament, as in the footer bar: The Man, ΣΑΥΛΟΣ ("Saul", Acts 13:9); The Charges, ΚΑΤΗΓΟΡΙΑ ("accusation", John 18:29); The Witnesses, ΟΙ ΜΑΡΤΥΡΕΣ ("the witnesses", Acts 7:58); The Verdict, ΚΡΙΣΙΣ ("judgment", John 5:22); The book, ΒΙΒΛΙΟΝ ("the book", Luke 4:17); and the Journal canton, ΓΡΑΦΩ ("I write", 1 John 2:1). The sections and the verdict take the Greek inscribed on their own pages; each word is checked against the SBL Greek New Testament. The Greek is chosen by the section or page a link points to (`paulus_nav_greek()`), so a renamed label keeps it. The English and the Greek share one cell (`paulus_greekswap()`), so the menu never shifts: measured at 819 pixels at rest and on every hover. Text links draw the double hairline beneath; the book button and the Journal canton keep their fills. The tooltip gives the meaning and verse, and screen readers hear the English only.

## [2.50.3] - 2026-09-24

### Added

- Footer bar: on hover or keyboard focus each English term is replaced at once, in the same place, by a Koine Greek word from the New Testament whose sense fits the page, each checked against the SBL Greek New Testament: Privacy Policy, ΚΑΤʼ ΙΔΙΑΝ ("privately", Mark 4:34); Terms of Use, ΟΡΟΘΕΣΙΑΙ ("the bounds set", Acts 17:26); DMCA, ΑΠΟΔΟΤΕ ("render", Matthew 22:21, "render unto Caesar the things which are Caesar's"); Contact, ΕΠΙΣΤΟΛΗ ("a letter", Acts 15:30); Sitemap, ΟΔΗΓΟΣ ("a guide", Romans 2:19). The English and the Greek share one cell, so the line never shifts width; the Greek is set in EB Garamond capitals (Cinzel has no Greek) and takes the accent colour, with the double-hairline ruling beneath. The swap is immediate, without fade or slide. The tooltip gives the meaning and verse, and screen readers hear the English only.

## [2.50.2] - 2026-09-24

### Changed

- Footer bar: set like a line of a Roman inscription. Small Roman capitals (Cinzel, 0.7rem where the label face had been 0.78rem, letter-spaced 0.14em) divided by raised interpuncts in the accent colour. On hover or keyboard focus a double hairline, the ruling of an inscribed tablet, draws out from the centre of the word and the word takes the accent; the current page keeps its ruling. Reduced motion shows the ruling without the drawing.

## [2.50.1] - 2026-09-24

### Added

- Front page: an `FAQPage` (`#faq`) for the questions in its answers section, at the owner's direction. The section records the questions and answers it shows (`$GLOBALS['paulus_front_faq']`; a block theme renders the body before `wp_head`), and the markup is built from that record, so it follows any change in the number shown. Each answer is the text the reader sees, without the "Full evidence" line and in WordPress's display typography (curly apostrophes and quotes); each question and answer links to the full, referenced answer on the Answers page, which keeps its own `FAQPage` under its own identifier. Tested on WordPress 7.1.2 with Rank Math active and without: six questions shown, six in the markup, every question and answer identical to the page; the whole site still validates against the schema.org vocabulary with no unknown term and no reference left unresolved.

## [2.50.0] - 2026-09-24

### Added

- Structured data in depth (`inc/schema-deep.php`, `paulus_schema_deepen()`). Every page and component is described with the most specific schema.org type true of it, and the page's main nodes are deepened, whether the theme or Rank Math builds them; with Rank Math the theme keeps its keys and uses its identifiers for the author and publisher. See the README's table. In short: header, footer and navigation on every page; articles as scholarly articles with their subject, source book, section, series, reading time, every footnote as a citation and every scripture block as a quotation; the Verdict's charts as Pew datasets; Martin's notes as a manuscript with its PDF; the timeline as events, the glossary as a defined-term set, the study guide as a quiz of 121 questions, the sources as a bibliography; the index pages as collections; the gallery as an image gallery; photographed works of art, manuscripts and maps as what they are; the Journal as a blog; the contact page and the publisher's contact point and policies; image metadata crediting the author on the site's own illustrations. Linked entities carry Wikipedia and Wikidata identifiers, each verified.
- Only what a page contains is declared: types that would misdescribe the content (products, events for sale, recipes and the like) are not used, since that breaks Google's structured-data policy and states something false.

Tested on WordPress 7.1.2 with the site's content, with Rank Math 1.0.279 active (as live) and without it: 22 kinds of page, 1,450 and 1,428 nodes; every type and property checked against the current schema.org vocabulary (1,017 types, 1,696 properties), no unknown term, no reference to a node absent from the page, and no duplicated page or article node. The one flag, `query-input` on the site's SearchAction, is schema.org's standard annotation for an action's input.

## [2.49.0] - 2026-09-24

### Added

- A secondary bar at the foot of the footer (`[paulus_footer_legal]`), under a hairline: Privacy Policy · Terms of Use · DMCA · Contact · Sitemap, in one line of the label face with oxblood separators, centred and wrapping on phones. Each link appears once its page is published. The Sitemap moves there from the Reference column, which now holds four links, so the three columns stand at four, four and three.
- Four pages, shipped as drafts for the owner's review (`'status' => 'draft'`, a new manifest field): Privacy Policy, Terms of Use, DMCA and Contact, 80 to 359 words each, written from what the site does (the Google tag found on the live site, server records, the reading position kept in the reader's browser, share buttons that send nothing until used, fonts served from the site), under Malaysian law and the Personal Data Protection Act 2010, with the DMCA notice and counter-notice procedure also serving for the Copyright Act 1987. Each opens with a note, hidden from readers, listing what to confirm before publishing. Checked against the house rules. Search descriptions of 125 to 127 characters.
- `[paulus_email]`: the publisher's address from Theme Options as a mail link, obscured from harvesters, so every page that gives it follows a change of address.
- The Privacy Policy becomes WordPress's privacy page if none is set. WordPress's own draft privacy page, created at install at the same address, is replaced by the theme's text only while it is an untouched draft holding WordPress's "Suggested text" template; a privacy page the owner has written or published is left alone.
- The dashboard widget lists the drafted pages awaiting review, with edit links.

Tested on WordPress 7.1.2 with the site's content: the sync creates the four drafts and registers the privacy page; while they are drafts the bar shows Sitemap alone; once published it shows all five, each page renders its title and mail link with the note hidden and no errors; an untouched WordPress privacy draft takes the theme's text, and an owner's own privacy text is kept. Content version 2.49.0.

## [2.48.3] - 2026-09-24

### Changed

- "The Verdict" is spelt the same way wherever the page is named, in the sections' title case: the page title (so its heading, breadcrumb, cards and every link that takes the page's title), its search title "The Verdict on Paul", the header menu and the footer's The case column, the Sitemap's group "The Verdict and the book", the link to it in *The religion of Paul in Malaysia and Southeast Asia*, code comments, the README and the release notes. The sync retitles the page and replaces the search title only while each still holds what the theme shipped ("The verdict on Paul" is recorded among the earlier shipped values); a title typed by hand is kept. Ordinary uses of the word stay as they are ("the man, the charges, the witnesses and the verdict" in the site description; "The verdict of the region…" beginning a sentence), as does the changelog entry that records the site's first four sections under their names of the time. Tested on WordPress 7.1.2 from a page titled "The verdict": after the sync the heading, breadcrumb, browser title, header menu, footer and Sitemap all read "The Verdict". Content version 2.48.3.

## [2.48.2] - 2026-09-24

### Changed

- Header menu: The Verdict takes the place of Answers, after the three sections, so the menu follows the case in order (The Man, The Charges, The Witnesses, The Verdict, then The book). Answers is linked from the front page's answers block and nowhere in the menus. The footer's The case column names the verdict "The Verdict", matching the menu and the sections' title case; the page's own title is unchanged.
- The menu is rebuilt once (`nav_version` 7), and only while it holds a set of links the theme placed there: the check now recognises the current set and the earlier ones with Answers, each with or without the Journal link of 2.46, compared without regard to capitals. A menu edited in the Site Editor is kept. Tested on WordPress 7.1.2 with the site's content: from the previous menu (The Man, The Charges, The Witnesses, Answers, The book) the sync builds The Man, The Charges, The Witnesses, The Verdict, The book; a menu with a hand-added link is left as it is. The test also caught a fault before release, a variable in the check that overwrote the list of sections and emptied the rebuilt menu of them, fixed before packaging. Content version 2.48.2.

## [2.48.1] - 2026-09-24

### Changed

- The Sitemap page is rebuilt as a table of contents (`[paulus_sitemap]`, new). It had repeated the front page's article cards, with excerpts, Greek lines and part counters, and then run every other page into one flat list with descriptions, Reference, Appendices, their pages and the book together. It now sets out the case first, each section as a row with its name and description beside a numbered list of its articles in reading order, each under its label (Profile, Count·II and so on), the parts of a series indented beneath the first on a hairline; then, two by two, the pages that close the case (the verdict, the Answers, the book), Reference, Appendices and the Journal's five latest entries with their dates. Titles only. The section descriptions stay justified, with hyphenation. Checked by installing the theme with its content on WordPress 7.1.2 and rendering the page: 18 articles, 15 series parts and four groups. Content version 2.48.1.

## [2.48.0] - 2026-09-24

### Changed

- The Journal leaves the header menu and takes a block of its own in the header, set apart from the menu as the owner asked (`[paulus_journal_canton]`, placed in `parts/header.html` between the menu and the search). It is the book button's companion: the same shape, lettering and height (line height matched to it, measured at 39 against 40 pixels), outlined in the accent colour where the book is filled, with a quill mark drawn on the icons' 24-point grid, and a dot while the latest entry is under a fortnight old; the title gives the date of the latest entry, and the accessible name says "new entry" while the dot shows. It fills with the accent colour on hover, focus and Journal pages. Below 1200 pixels it takes a round form the size of the search button, so the menu stays on one line at 1024 pixels, and on phones it stays in view beside the search when the menu collapses. It appears once the Journal has a published entry. Checked on the live header at 1440, 1280, 1024, 700 and 390 pixels.
- The menu builder no longer adds a Journal link, and the menu is rebuilt once (`nav_version` 6), only while it holds the theme's own links, to drop the link 2.46 added. The dashboard's note about adding the link by hand is withdrawn. Content version 2.48.0.

## [2.47.6] - 2026-09-24

### Fixed

- The section names are title case again, The Man, The Charges and The Witnesses, as set at the owner's request in 2.22.2 and lost in a later release. The manifest holds the title-case names; on the next sync an existing section whose name differs from the manifest's only in capitals is renamed, and a name the owner has changed in any other way is kept. Because the names are the sections themselves, the header and footer menus, the front-page section headings, the section title panels and the breadcrumbs all follow.
- Header menu: a section link whose stored label is the section's name in other capitals now shows the section's current name as the page renders, so a menu built before the rename reads The Man at once; a label written differently by hand is kept. The check that decides whether the theme may rebuild the menu (to add the Journal) now compares labels without regard to capitals, so the capital change alone does not count as a hand edit. Content version 2.47.6.

## [2.47.5] - 2026-09-24

### Fixed

- Footer on phones and small tablets: the link columns kept the right alignment meant for wide screens after wrapping below the brand, so they began about a third of the way across. Below 900 pixels they now take the full width and start at the left margin, level with the description (measured at 390 and 820 pixels wide on the live front page). Wide screens are unchanged.

## [2.47.4] - 2026-09-24

### Changed

- Footer: the site description and the publisher credit are one paragraph, as the owner set it: "A case file against Paul of Tarsus: the man, the charges, the witnesses and the verdict, drawn from the book by Mohd Elfie Nieshaem Juferi. Published by Langgam Fikir (Seri Kembangan, Selangor: 2025)." The place comes from the imprint on the Book tab and the year from "First published", so the line follows the book's details; Langgam Fikir links to the publisher's site. The separate publisher line and its styles are removed.

## [2.47.3] - 2026-09-24

### Removed

- The book card in the footer's brand column (added in 2.39.1): a cover thumbnail with "The book", the Malay title and "About the book". It repeated the header's The book button and the book banner under every article. Its markup, its styles and its thumbnail (`book-cover-160.webp`, used nowhere else) are removed; the brand column now holds the wordmark, the description and the publisher line.

## [2.47.2] - 2026-09-24

### Fixed

- Footer: with the link columns sized to their content, the free width fell between the brand block and the first column, a gap of 64 pixels against 20 between the columns. The brand block now takes the free width (its description and publisher line no longer capped at 46 characters, the book card at 30rem), and the row's horizontal gap is the columns' own half-gap. Measured on the live front page, every gap from text to the next rule is now the same: 20 pixels at 1440 wide and 15 at 1024; at 820 the link columns wrap below the brand as before.

## [2.47.1] - 2026-09-24

### Fixed

- Footer: with the three link columns at equal widths, the short labels of Reference left a wide empty stretch before the Appendices rule. The columns now take their own width and sit as one group against the right margin, so every gap is the same and each rule falls midway between the text either side (measured on the live front page: 20 and 21 pixels at 1440 wide, 15 and 16 at 1024). "Why Acts ignores the letters" now fits on one line at desktop width.

## [2.47.0] - 2026-09-24

### Added

- Appendices, a section of its own for material set beside the case: a new page, `/appendices/`, indexing its children as Reference does, with its own title, description (126 characters) and illustration. *Paul in the churches*, *“Luke” versus Paul: the notes of Dale B. Martin* and *Why Luke does not seem to know Paul's letters* move there from Reference, so their trails read Home · Appendices · … and their addresses move to `/appendices/…`.
- Moving a page between parents (`was_parent` in the manifest): the sync moves the existing page in place, keeping its content, edits and ID, and `paulus_redirect_old_page_slugs()` sends the old address to the new one with a permanent redirect.

### Changed

- Footer: a third link column, Appendices, headed and ruled like the others and linked to its page, so Reference holds the timeline, glossary, study guide, sources and sitemap. The three columns take equal widths, and the link area takes three parts of the row to the brand column's one, so the columns stand level with each other at desktop and tablet widths (checked at 1440 and 1024 pixels on the live front page). The short footer labels are shared by all columns. The Journal link leaves the footer, where it had lengthened Reference to six links, and remains in the header menu. Content version 2.47.0.

## [2.46.2] - 2026-09-24

### Fixed

- Journal page: an entry's title in the list took the justification of article text, spreading its words across the line, and the top margin of article headings, leaving a gap under its date. List titles are now set flush left, directly under the date.
- Journal entry: the date and reading time wrapped as two separate pieces under the author's name, leaving a separator dot at the start of a line. They now form one line of their own beneath the name, "24 September 2026 · About 3 minutes". Both found by rendering the two pages on the live site's layout with the new stylesheets.

## [2.46.1] - 2026-09-24

### Added

- The Journal's first entry, "Dale B. Martin's notes on Luke and Paul, published", dated 24 September 2026, at `/journal/2026/09/dale-b-martin-notes-published/`. It records how the notes came to the author in early 2023 and their publication with Martin's permission; states their argument, with two quotations checked against the archive ("cannot responsibly be harmonized", "It is fiction."); gives the site's answer to the question on which they end; points to the six places where his findings now stand, the Answers question included; and states plainly that Martin did not share the site's conclusions and is cited only for what he argued. 522 words, checked against the house rules. Search title "Dale B. Martin's notes published"; search description of 127 characters.
- Its featured image, `paul-gladius-painted.jpg`: the painted rendering of the gladius portrait from the owner's set, distinct from the engraved version used as the site's portrait since 2.39.0, registered with alt text and offered in the illustration picker.
- Shipped Journal entries (`journal` in the manifest), created once by the sync with their image, date and search fields, and never recreated or overwritten afterwards (`paulus_install_journal()`, `paulus_journal_shipped`). Content version 2.46.1.

## [2.46.0] - 2026-09-24

### Added

- The Journal, for dated entries (replies to missionary claims, notes on new sources, news of the book), as its own content type, `paulus_journal`, so entries never enter the reading order, the counts or the sections. Addresses carry the date: entries at `/journal/2026/09/slug/`, the Journal at `/journal/`, years at `/journal/2026/`, months at `/journal/2026/09/`, with pagination and a feed. The year and month views filter the Journal by the theme's own query variables, so WordPress does not treat them as date archives and Rank Math, which sends date archives to the front page, leaves them working; WordPress's own date archives are unaffected. The address rules refresh themselves once after each theme update. Tested: every kind of Journal address routes to its view, entry addresses take the entry's date, and articles, pages, sections and `/2026/09/` are untouched.
- The Journal page lists entries ten to a page (date, title, excerpt, link), then an archive of the months that have entries with their counts, then the feed; its title panel carries ΤΑΥΤΑ ΓΡΑΦΩ ΥΜΙΝ ("these things write I unto you", 1 John 2:1, checked against the SBL Greek New Testament). Year and month pages are titled "Journal: 2026" and "Journal: September 2026", with trails and search descriptions of their own, each within 120 to 130 characters.
- An entry shows the trail Home · Journal · month and year, the date in the byline, the share row, the text, a band with the older and newer entries (followed by the arrow keys) and the way back, and the book teaser.
- Theme Options, Journal: title, introduction and search description. Journal entries appear in site search, labelled with the Journal and their date. The footer's Reference column links the Journal; the header menu gains it between Answers and The book, rebuilt only while the menu still holds exactly the theme's links, so a menu edited in the Site Editor is kept (the dashboard widget then asks for the link to be added by hand). The dashboard widget counts published entries and links to a new one.

## [2.45.8] - 2026-09-23

### Fixed

- The header menu linked the three sections as /category/the-man/, /category/the-charges/ and /category/the-witnesses/ after Rank Math had been set to strip the category base. The installer writes each menu link's full address into the navigation when it builds the menu, and those three were recorded before the category base was removed; every address built as a page renders (footer, cards, breadcrumbs, structured data) already followed Rank Math. On the live site these three were the only such addresses; the old form redirected (301) to the clean one. Menu links that name their section or page by ID now take that item's current address as the page renders (`paulus_nav_link_live_url()`), so the menu follows Rank Math or any later permalink change; custom links are left as they are. The stored menu itself is not rebuilt, so changes made to it in the Site Editor are kept.

## [2.45.7] - 2026-09-23

### Reverted

- The 1.5 line spacing of 2.45.6 is withdrawn at the owner's direction, and every value returns to what it was in 2.45.5: article text 1.75, the base in theme.json 1.65, and the site's other running text at its own earlier values (footnotes 1.55, captions 1.45, the front-page introduction 1.65); print and the saved PDF 1.45 for text and 1.35 for notes. The justification of 2.45.5 stays.

## [2.45.6] - 2026-09-23

### Changed

- Line spacing is 1.5 for all running text. Article text had been 1.75, the site's base (theme.json) 1.65, the front-page introduction 1.65, footnotes 1.55, captions 1.45, and print and the saved PDF 1.45 for text and 1.35 for notes; every one is now 1.5. The rule covers the same blocks of running text as the justification rule of 2.45.5, and the base in theme.json follows. Headings keep their tighter leading (the article title measures 1.06). Measured on the live site by injecting the rules: every kind of running text reads 1.50.

## [2.45.5] - 2026-09-23

### Changed

- All running text is justified, with hyphenation to keep the word spacing even. Article text was already justified; the rule now covers every block of running text on the site: footnotes, figure captions, scripture and other quotations, table cells, the catalogue record, chart sources and the Answers index; the front-page introduction (set ragged-left in 2.27.3), card excerpts and section descriptions; title-panel standfirsts; the author's bio and the book panels; search snippets; the footer description, publisher line and colophon; and the 404 page. On phones the first paragraph of an article, which had been kept ragged beside the drop cap, is justified too. Blocks centred by design (article cards, section headings, the centred section panel, the stacked teaser and author card on phones) keep their centred look: their lines are justified and the last line centred. Print and the Save as PDF sheet are justified with hyphenation, where they had been ragged-left. Headings, buttons, menus, labels, chart bars and right-to-left Arabic keep their own alignment. Checked on the live site by injecting the rules and reading the computed alignment of each kind of text.

## [2.45.4] - 2026-09-23

### Changed

- *How Paul came to own the New Testament*, "The gospels that were set aside": at the owner's direction, the identification of Luke as Paul's personal physician is restored, stated as the tradition ("Luke, traditionally identified as the author of the Gospel of Luke and of Acts, was Paul's personal physician"), with the owner's account that he brought Paul's teachings into his gospel and exalted Paul in Acts. Dale B. Martin's judgment that the traditional ascription is almost certainly mistaken moves from the body to the note, with the link to his notes, so the article stays consistent with the archive. Content version 2.45.4.

## [2.45.3] - 2026-09-23

### Changed

- *How Paul came to own the New Testament*, "The gospels that were set aside": at the owner's direction, the account that the gospels set aside come from the records of the followers of ʿĪsā ibn Maryam, the twelve apostles foremost, and are the gospels closest to the original Injīl, never yet infiltrated by the teachings of Paul, is restored. The description of those texts added in 2.45.2 (Coptic translations of the second and third centuries, mostly Gnostic, standing no nearer to him than the four) is withdrawn with it, so the section does not contradict itself. The restored note, which said "most" of the gospels named were found at Nag Hammadi, now names those that were (Thomas, Philip, Truth) and where the Gospels of Mary and Judas survive, from Scholer. The corrections of 2.45.2 on Luke and on the four Gospels stand, as does the paragraph on the lost gospels of the Jewish followers who kept the law. Content version 2.45.3.

## [2.45.2] - 2026-09-23

### Fixed

- *How Paul came to own the New Testament*, "The gospels that were set aside": the section said that the gospels found at Nag Hammadi came from the records of the twelve apostles and were "the gospels closest to the original Injīl"; that the four canonical Gospels "teach the false doctrines"; and that the author of Luke and Acts was Paul's personal physician. The first is contrary to the evidence, since those texts are Coptic translations of Greek writings of the second and third centuries, most of them Gnostic; the second sat at odds with the rest of the site, which quotes the Gospels as a record of ʿĪsā ibn Maryam's words; the third contradicted Dale B. Martin's notes, now published on the site. The section now gives the Nag Hammadi find and the Berlin and Tchacos codices as they are (David M. Scholer, *Christian History* 96, 2007); the lost gospels of the Jewish followers who kept the law, known only from quotations by their opponents (Goulder, p. 108); the Qurʾān's statement that ʿĪsā ibn Maryam was given the Injīl, which none of these books is (5:46); and the anonymity of Luke and Acts, with the traditional ascription to Paul's companion and Martin's judgment against it. The point of the section stands: the churches that followed Paul chose which gospels to keep. Content version 2.45.2.

## [2.45.1] - 2026-09-23

### Added

- Michael Goulder, *St. Paul versus St. Peter: A Tale of Two Missions* (Westminster John Knox, 1995), cited from the owner's copy with page numbers checked against the text, and introduced at each first use as Goulder presents it, his own reading and a revival of Ferdinand Baur's proposal of 1831 (p. 194). *The Jerusalem Council*, "Open conflict with Peter": by Paul's own account the Jewish believers and Barnabas followed Peter at Antioch, and Goulder concludes that "the Peter party, the Petrines, had won the round" (p. 3); the first recorded dispute between Paul and the disciples ended in his defeat. *How Paul came to own the New Testament*: the New Testament "was selected by the winning mission, that is the Paulines" (p. x). *The early church*, "The Ebionites": the Ebionites traced their descent to the Jerusalem church, and "there never was such a person as Ebion" (p. 70); the Aramaic churches were driven out as "heretical sects" when the Paulines won in the second century (p. 108); their doctrine held ʿĪsā ibn Maryam "a straightforwardly human being" and gave the cross no significant part in the good news, the teaching Paul opposed (pp. 109–10); the page's notes are renumbered, nine in all. *Why Luke does not seem to know Paul's letters*: Goulder reaches the site's answer by another road, "it was Luke who invented the united virginal church theory, and Acts is his steady and skilful attempt to paper over all the cracks" (pp. x–xi). Goulder's view that Paul went "to the synagogue first" (p. 6), which follows Acts and which Martin's notes reject, is not used. Content version 2.45.1.

## [2.45.0] - 2026-09-23

### Added

- Reading aids, adapted from the owner's Book WP theme and switched in a new Theme Options tab, Reading (all on by default): a progress bar across the top of articles, measured over the article text; the left and right arrow keys for the previous and next article in the reading order, ignored in form fields, with modifier keys and while the image viewer is open, and announced on the links with `aria-keyshortcuts`; a resume prompt on long articles and pages (over two and a half screens) that offers to return to the section where the reader stopped, naming it, and never moves the page by itself and withdraws once the reader is at the saved place by any route (a browser that restores the position on reload or Back shows no prompt), with the position kept only in the reader's browser for sixty days and cleared on reaching the end; a copy-link button in the share row, with a matching single-colour link icon, a confirmation announced to screen readers and a fallback for browsers without the clipboard interface; and a back-to-top button after a screen and a half, which returns focus to the main content. The reader script now loads on pages as well as articles. All aids respect reduced motion, sit clear of the admin bar and the phone's safe areas, and are left out of print.
- Dashboard widget, also adapted from Book WP: the theme version, whether the content is up to date or a structure sync is pending, the number of shipped articles and pages, and each shipped article or page edited by hand (with an edit link), judged by the same test the sync uses to leave a page alone.
- README: "Where each fact lives", a file-of-record table after Book WP's single-source-of-truth document.

### Changed

- The theme version is kept in one place: `PAULUS_VERSION` now reads the `style.css` header, where it had been written again by hand in `functions.php`.
- The counts no longer follow the book's chapter order. *The letters of a man*, which tests Paul's claim to revelation against the ordinary content of his letters, moves from Count III to open Count IV, "Revelation or borrowing?", now in three parts (the letters, the witnesses and angels, the rabbinic borrowings); its close now hands on to the witnesses. Count III keeps *Adapting the message to the pagans* and *Seven doctrines* and is retitled "The message remade", since only one of its articles dealt with the law. Three links that named *The letters of a man* as Count·III now read Count·IV, and the study guide's Count 3 heading follows the new title. Content version 2.45.0.

## [2.44.1] - 2026-09-23

### Changed

- Reading flow. The articles were written in the book's order, and eight openings and closings still pointed to it. Each now follows the site's reading order: *A self-appointed apostle* hands on to the third count by name, where it had promised "the next articles"; *The persecutor and his guilt* opens from the articles before it (Tarsus, the claim of apostleship, the purse), where it had claimed that Paul's doctrine was already examined; *The letters of a man* ends by introducing the seven doctrines, where it ended on a dangling "These doctrines"; *Twisting the scriptures* hands on to Count VI, where it had promised that "the next articles" would examine the sources of Paul's teaching, which Count IV had already done; *How Paul came to own the New Testament* opens from Count IV's question about those sources, where it had answered Count III; *No prophet between* opens from the witnesses before it, where it had recalled the psychological portrait of another section; *At the crossroads* (Count I) refers to the Paul of the first section, where it had introduced him afresh; the epistle notes that on the site it closes the witnesses before the verdict.
- The witnesses are reordered to follow their own logic: *James* first (it opens "The first witness against Paul"), then *The early church*, which ends by handing on to the Islamic verdict; the Islamic tradition in its three parts; *Malaysia and Southeast Asia*; *The Qurʾān as witness*, whose close ("Every other witness on this site can be argued with") now ends the argued witnesses; and the epistle as the coda before the verdict.
- *The Damascus road*, "A Yale scholar's verdict": a sentence leads from Martin's published book to his notes on this site.

Content version 2.44.1.

## [2.44.0] - 2026-09-23

### Added

- Reference, "“Luke” versus Paul: the notes of Dale B. Martin": the notes the late Dale B. Martin of Yale sent to the site's author in early 2023, setting out why the Paul of Acts and the Paul of the letters cannot be reconciled, published with his permission. They are reproduced exactly as he left them, recovered from the PDF by layout (paragraph indents, headings in bold, footnotes by their line spacing, italic and bold runs, superscript markers), with words run together at page joins separated again and nothing else changed; his spelling and reference slips stand. The page opens with provenance and the owner's covering note of 19 February 2024, kept apart from Martin's text; three editor's notes after his footnotes give the correct verses for three references. His headings carry fixed anchors for citation, and the original PDF is offered for download (`[paulus_download]`, new).
- Reference, "Why Luke does not seem to know Paul's letters": the continuation, answering the question on which the notes end. From the Greek of Acts: *epistolē* occurs five times and never for a letter of Paul's; *apostolos* occurs in 29 verses and includes Paul only twice, jointly with Barnabas at Lystra (14:4, 14), against the test of Acts 1:21–22; the Antioch quarrel is replaced by a parting over John Mark; the collection shrinks to "alms to my nation" (24:17). The three scholarly answers, each verified: Vielhauer on the gap between the portraits; Pervo on an author who knew and mined the letters, writing about 115; Tyson on Luke-Acts as an answer to Marcion, 120–125. The site's answer: Acts departs from the letters by pattern, falling silent where they press Paul's claims and adding where they would forbid its portrait, which is the course of a writer who knew them.
- Martin's findings in six articles, each citing the archive by section: *A self-appointed apostle* (Galatians 1:22, "unknown by face", against Acts 8:3); *The Jerusalem Council* (the apostle to the Gentiles against the synagogue-first mission, with Thessalonica as the test case, 1 Thessalonians 1:9 against Acts 17:1–4); *The Roman* (the orator of Acts against "his speech contemptible", 2 Corinthians 10:10, 11:6); *No prophet between* (Paul's call in the words of Jeremiah 1:5 and Isaiah 49:1, Galatians 1:15–16); *The church that followed Paul* (the marks of a new religion in Acts 11:26, 14:23, 20:7, 24:5, 14); *How Paul came to own the New Testament* (the anonymity and date of Acts, 80 to 150 CE).
- Answers, a fourteenth question, "Doesn't the Book of Acts confirm Paul's story?", leading to the continuation; the page's description reads "Fourteen claims…". Study questions: one on Galatians 1:22 against Acts (Count 2).

Content version 2.44.0; the two pages are created, and the eight changed pages refresh, on the next structure sync.

## [2.43.1] - 2026-09-23

### Added

- *Seven doctrines*, "The law abolished and cursed": after Paul's statements that Christ is the end of the law (Romans 7:6, 10:4; Galatians 3:24–25), Matthew 5:19 is set in Greek and English, the saying that whoever breaks the least of the commandments and teaches men so shall be called least in the kingdom of heaven, with a sentence drawing the contrast: Paul told his congregations in writing that they were delivered from the law, and the letters became scripture. The site had cited Matthew 5:17 and 5:18 but never 5:19.
- Study questions, Count 3: one on Matthew 5:19 and the teacher who told his congregations they were delivered from the law.

Content version 2.43.1.

## [2.43.0] - 2026-09-23

### Added

- Answers, a thirteenth question: "Doesn't the Bible say that all scripture is inspired by God?" The proof text, 2 Timothy 3:16, is read with the verse before it, which names the scripture meant as the holy scriptures Timothy had known "from a child", the Jewish scriptures, since no Gospel existed in his childhood; and 2 Timothy is one of the Pastoral letters most scholars assign to Paul's school (Dale B. Martin, Lecture 19, the source the site already cites for the Two Pauls method). Its "Full evidence" link opens *How Paul came to own the New Testament* at "Two Pauls: the man and the canon". Notes 24 to 26; the index, the link marks and the FAQPage data take the question up automatically. The Answers page's search description reads "Thirteen claims…"; the old wording is recorded so an untouched description updates on sync.
- *Seven doctrines*, "Crucifixion and resurrection as the price of sin": a paragraph setting Mark 10:45, where the Son of man gives his life "a ransom for many", beside Luke 22:24–27, where Luke, writing with Mark before him, tells the same quarrel over greatness and ends the saying at service, "I am among you as he that serveth". One Gospel writer kept the saying on service and set the ransom aside.
- *How Paul came to own the New Testament*, "Two Pauls": the verse most often cited for the inspiration of the whole Bible stands in 2 Timothy, one of the letters written in Paul's name, and the verse before it names the Jewish scriptures.
- Study questions: one on the ransom saying (Count 3) and one on 2 Timothy 3:15–16 (Count 5).

Both points were drawn from a public debate, which is not cited; each rests on the biblical text, checked against the KJV and the SBL Greek New Testament. Content version 2.43.0.

## [2.42.1] - 2026-09-23

### Changed

- Answers, "Did Peter endorse Paul's letters?": the answer cited the United States Conference of Catholic Bishops' introduction to 2 Peter (*New American Bible, Revised Edition*), whose page refuses automated requests; checked against a saved copy, both claims drawn from it stand. The answer now also gives what the introduction says further: that many date the letter to the first or even the second quarter of the second century; that it counts the very passage on Paul (2 Peter 3:14–16) among the reasons, since the passage presupposes a known collection of Paul's letters already in dispute; and that some local churches still excluded the letter from the canon in the fifth century.
- Answers, "Were the other gospels left out…?": the sentence on 2 Peter, doubted for centuries and placed by many scholars last of the New Testament writings, had no note; it now cites the same introduction (note 22), and the notes after it are renumbered, 23 in all. Content version 2.42.1.

## [2.42.0] - 2026-09-23

### Added

- Answers page navigation. Each question already had an anchor (its heading id, for example `/answers/#was-paul-an-apostle`), but nothing exposed it. Under the introduction a numbered index now lists the twelve questions, in two columns on wide screens, each linking to its answer; each question heading carries a link mark (#), shown on hover or keyboard focus and faintly on touch screens, for readers to copy; and each answer ends with "All questions ↑" back to the index. All three are built from the headings as the page renders (`paulus_answers_navigation()`), so an edited copy of the page gets them too, and all are left out of print.
- FAQPage structured data: the Answers page already published all twelve questions with their accepted answers; each Question now also carries its own `url`, the page address with the question's anchor, so a search result can open at the answer.

## [2.41.0] - 2026-09-23

### Added

- Data charts. `inc/charts.php` draws charts as plain HTML bars in the active scheme's accent, each value written on its bar (so the numbers reach screen readers and print), with the source linked beneath; no script and no images. Three kinds: paired bars for two years, diverging bars for gains and losses, and single bars on a scale with a zero line and a marker line. Rows the text discusses are drawn at full strength and the rest muted. On narrow screens each label sits above its bars. `build.py` places a chart with `{{chart:id}}`.
- *The Verdict*: the Pew material moves from the closing section on colonial legitimation into a section of its own, "What the demographers project", with a chart after each paragraph, redrawn from the figures in Pew's own charts and printed to the decimals Pew prints: share of the world's population in 2010 and 2050 (Christians 31.4 and 31.4 per cent, Muslims 23.2 and 29.7, the unaffiliated 16.4 and 13.2, Hindus 15.0 and 14.9, Buddhists 7.1 and 5.2); net change through religious switching, 2010–2050 (the unaffiliated +61.49 million, Muslims +3.22, Christians −66.05, and the smaller groups); where the world's Christians live in 2010 and 2050 (sub-Saharan Africa 23.9 and 38.1 per cent, Europe 25.5 and 15.6, the Middle East and North Africa 0.6 and 0.6, and the other regions); and change in population size, 2015–2060 (Muslims +70 per cent, Christians +34, against world growth of 32, marked by a dashed line). Content version 2.41.0.

## [2.40.1] - 2026-09-23

### Added

- *The Verdict*: a second paragraph of figures from two further Pew sources, each checked against the live page. From the report's chapter on Christians (2 April 2015): in 2010 a quarter of the world's Christians lived in Europe and less than 1 per cent in the Middle East and North Africa, where Christianity began; by 2050 Europe's share falls to about 16 per cent and sub-Saharan Africa's rises from 24 to 38 per cent. From Michael Lipka and Conrad Hackett's update of 6 April 2017: between 2015 and 2060 the world's population grows by 32 per cent and the number of Muslims by 70 per cent, from 1.8 billion to nearly 3 billion (24.1 to 31.1 per cent); Muslim women average 2.9 children against 2.6 among Christians; the median Muslim age in 2015 was 24 against 32 for non-Muslims; and switching costs Christianity some 72 million adherents over the period, with no net loss to Islam. The second note points to Pew's analysis of 10 June 2025 for its latest figures. Content version 2.40.1.

## [2.40.0] - 2026-09-23

### Fixed

- Web addresses in footnotes were plain text: 35 addresses on 15 pages, among them the bit.ly and pewrsr.ch short links, the Yale lecture page, the USCCB Bible page and the Pew reports. The build script now links every bare address in the finished page (`linkify()`), leaving addresses already in a tag or link alone and keeping trailing punctuation outside the link; external links open in a new tab with `rel="noopener"`. Every article was rebuilt and the two hand-kept pages with addresses, Answers and Sources, were passed through the same step; a comparison against the previous build confirms that links are the only change. Long addresses may now break anywhere, so a footnote never runs off a phone screen. Content version 2.40.0.
- Every address was resolved. The short links all lead where their footnotes say, with one exception: `https://pewrsr.ch/4mTed0z`, cited in *The Verdict* as the report's "United States" chapter, led to the report's overview; Pew has no such chapter, and its United States figures sit in the North America chapter. The footnote now cites "Projected Religious Population Changes in North America," 2 April 2015, at its full address.

### Known

- `https://bit.ly/4kw6wv8`, in *The religion of Paul today*, leads to a Charisma article on red heifers that has been removed (404) and is not held by the Wayback Machine. The link is kept, since the footnote records what was cited; a replacement source is needed. Pages at academia.edu, bible.usccb.org and theendtimenews.com refuse automated requests (403) but open in a browser.

## [2.39.3] - 2026-09-23

### Added

- *The Verdict*: a paragraph before the closing declaration gives the figures behind it, from the Pew Research Center's "The Future of World Religions: Population Growth Projections, 2010–2050" (2 April 2015; Conrad Hackett, lead researcher): Christianity at 2.2 billion (31 per cent) and Islam at 1.6 billion (23 per cent) in 2010; near parity by 2050, Muslims at 2.8 billion and Christians at 2.9 billion; equal shares of about 32 per cent around 2070; about 40 million switching into Christianity by 2050 against 106 million leaving, the largest net loss of any group; and Europe's Christians falling from 553 million to 454 million, from three-quarters of the population to less than two-thirds. Every figure was checked against the live report. The first footnote records Pew's 2025 note that the 2010 baseline has since been revised and that no projections beyond 2020 have yet been published. Content version 2.39.3.

## [2.39.2] - 2026-09-23

### Changed

- *From Ibn Ḥazm to al Faruqi*: the section heading "Al-Attas" reads "Syed Muhammad Naquib al-Attas", in the article and in its contents rail. The section's anchor becomes `#s-syed-muhammad-naquib-al-attas`; nothing on the site linked to the old one. Content version 2.39.2.

## [2.39.1] - 2026-09-23

### Changed

- Footer brand column: it held the wordmark, the description and the publisher line and then stopped, well short of the link columns beside it, leaving a dead block. Below a hairline it now carries a small card for the book the site is drawn from: the cover (a 160px thumbnail, 8.6 KB) beside "The book", the Malay title in italic and "About the book →", the whole card linking to the book page. On hover the cover lifts slightly and the link underlines; the lift is off for readers who ask for reduced motion. On the live footer the column now ends level with the Reference column.
- The first link column (The case) is ruled off from the brand column by the same hairline that already parted Reference from The case, so every footer column is divided alike.

## [2.39.0] - 2026-09-23

### Changed

- The cover portrait is redrawn, from the owner's 8K engraving (`paul-gladius-historical-engraved-8k.png`, chosen from six versions for its engraved line and historical sword). Paul now holds a gladius, the Roman short sword of the first century, with its round pommel, in place of the medieval sword with a crossguard. The composition matches the old portrait, so every variant keeps its framing, measured by template matching against the old files: the face crop (223, 38 to 843, 658), the hands band (the lower 614 rows), the mirror, and the ink, oxblood and limestone duotones (each fitted to its old pair to within 2 to 4 levels per channel), with the mirrored limestone of 2.38.0. The front-page hero cutout is rebuilt at 962 by 1024 with the paper removed and the cross kept, including the pocket of paper enclosed by the cross arm, the sword and the robe; the 480 and 720 sizes follow. The bundled fallback site icon (512 and 96 pixels) is recropped from the new head. Alt text describes the engraving and the gladius.

### Added

- Featured images refresh in place when an illustration is redrawn. Imports now record the MD5 of each bundled file; when it changes, the structure sync copies the new file into the uploads folder under a fresh name (so no cache serves the old one), points the existing attachment at it and regenerates its sizes. Imports made before hashes were kept are refreshed once if the illustration is one redrawn since (`paulus_replaced_images()`, holding the old files' MD5s), and otherwise simply recorded. Content version 2.39.0 so the sync runs.

## [2.38.0] - 2026-09-23

### Added

- Section pages carry featured images in their title panels, which now take the two-column layout of every other page. All 42 existing illustrations were already in use, so three new variants are made in the collection's limestone duotone (fitted to the existing pair, portrait and portrait-stone, to within about 2 levels per channel), each following the readings the illustrations carry: The man takes the portrait, mirrored (the man himself); The charges takes the cap lettered BASTARD (illegitimacy, the self-appointed apostle); The witnesses takes the horned figure (deception, which the witnesses expose). The images are named in the manifest's section entries (`image`), registered in the illustration picker with alt text, and bundled as `paul-portrait-mirror-stone.jpg`, `paul-bastard-stone.jpg` and `paul-horned-stone.jpg`. The centred layout added in 2.37.4 remains for any panel without an image.

## [2.37.4] - 2026-09-23

### Fixed

- Section pages (The man, The charges, The witnesses): their title panel has no image, so its text sat in a narrow left-aligned column right of centre inside a full-width box, leaving empty space on three sides. The panel is now a centred title block (breadcrumb, Greek line, title and description centred, the description held to 58 characters a line) with tighter vertical padding, matching the centred section headings on the front page and the centred cards below. Measured on the live section page, the panel is 80px shorter. Panels with an image are unchanged.

## [2.37.3] - 2026-09-23

### Fixed

- Title-panel breadcrumbs were inconsistent. The last item switched from the typewriter face to Cinzel capitals, on a different baseline from the items before it; section pages ended the trail with the word "Section"; and pages such as Answers and The Verdict had no item for themselves, so "Home" took the current-page style. The trail now uses the typewriter face throughout, aligned on one baseline, and always ends with the current place in the ink colour: "Home · The man" on a section page, "Home · Answers to missionary claims" on a page, "Home · Reference · Glossary" under a parent, and the section, label and part on an article as before.
- The Greek line in the title panel takes the breadcrumb's size and sits the same distance above the title on every page.

## [2.37.2] - 2026-09-23

### Changed

- The Save as PDF button used a full-colour PDF file icon (red page, white fill, grey lettering), the only mark in the share row not drawn in the text colour; it looked pasted in at rest and turned into a red block on the filled hover state. It is redrawn on the 24-point grid of the print and email marks as a single-colour line drawing, a page with a folded corner and a download arrow, so it takes the text colour at rest and the cream on hover like every other mark.

## [2.37.1] - 2026-09-23

### Changed

- The "Study questions for this article" link in the reading-order band was a plain underlined line. It is now a pill with a 1px accent outline: an oxblood roundel carrying a question mark (in the manner of the prutah on the search button), the label, and after a hairline the number of questions in the set ("6 questions"), counted from the Study guide page. On hover or keyboard focus the pill fills with the accent colour and the roundel inverts; transitions are off for readers who ask for reduced motion. The pill is at least 44px tall, so it leaves the small-links tap-area rule.

## [2.37.0] - 2026-09-23

### Added

- Koine Greek inscriptions, each a New Testament word or phrase set small in uncials above an English heading that stays, with a tooltip giving meaning and verse: ΣΑΥΛΟΣ Ο ΚΑΙ ΠΑΥΛΟΣ ("Saul, who is also Paul", Acts 13:9) above The man; ΚΑΤΗΓΟΡΙΑ ("accusation", John 18:29) above The charges; ΟΙ ΜΑΡΤΥΡΕΣ ("the witnesses", Acts 7:58, the witnesses who laid their clothes at Saul's feet) above The witnesses; ΑΠΟΛΟΓΙΑ ("defence", Acts 22:1) above Answers; ΚΡΙΣΙΣ ("judgment", John 5:22) above The Verdict; and ΑΠΟΛΩΛΩΣ ("lost", Luke 15:24) beside "Error 404" on the not-found page. The section words appear on the front page and on the section pages, the Answers word on the front page's answers block and the Answers page. All checked against the SBL Greek New Testament and set in EB Garamond, since Cinzel has no Greek. The words live in `paulus_greek_marks()`; the search label added in 2.36.4 shares the style.

## [2.36.4] - 2026-09-23

### Changed

- The search field's label is Koine Greek in place of Latin: ΖΗΤΕΙΤΕ, "seek", from Matthew 7:7 (ζητεῖτε, καὶ εὑρήσετε, "seek, and ye shall find"), set in uncials without accents as the manuscripts wrote it, and marked `lang="grc"`. Cinzel has no Greek, so the label is set in EB Garamond. A tooltip gives the English and the verse. It had read "Quaere".

## [2.36.3] - 2026-09-23

### Fixed

- Front-page hero: with the kicker and the author's name added, the hero grew taller than a laptop screen, and its bottom meander border fell below the fold (70px below at 1366×768, 117px at 1280×720). On screens 1025px and wider the heading, subheading, name and introduction now scale with the screen's height as well as its width, the vertical spacing is tightened, and the portrait is held to the screen height less the header. Measured on the live front page, the border now sits inside the first screen at 1280×720, 1366×768, 1440×900, 1536×864 and 1727×978.

## [2.36.2] - 2026-09-23

### Changed

- The publisher's address and telephone return to the structured data (the Organization's PostalAddress and international telephone number), with the two helpers that build them. The book page still does not show them. The Theme Options labels say "structured data only".

## [2.36.1] - 2026-09-23

### Changed

- Book page: the publisher's address and telephone number are no longer shown; the name, registration number, email and website remain. The structured data stops publishing them as well (the Organization's PostalAddress and telephone), and the two helpers that built them are removed. Both fields stay in Theme Options, Publisher, marked as kept on record.
- The order button reads "Order the book" on the book page, as it does in the book panel under every article. It had read "Order from Langgam Fikir", and "Order from the publisher" where only an email was set.

## [2.36.0] - 2026-09-23

### Added

- Book page: a "Catalogue record" section after the contents, giving the second printing's library record as a list of label and value: title and statement of responsibility, edition, author, publication, physical description, language, notes, the seven subject headings, both ISBNs, the Bib ID and the OCLC number. The contents are left out, since the page already lists them in Malay. Values are set ragged-left in every width; below 560px the label sits above its value. Content version 2.36.0: an unedited copy of the book page gains the section on the next structure sync.
- `[paulus_catalogue]`, and a Theme Options tab, Catalogue record, holding the fields the Book tab does not (title statement, physical description, language, notes, subjects, ISBNs, Bib ID), each defaulting to the library record.

## [2.35.3] - 2026-09-23

### Changed

- Footer credit: the tablet opens "The English text of this site is drawn from the Malay original:" before the book's details, so the site reads as an English work taken from the Malay book, and the book as its source.

## [2.35.2] - 2026-09-23

### Changed

- Footer credit: the catalogue card is withdrawn and the tabula ansata restored. Its text is shortened to run three or four lines: "Mohd Elfie Nieshaem Juferi, *Paulus: Perosak Risalah Al-Masih*. Cetakan kedua. Seri Kembangan, Selangor : Langgam Fikir, 2025. ISBN 978-629-96135-0-3. OCLC 1531949453." The edition and imprint follow the library record of the second printing; the ISBN and OCLC number each stay on one line.
- Theme Options: the Catalogue card tab is removed. Its two fields still in use, Edition and Imprint, move to the Book tab. The 2.35.1 one-time upgrade for the card's fields is removed with them.

## [2.35.1] - 2026-09-23

### Changed

- Catalogue card: the entry follows the library record of the second printing. The heading is "Mohd Elfie Nieshaem Juferi."; the title paragraph adds the edition, "Cetakan kedua", before the imprint "Seri Kembangan, Selangor : Langgam Fikir, 2025"; the physical description is "xxvi, 193 pages : illustrations ; 21 cm"; two notes follow ("In Malay, with quotations from the Qur'an, hadith, etc., in Arabic." and "Includes bibliographical references."), then the contents, both ISBNs as catalogued ("9786299613503 (paperback)" and "6299613505 (paperback)"), seven subject tracings with the title tracing, and "Bib ID 300162873" beside the OCLC number at the foot. The record's "Islam -- Apologetic works", listed twice, is traced once.
- The card's design is pared back: plain cream stock, a hairline edge and a softer shadow, with the punched hole kept. The blue ruling and the red margin line are removed.

### Added

- Catalogue card tab: Edition, Contents, ISBNs as catalogued (one per line) and Bib ID; Notes now takes one note per line. A one-time upgrade moves fields still holding the 2.35.0 defaults to the new record.

## [2.35.0] - 2026-09-23

### Changed

- Footer credit: the colophon in a tabula ansata becomes a library catalogue card. The entry follows the book's national-library record: the author heading "Mohd. Elfie Nieshaem Juferi."; the title and statement of responsibility with the imprint "Seri Kembangan, Selangor : Langgam Fikir Enterprise, 2025"; "xxvi, 193 pages, 2 unnumbered pages ; 21 cm"; the ISBN; "Text in Malay"; the tracings "1. Paul, the Apostle, Saint. 2. Christianity -- Origin. 3. Apostasy -- Islam. I. Title."; and "OCLC 1531949453" at the foot. It is typed in Special Elite in catalogue indention on cream card stock with pale blue ruling and a red margin line, and a punched hole near the foot is cut with a CSS mask so the footer texture shows through it, with the drop shadow following the hole. The card keeps its cream stock and ink in every scheme. The tabula ansata rules are removed from ornament.css.

### Added

- Theme Options, Catalogue card tab: the author heading, title and statement of responsibility, publication, physical description, note and subject headings (one per line), each defaulting to the national-library record.

## [2.34.4] - 2026-09-23

### Changed

- Footer wordmark: the site icon sat inside the home link, so the link's focus ring and hover state wrapped icon and name together. The icon now sits outside the link, and only the wordmark "Apostle of Doom" is linked.

## [2.34.3] - 2026-09-23

### Added

- *The Verdict*: a portrait of Isma'il R. al Faruqi (1921–1986), placed after the passage of his that the page quotes, in the section "The other side has had its say". Supplied by the site owner as public domain; bundled in grey at 1,400 and 720 pixels. *The Verdict* now carries two figures.

### Changed

- Figures from outside Wikimedia Commons: a registry entry with an empty `source` gets a credit line of author and licence alone ("Image: Unknown photographer, Public domain."), and its ImageObject omits the Commons credit and `acquireLicensePage`. Content version 2.34.3.

## [2.34.2] - 2026-09-23

### Changed

- Writing-style pass (anti-AI style) over every page's own prose, the manifest's titles, descriptions and excerpts, the Theme Options defaults, the 404 excuses, the image alt text and the documentation, with quotations, scripture and titles left as written. Site text: one "in order to" cut from a caption; three contrastive constructions rewritten (the Eastern reading of redemption in *The church that followed Paul*, the milk in the Syriac martyrdom in *The failed prophet*, the council's ruling in *James*); padded sets cut in *By their fruits* ("hypocrisy, deviation, falsehood and lies" to "hypocrisy and falsehood", and "lazy, misguided and hypocritical" to "hypocrites", the charge Galatians 2:13 supports), *The letters of a man*, *How Paul came to own the New Testament*, *Twisting the scriptures* and *From Ibn Ḥazm to al Faruqi*. No anaphoric runs or clause tricolons were found. Nine articles rebuilt; content version 2.34.2.
- Documentation: the "rather than" and "instead of" reframes, em dashes, contractions, "actually" and "highlight" rewritten throughout README.md, readme.txt and this changelog, earlier entries included.

## [2.34.1] - 2026-09-23

### Changed

- Timeline, c. 5–10 CE: "a Jew of the tribe of Benjamin (allegedly), with Roman citizenship." The tribe rests on Paul's own word alone, as *Saul the persecutor* notes. Content version 2.34.1.

## [2.34.0] - 2026-09-23

### Added

- Timeline interaction. On hover or keyboard focus an entry takes a faint oxblood wash, its diamond marker grows and glows in the accent colour, and its date darkens. The spine, now drawn entry by entry, lights up in oxblood from the first entry down to the marker of the one under the pointer, with every earlier marker lit, so a reader sees how far into Paul's life the moment falls. At rest the spine is drawn at 35 per cent of the rule colour, so the lit stretch shows in every scheme, including Ochre, where the rule colour and the accent are the same oxblood. Transitions are switched off for readers who ask their device to reduce motion. Pure CSS; the spine fill uses `:has()`, and browsers without it keep the lit entry.
- Fourteen of the seventeen dates now link to the articles that treat them, each link labelled with the article's title and an arrow that moves on hover: Tarsus and Gamaliel to the profile, the persecution and the vision to *Saul the persecutor* and *The Damascus road*, Arabia to *A self-appointed apostle*, the first Jerusalem visit to *James*, Antioch to *The church that followed Paul*, Cyprus to *The Roman*, the council to *The Jerusalem Council*, Greece to *Adapting the message*, Ephesus to *By their fruits*, the collection year to *The purse*, the Temple vow to its section in *The Jerusalem Council*, the appeal to Caesar to its section in *The Roman*, and the death to *The failed prophet*. The links stay visible at all times, so they work by touch. Print drops them and greys the spine. Content version 2.34.0.

## [2.33.9] - 2026-09-23

### Fixed

- Timeline: all 17 dates read "c. 5–10" and so on, relying on the introduction's note that dates are in the Common Era. Each now carries CE ("c. 5–10 CE"), as does the range for Paul's death in the introduction. Content version 2.33.9; an unedited copy of the page refreshes on the next structure sync.

## [2.33.8] - 2026-09-23

### Fixed

- Footer base row: the colophon tablet sat at the far left and the badges at the far right, with nothing between them on wide screens. The two now sit together as one centred group with a 3rem gap, and stack centred on narrow screens. The row is centred both in `parts/footer.html` and in the stylesheet, so a footer customised in the Site Editor follows too.
- The tabula ansata handles on the colophon were drawn as borders clipped to a dovetail shape, and the clip left only two thin vertical strokes, seen as stray "|" marks either side of the tablet. They are now solid dovetails in the ink colour, narrow where they meet the tablet and wide at the outer edge.

## [2.33.7] - 2026-09-23

### Changed

- Front-page hero: the author's name stands alone under the subtitle, as on a book cover. The "By" label and the "Author of *Paulus: Perosak Risalah Al-Masih*" line are removed; the book is already named across the page.
- Article byline: "By" removed; the name in Cinzel capitals stands on its own beside the reading time.

## [2.33.6] - 2026-09-23

### Fixed

- The one-time repair also restores the hero kicker ("The case against Paul of Tarsus", the line above the front-page heading), emptied by the same sync fault. It runs again for sites that already took the 2.33.5 repair, and fills only fields that are still empty.

## [2.33.5] - 2026-09-23

### Fixed

- Theme Options fields emptied by the theme itself. The sanitiser registered for the options record runs on every write to it, and wrote an empty value for any field not in its input. The theme's own writes pass only the keys they change: from 2.30.0 the structure sync updated the front-page description this way, and every field the site had never saved (so showing its default) was blanked. On apostleofdoom.org this removed the footer description, the AI Visibility badge and the front-page search title, so the home page was titled with the site name alone. The sanitiser now keeps a field's stored value when the field is absent from the input, and blanks nothing.
- A one-time repair (`paulus_upgrade_2335()`) restores the footer description, the footer badges and the front-page search title to their defaults where they are empty. Other fields are left alone, since an empty field may be deliberate; UPGRADING.md asks for a check of the Front page tab.

## [2.33.4] - 2026-09-23

### Fixed

- The footer showed no site description when the Theme Options footer description was saved empty, printing only "Published by Langgam Fikir (2025)." It now falls back to the WordPress tagline (Settings, General), so the site's own description always appears under the wordmark. The field's label says so.

### Changed

- The description and the publisher credit are two lines: the description in the body face, then "Published by Langgam Fikir (2025)." in a smaller, muted line. They had been joined into one sentence, which read correctly only with the default description.

## [2.33.3] - 2026-09-23

### Changed

- Theme Options footer credit: "Paulus, built by MENJ for Apostle of Doom", with MENJ linking to https://github.com/menj. Both come from the theme header (Author, Theme URI).

## [2.33.2] - 2026-09-23

### Changed

- Theme Options redesigned. The screen had kept WordPress's stock form layout. It now opens on a masthead in the active scheme's colours: the site icon in an accent ring, the site name and theme version in the label face, "Theme Options" in Cinzel capitals, and a "View site" button. Tabs carry an accent underline; fields sit in rounded cards with hairline rules between rows and accent focus rings; the colour schemes are larger swatch cards and the hero images a grid with accent selection. The screen follows the chosen scheme at once, before saving, so a scheme can be judged in place. "Save options" becomes "Save changes" on a sticky bar at the foot of the form. The theme's bundled fonts load on this screen only.

### Added

- Admin footer credit on Theme Options: "Paulus, designed and built by Mohd Elfie Nieshaem Juferi for Apostle of Doom", with the author's site and the front page linked, in place of WordPress's "Thank you for creating with WordPress", and "Paulus <version>" in place of the WordPress version. Other admin screens keep WordPress's text.

## [2.33.1] - 2026-09-23

### Fixed

- The admin menu entry under Appearance and the screen's heading read "Theme options"; both now read "Theme Options", as do the references in the code comments, README.md, UPGRADING.md and readme.txt. Earlier changelog entries keep the wording they were written with.

## [2.33.0] - 2026-09-23

### Added

- Front-page hero: the author's name, under the subheading and above the introduction, in Cinzel capitals in the accent colour, with "Author of *Paulus: Perosak Risalah Al-Masih*" beneath it. Both come from the Book tab (author, Malay title).

### Changed

- Article byline: the author's name is set as a signature in Cinzel capitals at up to 1.25rem in the ink colour, where it had been the same small typewriter label as the reading time. "By" and the reading time stay in the label face beside it. The Save as PDF sheet and the print stylesheet set the name at 12pt.

### Fixed

- The Save as PDF button showed a text label in place of its icon on every site. `paulus_icon()` passed icon names through WordPress's `sanitize_file_name()`, which turns a bare name that is also a file extension into "unnamed-file.<ext>", so "pdf" was looked up as `unnamed-file.pdf.svg` and never found; the other marks were unaffected because their names are not extensions. Icon names are now checked as plain slugs, segment by segment, which still refuses any path outside the icon folder.

## [2.32.2] - 2026-09-23

### Added

- UPGRADING.md: the routine for updating a live site, what the structure sync changes and what it leaves alone, the templates that need resetting if they were customised in the Site Editor, and notes for each version from 2.26.5.

### Changed

- README.md brought up to date: the article layout after the removal of the "This series" list; the front-page search title option; `[paulus_404_quip]`, `[paulus_link]` with its self-closing form and `anchor` attribute, and the status of `[paulus_series_nav]`; the author card's plain-heading name; the footer site icon and the muted front-page masthead; the build details (reading-order notes, every apparatus mark stripped, `{{fig:}}`); a new "Editorial conventions" section (the two Pauls, cross-references, verification, figure placement); the title and description rules and their behaviour with SEO plugins; the 404 page; and the photograph count, sourcing and licensing notes. The installation step names the current zip, and the last em dashes are gone.
- readme.txt: description and installation updated to match.
- `commons-meta.json` carries every registered figure; 21 entries were missing.

### Fixed

- Two figures added in 2.29.0 repeated works already on the site: the Poussin in *Ego, epilepsy and narcissism* is the same Louvre painting as the one in *Five hundred witnesses and an angel of light*, and the Veneziano engraving in *The Roman* reproduces the Raphael cartoon already shown in *Perjury, curses and the whited wall*. Both are removed, with their files and registry entries. *The Roman* now shows a Roman military diploma from Judaea, 90 CE (CC BY-SA 3.0, licence checked), in its section on citizenship.
- Content version 2.32.2.

## [2.32.1] - 2026-09-23

### Fixed

- Cross-references. Eight articles named other parts of the site in their running text ("set out under Count·II," "examined in The vision," "treated in The Islamic tradition," "answered in the Answers," the book's title) without linking them; some linked only from a footnote, some not at all. All 26 such references are now links, each to the article that holds the material: a reference to Count·V on the disputed letters opens the "Two Pauls" section, Count·V's typology opens the Typology section, and so on.
- *The religion of Paul in Malaysia and Southeast Asia*: a note said "The full list is given below," but notes print after the article's Further reading list, so the list stood above the note. The note now links to Further reading by name.
- Content version 2.32.1.

## [2.32.0] - 2026-09-23

### Changed

- The not-found page. Its heading is now one of eight excuses for the missing page, chosen at random on each visit and each drawn from Paul's own record: the page went to Arabia for three years (Galatians 1:15–18), saw a light on the road that no two witnesses agree on (Acts 9, 22, 26), was seen by five hundred nameless witnesses (1 Corinthians 15:6), was let down the wall in a basket (2 Corinthians 11:32–33), became all things to all men (1 Corinthians 9:20–22), was shipwrecked thrice (2 Corinthians 11:25), swears before God that it lies not (Galatians 1:20, against Acts 9), or is absent in body and present in spirit (1 Corinthians 5:3). Each carries its reference and a "Hear another excuse" link that draws a new one. Only the letters Paul wrote, and Acts, are used. "Error 404 · Page not found" stays above the joke, and the plain explanation, the links and the search form follow below a rule, so a lost reader still finds the way.

## [2.31.0] - 2026-09-23

### Added

- *How Paul came to own the New Testament*: a section, "Two Pauls: the man and the canon," setting out the site's method. Charges against Paul himself rest on the seven letters scholars accept as his, with Acts as a later and weaker witness; charges against the religion that bears his name draw on all thirteen, because the church received all thirteen as his. The section notes the six disputed letters and the anonymous Hebrews, and the second-century contest between the Pastoral letters and the Acts of Paul and Thecla, each claiming Paul. Notes to Dale B. Martin's Yale lectures 17, 19 and 20.
- *Seven doctrines*: a reply to the reading of Paul "within Judaism," which holds that he closed the law to Gentiles only. His own words to those "that know the law" (Romans 7:1, 6), "not being myself under the law" (1 Corinthians 9:20, in the Greek of the critical text), the rebuke of Peter at Antioch (Galatians 2:14) and "but dung" (Philippians 3:5–8).
- *Twisting the scriptures* and the law answer: Hebrews 8:13, "ready to vanish away," set against the verse of Jeremiah it quotes, which writes the law on the heart.
- *Adapting the message to the pagans*: the Hellenistic background of divine fathers and mortal mothers and of gods renamed across peoples, after Martin's lecture on the Greco-Roman world.
- Thirteen figures for The charges and The witnesses, each licence checked on its Commons page: El Greco's Peter and Paul; the Areopagus; Rembrandt's Moses with the tablets; La Hyre's Paul on Malta; Guercino's Hagar and Ishmael; the Etchmiadzin relief of Paul and Thecla; Bloemaert's Moses striking the rock; the Habib-i Neccar Mosque at Antakya; the Great Mosque of Kufa in 1915; the Tughrul Tower at Rayy; the grave of Isma'il R. and Lois Lamya al Faruqi; St George's Church, Penang; and a page of Codex Sinaiticus, the first figure on *The Verdict*.

### Fixed

- Attribution of the disputed letters. Every passage that made a charge against Paul himself from a letter scholars do not accept as his now either argues from an undisputed letter or says plainly that the letter was written in his name. The Jesus-and-Paul table in *Adapting the message* quotes Philippians 2:6 and Romans 5:9 in place of Colossians and Hebrews; *Seven doctrines* quotes Galatians 2:16 and Romans 14:14 in place of Ephesians and Colossians; the passages on women and on slavery, in *How Paul came to own the New Testament* and *Slaves, Caesar and the witnesses of conscience*, now rest on 1 Corinthians 11 and 7:21, with Ephesians, Colossians and 1 Timothy marked as his school; the typology note cites Romans 5:14 and 1 Corinthians 10; the Logos vocabulary, the Qurʾān article, the Jannes and Jambres passage, the Ephesians quotation of Psalm 68, and several footnotes are relabelled to match.

### Changed

- *The letters of a man*: a note that three of its letters are held to be his school's, weighed as the scripture the church made them.
- Content version 2.31.0. Unedited copies of the 18 changed pages refresh on the next structure sync.

## [2.30.0] - 2026-09-23

### Changed

- Titles. Every page now takes the form "<page title> | Apostle of Doom" in at most 50 characters: articles, pages, the three sections, the front page, search results and the not-found page. Each article, page and section has a short search title in the manifest (32 characters at most, the room left after " | Apostle of Doom"), and the front page has its own in Theme options > Front page. Where no short title is set, the page's own title is used and cut at a word boundary if it would run past the limit; the site name is never cut.
- Descriptions. All 47 descriptions are rewritten to 120 to 130 characters, each describing its own page and closing on a quiet invitation in place of the old imperatives ("Read the record.", "Order the book."). The sections have descriptions of their own for the first time, in place of their long archive introductions. Any description, however it was entered, is held to 130 characters on output.
- With Rank Math or Yoast active, the theme supplies the same title and description to the plugin, including its Open Graph and X tags, so the format holds whichever plugin writes the head. Titles or descriptions typed into the plugin's own fields are replaced by the theme's.
- The editor's side box is now "Search title and description", with a title field limited to the available 32 characters and the description limited to 130.
- Sync: shipped titles and descriptions reach existing posts, pages and sections, and the front-page description option, wherever the stored value is empty or still one this theme shipped earlier (listed in the manifest as `prior_meta`). Values written by hand are kept.
- Content version 2.30.0.

## [2.29.1] - 2026-09-23

### Fixed

- Answers, "Did Paul abolish the law?": the answer set Romans 3:31 against Ephesians 2:15. Ephesians is among the letters most scholars do not accept as Paul's, and the site itself says only seven letters are his, so the argument rested on a text an opponent could disown. It now sets Romans 3:31 against Romans 7:6, where Paul uses the same verb, *katargeō*, in the same undisputed letter ("delivered from the law"), and adds Galatians 4:9–10, where he calls Gentile law-keeping a return to "the weak and beggarly elements." The page's notes are renumbered in reading order (21 in all). The matching study question now names Romans.

### Added

- *A self-appointed apostle*: Paul's own itinerary in Galatians 1:17–19 (Arabia, Damascus, and Jerusalem only after three years, seeing Peter and James alone) set against Acts 9:26–27, where Barnabas brings the new convert "to the apostles," with Paul's oath in Galatians 1:20 and a note to Dale B. Martin's Yale lectures on which account historians prefer.

### Changed

- Content version 2.29.1.

## [2.29.0] - 2026-09-23

### Fixed

- Footnote numbering. In 23 of the 31 articles the shipped notes were numbered out of reading order (scripture blocks took the first numbers wherever they fell), so a reader met note 13 before note 1. Every article is now rebuilt from its source in `content/src`, and each one's notes run 1, 2, 3 in the order they appear. An audit before the rebuild confirmed that the text and the notes themselves were identical to the shipped files in all 23; only the numbering changes.
- Study-question links. *Saul the persecutor* and *A self-appointed apostle* linked to a study-questions section that did not exist. The section now exists.
- Answer links. Each "Full evidence" link now shows the title of the article it opens, drawn from the article itself, so the label cannot drift again. Two links were also retargeted to the article that holds the evidence: the Muslim scholars answer now opens *The early Islamic record* (where Khushaysh ibn Aṣram and ʿAbd al-Jabbār are treated), and the law answer opens *Seven doctrines* at "The law abolished and cursed" (the first part of that series, which the link used to open, does not discuss the law).

### Added

- Study questions for eight articles that had none: *Saul the persecutor*, *The Roman*, *The purse*, *The first witnesses against Paul*, *James, the brother of ʿĪsā ibn Maryam*, *The Qurʾān as witness*, *An epistle to the churches of Paul*, and *The religion of Paul in Malaysia and Southeast Asia*. Every article now reaches a set of questions, its own or its series'.
- Figures for the section The man, following the placement rule of 2.27.0: Herodium in *Who was Paul of Tarsus?*; Agostino Veneziano's 1516 engraving after Raphael's blinding of Elymas in *The Roman*; the spoils relief on the Arch of Titus in *Saul the persecutor*; Caravaggio's Odescalchi conversion in *The persecutor and his guilt*; Poussin's rapture of Paul in *Ego, epilepsy and narcissism*; the Ohrid fresco of Epiphanius in *The flesh*. Each licence was checked on its Commons page.

### Changed

- *The religion of Paul today*: the 1807 abridged Bible gives way to a photograph of John Shelby Spong (Scott Griessel, CC BY-SA 2.0), beside the passage that quotes him. The 1807 files and their registry entry are removed, along with the schema rule for printers that existed only for them.
- Front page: the masthead is set in the muted ink colour, so the hero title leads; on every other page it is unchanged.
- Content version 2.29.0. Unedited copies of the 27 changed pages refresh on the next structure sync.

## [2.28.2] - 2026-09-23

### Added

- Study questions: one question added to each of the four sections whose articles or answers changed in 2.28.0, so the questions follow the new material. The Damascus road asks about φωνή read as a voice in one verse and a sound in the next; Paul against the disciples, about 2 Peter as the only letter in which Peter praises Paul; The law as a curse, about *katargeō* in Romans 3:31 and Ephesians 2:15; How Paul came to own the New Testament, about the tests said to have excluded the other gospels. The existing questions are unchanged: none of them relied on the details corrected in 2.28.0.

### Changed

- Content version 2.28.2. An unedited copy of the page refreshes on the next structure sync.

## [2.28.1] - 2026-09-23

### Fixed

- The "Full evidence:" line under each answer was pulled up into the paragraph above by a negative margin, so it sat hard against the last line of text, closer still where that line carried a footnote marker. Its small typewriter label and the much larger bold link beside it also sat unevenly. The line now stands 0.75rem below the answer as its own row, the label and link are laid out on a shared baseline with a fixed gap, and the size gap between them is narrowed (label 0.8rem, link 1rem). The front-page accordion gets the same treatment.

## [2.28.0] - 2026-09-23

### Added

- Answers: four new questions, for twelve in all. Did Paul abolish the law, or only deny that it saves (the same verb, *katargeō*, in Romans 3:31 and Ephesians 2:15)? Do the three accounts of the Damascus road agree (the men who "stood speechless" against "we were all fallen," and the φωνή that is a voice in one verse and a sound in the other)? Did Peter endorse Paul's letters (2 Peter, its authorship and its late reception)? Were the other gospels left out because they were anonymous, late or Gnostic (the anonymous canonical gospels and Hebrews, and the law-keeping Ebionites who rejected Paul)? Every verse was checked against the KJV and SBLGNT text, and each scholarly claim against a live source; the notes continue the page's numbering (10 to 20).
- The Damascus road: a paragraph on the "sound, not voice" reading of φωνή, answering it from Acts 9:4 and 22:9.

### Fixed

- The Damascus road: the comparison table said "Paul alone" fell in the first two accounts. Acts 9 has Paul fall while the men "stood speechless," and Acts 22 does not say what the companions did, so the cells now say exactly that. The contradiction the article draws, Acts 9:7 against 26:14, stands on those two verses alone.
- How Paul came to own the New Testament: the justification example paired Romans 3:28 ("without the deeds of the law") with Romans 2:6 ("according to his deeds"), which let a reader answer that the law's works and good deeds are different things. It now pairs Romans 2:13 ("the doers of the law shall be justified") with Romans 3:20 ("by the deeds of the law there shall no flesh be justified"), where both verses speak of the law. The article's footnote numbering is unchanged.
- `build.py` now strips the SBLGNT apparatus mark ⸁ along with the others; Romans 2:13 carries one, and it would otherwise have appeared in the Greek text.

### Changed

- Content version 2.28.0. Unedited copies of the Answers page and the two articles refresh on the next structure sync.

## [2.27.4] - 2026-09-23

### Added

- The footer wordmark carries the site icon beside the name, in a round frame with the oxblood ring of the author portrait. It uses the icon set under Site Identity, and the bundled icon (a 96px copy of 3 KB, in place of the 512px original of 240 KB) when none is set. The icon is decorative and sits inside the home link, so the name stays the link text. The header masthead remains a wordmark alone.

## [2.27.3] - 2026-09-23

### Fixed

- The front-page lede was justified with automatic hyphenation. Four short lines cannot absorb the stretch, so the first line opened with wide gaps between words ("Saul of Tarsus hunted the") and "centuries" broke across lines as "cen-turies". The lede is now set ragged-right with no automatic hyphens, like the other display text above it.
- The hero's actions still paired a filled button with a plain underlined link, the mismatch 2.26.4 removed from the book panel. About the book is now the same outlined button used there, so Read the charges and About the book read as a pair; the gap between them is tightened to suit two buttons.

## [2.27.2] - 2026-09-23

### Changed

- Saul the persecutor, under "Letters from the high priest": the arrest passage names the book in the text again, now with its publisher and year, and keeps the first person: "In *Polis Raja Di Malaysia* (Langgam Fikir, 2026), I recount how…". The footnote with the full reference and pages is unchanged. Unedited copies refresh on the next structure sync.

## [2.27.1] - 2026-09-23

### Fixed

- Saul the persecutor, under "Letters from the high priest": the passage on the arrest described the site's own author in the third person ("the author recounts how, after his own arrest"), as though someone else had written the book it cites. It now speaks in the first person, as testimony should, and the book's title leaves the body text for the footnote, which already carries it in full. Unedited copies refresh on the next structure sync.

## [2.27.0] - 2026-09-23

### Added

- More figures in the chapter *Paul, the father of a new religion*, the pilot for placing figures by length (about one for every 700 to 800 words, one per section at most, none in the first screen or in the closing section). The church that followed Paul gains two: Marcion in the woodcut portraits of the Nuremberg chronicle of 1493 (Rijksmuseum, CC0), and a 1904 reproduction of the Alexamenos graffito from the Palatine (public domain). The religion of Paul today gains the title page of the 1807 Bible abridged for enslaved Africans in the British West Indies, which kept "Servants, be obedient" and cut the Exodus (public domain). The failed prophet already carried three and is unchanged. Each licence was checked on its Commons file page; the files are bundled at the house sizes in `assets/images/church/` and registered in `paulus_figures()`, so the credit lines, the lightbox sequence and the ImageObject schema pick them up.
- Image schema: a printing house named as a figure's author is described as an Organization.

### Changed

- Content version 2.27.0. Unedited copies of the two articles refresh on the next structure sync; copies edited by hand are left alone.

## [2.26.5] - 2026-09-23

### Removed

- The "This series" list after an article. It was the third listing of the same parts on one page: the side rail carries them under "In this chapter" through the whole text on wide screens, the title panel carries them on narrower screens, and the reading-order band directly beneath gives the previous and next part at every width. The `[paulus_series_nav]` shortcode remains available for manual placement; only its place in the single-article template is gone.

## [2.26.4] - 2026-09-23

### Changed

- The book panel's actions are redesigned. The price had been set inside the order button ("ORDER THE BOOK, RM 45.00"), where the display face has no figures, so the number fell back to a smaller face and the button read as a price tag. The price now stands on its own, in Cinzel capitals with the format above it in the typewriter face ("Paperback / RM 45.00"), and the button simply says Order the book. About the book, which was a plain underlined link beside a filled button, is now a matching outlined button of the same size that fills on hover, so the two read as a pair of choices.
- In the details line, each item keeps together: the ISBN no longer breaks across two lines at its hyphen.

## [2.26.3] - 2026-09-23

### Fixed

- The side rail ran on past the end of the article: it stayed pinned beside the author card and the "This series" list, which repeated its own list of parts side by side, and slid under the reading-order band. The rail now stops where the article text ends (measured by the reader script, kept current as the page and images load and on resize), so after the text there is one list of parts only.
- "More from" under an article listed every part of every chapter in the section, thirteen entries on The Charges, including the parts of the reader's own chapter already listed under "This series" just above. It now leaves out the reader's own chapter and lists each other chapter once, by its title and first part, as the section page does.

## [2.26.2] - 2026-09-23

### Fixed

- On screens 1240px and wider the parts of a chapter were listed twice within one screen: in the title panel under the byline, and in the side rail under "In this chapter". The title-panel list now gives way wherever the rail is shown; below 1240px, where there is no rail, it remains, and the "This series" list after the text is unchanged.
- The first part of The character of Paul said "These two articles examine his conduct" although the chapter has had three parts since 2.16.0. It now says three. Unedited copies refresh on the next structure sync.

## [2.26.1] - 2026-09-23

### Changed

- The author's name at the head of the author card is a plain heading again. It linked to the author's site, and so did the same name opening the bio directly beneath it, which put two links to one address a line apart; the one in the bio remains, where a link reads as part of the sentence. The heading's underline and hover styles are removed with it.

## [2.26.0] - 2026-09-23

### Added

- Structured data for the parts of the site the graph did not yet describe. Section pages are a CollectionPage whose main entity is an ItemList of their chapters in reading order, one entry per series as the page lists them. The Answers page is an FAQPage, each h2 a Question and the paragraphs under it, up to the "Full evidence" link, its Answer, with footnote markers stripped. The book page is an ItemPage whose main entity is the Book; search results are a SearchResultsPage; the front page and reference pages are WebPages, the front page about Paul of Tarsus. The WebSite carries a SearchAction for the new site search.
- Every photograph placed with `[paulus_figure]` is described as an ImageObject with its licence (Creative Commons URL, or the Public Domain Mark), the Commons page as `acquireLicensePage`, the credit line and the creator (a person, or an organisation for museums and firms; none for unknown painters and scribes). This is the licence metadata Google reads for Images results.

### Fixed

- The publisher's address was one string holding street, postcode, town and state together; it is now split into `streetAddress`, `postalCode`, `addressLocality` and `addressRegion`. The telephone is given in international form (+60-11-7346 8081).

### With Rank Math

- Rank Math keeps the page node, the article, breadcrumbs, the organisation and the site. The theme adds, into Rank Math's own graph and only where Rank Math has no node of that type, the section ItemList, the Answers FAQPage, the photographs' ImageObjects, and, as before, the Book and the author. Checked with a simulated Rank Math graph: one JSON-LD script per page, no type duplicated.

## [2.26.0] - 2026-09-23

### Changed

- The footer credit gives the year of publication after the publisher: "…published by Langgam Fikir (2025).", the year taken from the "First published" field so a new edition updates it.
- The publisher's address in structured data is split into its parts (street, postcode, town, state, country) where it had been one string, and the telephone is given in international form (+60-11-7346 8081).

### Added

- Structured data describes every kind of page, not only articles and the book: sections as a CollectionPage whose main entity is an ItemList of their six pieces in reading order; the Answers page as an FAQPage of its eight questions, each answer taken from the text under its heading; the book page as an ItemPage whose main entity is the Book; search as a SearchResultsPage; other pages as a WebPage; and the WebSite with a SearchAction pointing at the new site search.
- Every photograph placed in the text is published as an ImageObject with its licence (Creative Commons or the public-domain mark), the Commons page as the place to acquire the licence, its credit and its creator: the image-licence metadata Google shows in Images.
- With Rank Math active, its own page node, breadcrumbs, organisation, person and website stand; the theme adds only what Rank Math does not publish (the Book, a section's ItemList, the Answers FAQPage and the photograph ImageObjects), each as a separate node. Checked on all page types with and without Rank Math's graph: valid JSON-LD and no reference to an entity that is not in the graph.

## [2.25.6] - 2026-09-23

### Changed

- The publisher credit in the footer brand block was a separate line in the typewriter face ("Published by Langgam Fikir"), which read as a label detached from the description above it. It now closes the description as one sentence in the body face: "…drawn from the book by Mohd Elfie Nieshaem Juferi, published by Langgam Fikir.", with the publisher's name linked. Either half still stands alone if the other field is empty.

## [2.25.5] - 2026-09-23

### Fixed

- An empty band sat between the footer's brand block and its link columns. The brand block could grow to 30rem while its description stopped at 34 characters a line, so the unused width, plus the gap, pooled beside it, and the link columns were pushed across to take what remained. The brand block and each link column now take equal thirds of the width, and the description may run to 46 characters, filling its third.

## [2.25.4] - 2026-09-23

### Changed

- The sitemap page moves from `/site-map/` to `/sitemap/`. On the next structure sync the installer renames the existing page in place, so its content, edits and ID carry over; this is driven by a new `was_slug` key in the manifest, usable for any future page move. The old address answers with a 301 redirect to the new one, since WordPress redirects the old slugs of posts but not of pages. Rank Math's and WordPress's own XML sitemaps (`*.xml`) are unaffected. `content_version` moves to 2.25.4.

## [2.25.3] - 2026-09-23

### Changed

- The HTML sitemap page is titled "Sitemap" (formerly "Site map"), and so is its link in the footer, on the 404 page and in breadcrumbs, which all read the page title. `content_version` moves to 2.25.3 so existing sites pick up the new title on their next structure sync; a title already changed by hand in WordPress is left as it is. The address stays `/site-map/`, so existing links and search listings keep working.

## [2.25.2] - 2026-09-23

### Changed

- The author card appears only on articles and the book page. Since 2.24.0 it had been on every page, including the front page, section listings, reference pages, search and the 404 page, where there is no piece of the author's writing to attribute; that went further than the usual practice of an author box on articles. The fallback that placed it above the footer is removed.

## [2.25.1] - 2026-09-23

### Changed

- The author card moves up to follow the text directly on articles and pages, after the references and before the series list, reading-order band, related links and book panel, where readers look for who wrote what they have just read, and where authorship sits beside the content it vouches for. It had been attached to the footer, which put five other blocks between the end of an article and its author. Pages without body text of their own (front page, section listings, search, 404) keep it above the footer. It is attached to the post-content block as that renders, so Site Editor templates get it too; it appears once per page, and post content rendered for other posts inside a query loop is left alone.

## [2.25.0] - 2026-09-23

### Added

- The author card's medallion shows the author's portrait, where it had shown his initials: `assets/images/author-portrait.webp`, cropped head and shoulders from the supplied photograph to 240×240 (8.6 KB), its transparent background letting the scheme's accent colour (or the bronze prutah, with the ornament on) show behind it. The medallion grows to 7.5rem (6.5rem on phones) so the face reads at a glance, and the photograph is inset so its rings still show. The monogram remains as a fallback if the file is removed.
- Person structured data carries the portrait as `image`.

## [2.24.1] - 2026-09-23

### Fixed

- The author card's links belong inside the bio text, but on a site whose bio was saved as plain text by an earlier version they appeared instead as a separate row of bare addresses under it. That row is removed. A plain-text bio now gets its links added in place: the author's name, Bismika Allahuma (a bare "bismikaallahuma.org" is shown by that name), The Muslim Apologist and the publisher, each linking to the address set in Theme options, so the saved bio needs no retyping. A bio that already contains links is used as written.

## [2.24.0] - 2026-09-23

### Added

- "Saul the persecutor" gains a section, "Letters from the high priest", excerpting the author's own account of the Paul parallel from *Polis Raja Di Malaysia* (Langgam Fikir, 2026), pp. 142–144, verbatim.
- "Who was Paul" no longer presents Paul's Benjamite lineage and Pharisee affiliation as established fact, matching the new note; the note on those claims now also cites Acts 26:5 and Romans 11:1 and records that these self-descriptions have no independent, contemporaneous corroboration.
- The footer credit line now carries the book's OCLC control number after the ISBN, and the Book structured data carries it as an `identifier` with `propertyID` OCLC. Both are omitted when the field is empty.
- Social profiles: a Theme options field (Book tab), one URL per line, the platform detected from the address. They appear as a row of marks in the footer brand block and are added to the author's `sameAs`. The full Minimalist Social Icons pack is bundled in `assets/icons/social/` (45 platforms; its readme is in `licenses/`), and a platform it does not cover is linked by name. `paulus_icon()` now reaches that subfolder, refuses `.` and `..` path segments, and converts the pack's hard-coded black fills to the text colour so every mark follows the scheme.
- The footer gains a brand block: the site wordmark, a description of the site, and the publisher credit. The description is a new Theme options field (Front page tab), since sites often leave the WordPress tagline at its default.
- The author bio is now a single card (monogram medallion, name, bio, links to the author's site, YouTube channel and publisher, and a collapsible list of his other books), rendered above the footer on every page as well as under articles.
- The author card links out: the name to menj.blog, and, in the bio, Bismika Allahuma, The Muslim Apologist and Langgam Fikir to their own sites. The bio field now accepts links and light emphasis (`a`, `em`, `strong`, `cite`), filtered through `wp_kses`; the separate links row is shown only when the bio has no links of its own.
- Three new Theme options fields on the Book tab: the author's website (which the card's name links to), the author's YouTube channel, and "Other books by the author", one per line as Title (Publisher, Year).
- `content_version` moves to 2.24.0, so sites pick these up on the next structure sync; only text still unedited since the last sync is refreshed.

### Changed

- The footer link columns were capped at `max-width: 560px` inside an 1180px footer, so they crowded into the left half and left the right half empty. The cap is gone and the footer is now a brand block beside the link columns, filling the width. The wordmark moves out of the bottom row into that block.
- Footer columns use flex, since `grid-template-columns: repeat(auto-fit, …)` renders inconsistently; the ornament layer's hairline between columns was rewritten to match (it relied on a negative margin sized for the old grid).
- Rank Math now takes precedence over the theme's structured data, type by type. The theme hooks `rank_math/json_ld` and adds only the types absent from Rank Math's own graph for that request, so anything Rank Math manages is left untouched (subtypes count: BlogPosting or NewsArticle means it owns the article), while what it does not emit (the Book above all, since its schema module has no Book type) still gets published. Entities added this way inline their own author and publisher, with no reference to Rank Math's `@id` values, and are keyed `paulus_*`, which Rank Math's own entity-linking pass leaves untouched (it only rewrites `schema-*` and `richSnippet` keys). The filter runs at priority 100, after that pass, so it sees the final graph. Checked against Rank Math 1.0.279: on an article with Rank Math's defaults the theme adds nothing at all. If Rank Math is active but publishing no graph at all (its schema module switched off), the theme prints its own.
- The other detected SEO plugins (Yoast, AIOSEO, SEOPress, The SEO Framework) can't be inspected the same way, so there the theme still adds only the Book, which none of them emits.
- The bio pasted into the book page content is removed; the card renders it from Theme options instead, so it can't drift out of step between pages. A book page that has been hand-edited keeps the old pasted copy and will show the bio twice; delete its "About the author" and "Other books" sections.
- Person structured data now lists the author's site, apologetics site and YouTube channel under `sameAs`, uses the author website as `url`, and strips the bio's markup out of `description`.
- `[paulus_about_author]` now renders nothing, kept registered only so a customised template neither duplicates the card nor prints the raw shortcode.

### Fixed

- `wordCount` in Article schema counted with `str_word_count()`, which is not UTF-8 aware and miscounted the Greek, Arabic and transliterated words throughout these articles, and counted shortcode syntax as words. It now strips shortcodes first and counts with a Unicode-aware pattern.
- Article illustrations now declare an image licence, since they are public domain: `license` points at the Public Domain Mark by default and no `copyrightNotice` is asserted over them, while credit is still given. Two new Theme options fields (Book tab) make both the licence URL and an optional licence-acquisition page configurable; clearing the licence URL restores the previous `copyrightNotice` behaviour. The book cover and publisher logo are deliberately excluded, since neither is public domain.
- Organization structured data used the book cover as the publisher's `logo`. Langgam Fikir's own logo now ships as `assets/images/publisher-logo.png` (500×500, transparent, quantised to 64 colours: 470 KB down to 78 KB with no visible change) and is used instead, with its dimensions declared. The cover remains the Book entity's `image`, which is what it should be.
- `inLanguage` on Article and WebSite was hard-coded to `en`; both now follow the site's own locale.
- The Save as PDF icon was drawn so that its "PDF" lettering filled in the same direction as the page shape, cancelling into a solid block. It is replaced with a full-colour PDF file mark (red page, folded corner, "PDF" lettering), the one exception to the share row's text-coloured marks. `paulus_icon()` now strips any width and height an icon file declares on its root before setting its own 16px: this file ships at 75mm, and since a browser keeps the first of two duplicate attributes, the button would otherwise have rendered at that size.
- The Print and Save as PDF buttons rendered as an empty circle whenever their icon file could not be read (the social links already skip themselves in that case); they now show a short text label instead.
- Documentation brought up to date with this release: the Theme options table, the file tree (`options-fields`/`options-render`, `licenses/`, the publisher logo), the shortcode table (including the figure, search and footer-badge shortcodes, which were never listed), a new section on the author card and footer, the structured-data and Rank Math behaviour, the field-by-field edit protection, and the portable build. The duplicate "Search" heading is fixed: the first of the two is about search engines. `readme.txt` gets its article count corrected (26 to 33) and changelog entries for 2.22.2 and 2.23.0.
- Theme options, Book tab: the image licence fields moved to the end of the tab, where they no longer split the bibliographic fields.

## [2.23.0] - 2026-09-22

### Fixed

- Importer: manual edits to an article's body could be silently discarded the second time a release changed the shipped text for that article, even though the edit correctly survived the first such change. Title and excerpt are now protected independently of the body, so a body refresh no longer overwrites them.
- Importer: a legacy upgrade (2.0.0) could write an empty article body if its bundled source file were ever renamed or removed; all `paulus_content_file()` results used in one-time upgrades are now checked for an empty return before writing.
- Importer: a manual recategorisation of an article was reset on every reinstall; category assignment is now tracked against a recorded baseline, so a manual change is recognised and left alone. On a site upgrading through this fix (no baseline yet), a category is only reassigned when the article is plainly untouched (no category, the default one, or already the target).
- Importer: `menu_order` and `page_template` were never brought in line with the manifest for a page that already existed; they are now reconciled the same way, protected by the same baseline tracking so a manual reorder or template change is not undone; with no baseline yet, a differing value is left alone and treated as a deliberate edit.
- Importer: a failed or partial site-structure sync could still be marked complete, so it was never retried; the sync is now marked complete only when nothing failed, and a simple lock prevents two overlapping sync attempts.
- Every one-time upgrade function (`paulus_upgrade_*`) is now gated behind a single `current_user_can( 'edit_theme_options' )` check, run once; previously none of them checked who was asking, and all ran on `admin_init`, which fires for any logged-in user on any wp-admin screen.
- Installer: article publish dates were computed in UTC but written to `post_date`, which WordPress treats as the site's local time; the site's local-time conversion and the true UTC value are now set correctly and separately.
- Search: marking search terms could corrupt the markup it had just inserted. Searching "Paul mark" would match the literal word "mark" inside the `<mark>` tag the first replacement added. Term matching is now done in a single pass over the original text.
- The Answers page's front-page teaser ignored password protection and would still show its questions and answers if the page were password-protected; it now checks `post_password_required()`.
- Category archives were built from a separate query (inside `[paulus_parts part="current"]`) that implicitly excluded any post without a recorded reading order, silently dropping ordinary posts from the section; it now includes them, matching the already-correct logic in the `pre_get_posts` filter that this query bypassed. The query's own hard 100-item cap and disabled pagination are also removed.
- A heading with a manually added HTML anchor (`id="…"`) got a side-rail link pointing to a different, computed id that was never applied to the page, since the id is preserved but the rail's link was not computed from it. The rail now reuses an existing id when a heading already has one.
- The block editor never reflected the site's active colour scheme: `schemes.css` is scoped entirely to `body.paulus-scheme-*`, and nothing ever added that class inside the editor's iframe. The active scheme's own declaration is now also passed to the editor through its settings' styles, which load inside the editor canvas.
- The search field's background was a fixed colour regardless of scheme; on the three darker schemes (oxblood, ink, graphite) this put near-white text on a near-white background (contrast ratios of 1.00–1.08, against a 4.5:1 requirement). It now uses the scheme's own surface colour, which is designed to contrast with its text colour in every scheme.
- A CSS custom property (`--wp--preset--color--accent-deep`) referenced itself in its own fallback value, which is an invalid, cyclic declaration; because that declaration also came later in the file at equal selector specificity, it silently overrode every scheme's own accent-deep colour, including the one explicitly set colour (ochre's). Fixed with `:where()` so each scheme's own value, where it declares one, always wins.
- Rapid or repeated clicks on the PDF export button could start more than one concurrent export, since `aria-busy` was set for accessibility and never checked. It is now the real guard: the button disables itself for the duration of an export and ignores further clicks until it finishes or fails.
- Four of the site's `-720.webp` responsive image variants (the portrait-oriented figures, resized to a fixed height) were declared as `720w` in their `srcset` although they were narrower than 720 pixels. The descriptor is now read from the image's own dimensions.
- Structured data for the book page had several fields hard-coded regardless of the corresponding Theme options field: page count, first-publication date, language and format all now come from their admin fields (`book_pages`, `book_first_pub`, `book_language`, `book_format`). Page count and date are omitted when the field has no parseable value; language is mapped to a language code (falling back to `ms`).
- Activating an SEO plugin (Rank Math and others) previously dropped the book's structured data entirely, since no mainstream SEO plugin has an equivalent Book schema type; a self-contained version of it is now still output on the book page even when a plugin is handling everything else.
- No `<link rel="canonical">` was ever output for category archives or search results; WordPress core's own canonical output only covers singular content. A canonical link is now added for both, when no SEO plugin is active.
- `build.py`: `{{kjv:}}` scripture references and `[[fn:]]` plain footnotes were numbered in two separate passes, so an article that interleaves both got footnote numbers out of reading order (confirmed on 23 of the 33 bundled articles). Both are now substituted together in one left-to-right pass. The bundled `content/articles/*.html` files have not been regenerated in this release, so the 23 affected articles still ship with their existing numbering until `build.py` is rerun with network access (or a populated `kjv-cache.json`) and the output compared against the current files.
- `build.py`: several paths were hard-coded to one specific checkout location and failed silently (exiting successfully with no output) when run from anywhere else. Paths are now resolved relative to the script's own location, and a missing source directory now raises a clear error instead.
- Search snippets, search-term marking, the meta-description save and the glossary index called `mb_stripos()`, `mb_strpos()`, `mb_strrpos()` and `mb_strtoupper()` directly; WordPress core polyfills only `mb_strlen()` and `mb_substr()`, so on hosting without the mbstring extension those were fatal errors. They now go through theme helpers that use mbstring when present and a UTF-8-aware fallback otherwise (checked against Arabic and accented text with mbstring absent).
- README: corrected the article count (33, not 34) and the caching guidance, which described asset URLs as carrying the version in the file name; they carry it as a `?ver=` query string instead, which most but not all caches key on.

## [2.22.2] - 2026-09-22

### Changed

- `inc/options.php` split into `inc/options-fields.php` (field definitions, sanitisation, registration) and `inc/options-render.php` (the tabbed screen markup and field rendering), for readability as the settings screen grows.
- Vendor and font license files (`html2pdf.bundle.min.js.LICENSE.txt`, `html2pdf-LICENSE.txt`, `lightbox2/LICENSE.txt`, `special-elite-license.txt`) moved out of `assets/js/vendor/` and `assets/fonts/` into a new top-level `licenses/` directory, so only runtime code and font files ship under `assets/`.

## [2.22.1] - 2026-09-20

### Changed

- The search is dressed in the first-century layer, and so follows the Ornament switch. The header magnifier sits on a bronze prutah like the counts; the panel is an inscribed limestone tablet closed by a meander band, with the field on parchment; the form carries QVAERE in Cinzel capitals; the result count rests on a meander rule; each result is numbered with a Roman numeral on a prutah, continuing across pages; and the page links are inscribed tablets, the current one a prutah. With the ornament off, the same elements appear as plain ringed numerals and hairlines.

## [2.22.0] - 2026-09-20

### Added

- Site search. A magnifier in a ringed roundel beside the header menu opens a search panel under the header, on every page and on phones; it is built on `<details>` and needs no script. The results page has its own template in the theme's style: a title panel, the search form, the number of results, and for each result its section and label in the typewriter face, its title in Dubidam and an excerpt around the first match with the terms marked, twenty to a page with numbered page links. Articles and pages are searched in full, footnotes included; attachments are left out. A search with no results offers the section links. The 404 page uses the same form.

## [2.21.3] - 2026-09-20

### Changed

- The figure rules now apply to every image in the text. Image blocks added in the editor get the same treatment as the theme's figures: the frame is the image, with no empty space; tall images are held to 80 per cent of the screen height with the caption shrinking to their width; and when linked to the media file they open in Lightbox2 with their caption, grouped with the page's other figures. Lightbox2 now loads on any page with a figure or a linked image.
- Audited every image on all 44 pages at 1440, 1024 and 390 pixels: the title-panel illustrations fill their panels (cropped to cover, never letterboxed), the book covers keep their true proportions, and all 45 figures fill their frames.

## [2.21.2] - 2026-09-20

### Fixed

- Figure frames showed empty bands. The image was sized to fill a box capped at 85 per cent of the screen height and letterboxed inside it on a surface colour, so tall images sat in a frame wider than themselves. The frame is now the image: wide images fill the column at their natural proportion with no height cap; images taller than they are wide are held to 80 per cent of the screen height and the frame and caption shrink to the image's width. Checked on all 45 figures across 35 pages at desktop and phone widths: no empty space in any frame, no caption wider than its image.

## [2.21.1] - 2026-09-20

### Changed

- Figures open in the supplied Lightbox2 (2.12.0, MIT, bundled in `assets/vendor/lightbox2/` with its licence), in place of opening the image file in a new tab: a dark overlay, the image in a limestone frame with a thin stone rule, the caption and credit beneath in Garamond, "Figure 3 of 8" in the typewriter face, and arrows and keyboard navigation through all the figures on the page, wrapping at the ends. Lightbox2 and jQuery load only on pages containing a figure. Without JavaScript the link still opens the full-size image.

## [2.21.0] - 2026-09-20

### Added

- Thirty-one further figures, so that every article carries at least one, each chosen for its page, licence-checked through the Commons API and credited: Cleopatra's Gate at Tarsus; Rembrandt's *Stoning of Saint Stephen*; the Street called Straight; Bab Kisan; Hogarth's *Paul before Felix*; Rembrandt's *Saint Paul in Prison* and *Self-Portrait as the Apostle Paul*; Raphael's *Conversion of the Proconsul* and *Sacrifice at Lystra*; a bust of Nero; the Temple of Apollo at Corinth; the Minaret of ʿĪsā; the Second Temple model; the Library of Celsus; Papyrus 46; Damaskinos's Nicaea icon; Poussin's *Ecstasy of Saint Paul*; the Munich Talmud; Nag Hammadi Codex II; Codex Alexandrinus; the Great Isaiah Scroll; the Dome of the Rock; Pella; the Hezir tomb; a Marinid manuscript of al-Bukhārī; the Umayyad prayer hall; the Great Mosque of Córdoba; the Birmingham Qurʾān; Valentin de Boulogne's *Saint Paul Writing His Epistles*; St Paul's Church, Malacca; and a 1695 map of Paul's journeys on the timeline. Only sources of at least 900 pixels on the short side were accepted; two candidates below that (Codex Sinaiticus, Codex Vaticanus) were replaced. Al-Bīrūnī's illustrated Edinburgh manuscript was excluded because it depicts the Prophet ﷺ.

### Changed

- Figures are larger: all 39 images are now bundled at 1,400 pixels with 720-pixel versions served through `srcset`; figures fill the text column, tall images cap at 600 pixels wide and 85 per cent of the screen height, and every figure opens its full-size image on click.

## [2.20.0] - 2026-09-20

### Added

- Photographs of Paul in the churches, from Wikimedia Commons, each licence checked through the Commons API before download: Caravaggio's *Conversion of Saint Paul* (public domain); the *Traditio Legis* of Santa Costanza (José Luiz, CC BY-SA 4.0); Christ between Peter and Paul in the Catacombs of Saints Marcellinus and Peter (public domain); the Byzantine medallion of Paul at the Metropolitan Museum (CC0); Tadolini's statue before Saint Peter's (AngMoKio, CC BY-SA 2.5); the beheading relief at Tre Fontane (Yong Woo Park, CC BY 4.0); the tomb at Saint Paul Outside the Walls (StPaul.jpg, CC BY 4.0); and the Lateran ciborium (Jastrow, CC BY 2.5). Bundled as WebP at 960 pixels, lazy-loaded.
- Figures in the text, each with a caption tied to the argument and a credit line: Caravaggio in The Damascus road; the catacomb fresco in The Roman; the *Traditio Legis* in The church that followed Paul; Tre Fontane, the tomb and the Lateran in The failed prophet.
- "Paul in the churches," a reference page on the church's portrait of Paul from the catacombs to the Vatican, showing the originals of the sword and the book that the site's illustrations turn against him.
- `inc/figures.php` (registry and `[paulus_figure]`), a `{{fig:…}}` build syntax, figure styles for screen and print, a Photographs section on the sources page, and a limestone variant of the grinning figure for the new page's featured image.

## [2.19.2] - 2026-09-20

### Added

- A "Footer badges" field under Theme options, Front page, taking links and images only (anything else is stripped), rendered under the footer credit by `[paulus_footer_badges]`. It carries the AI Visibility 10/10 Platinum badge for apostleofdoom.org by default, linked to its directory page, lazy-loaded.

## [2.19.1] - 2026-09-20

### Changed

- The English title of the book is "Apostle of Doom: How Paul of Tarsus Undid Jesus" (title and subtitle fields under Theme options, Book), in place of the descriptive rendering used until now. It appears on the book page's gloss line, in the book panel under the Malay title, and as the book's alternate name in the structured data. Sites still holding the earlier wording are updated once.

## [2.19.0] - 2026-09-20

### Added

- `assets/css/print.css`, loaded for print on every page: chrome removed (header, footer, rail, share, series lists, reading-order band, related, book panel, author note); black Garamond on white at 11pt, A4 with 18/16/20mm margins; a ruled head of breadcrumb, title, standfirst and byline; Greek and King James text kept together; quotations, headings and footnote entries kept from breaking across pages; external link addresses printed after their text; references in one column under a rule; the drop cap flattened.
- Print and Save as PDF buttons in the share row of every article, with marks drawn to the icon pack's convention. Print opens the browser dialog. Save as PDF loads the supplied html2pdf.js (0.14.0, MIT, in `assets/js/vendor/` with its licence files) on first use only, builds an off-screen sheet from the title panel and text, and saves `<slug>.pdf` on A4 at double resolution, with page breaks kept out of quotations, headings, rows and list items; if the library fails to load it falls back to the print dialog. The sheet's rules live in theme.css because html2canvas reads screen styles, and they avoid `color-mix()`, which html2canvas cannot parse. Verified on the Damascus road article: the browser print produces a text PDF; html2pdf produces the file in under two seconds.

## [2.18.0] - 2026-09-20

### Changed

- The book panel at the foot of the front page and of every article was a 64-pixel thumbnail beside one sentence and a text link. Since the site exists in part to sell the book, it is rebuilt as a bookseller's panel: the cover at up to 220px, set slightly askew and righting itself on hover, with a deep shadow; the kicker THE BOOK BEHIND THE CASE in Cinzel capitals; the Malay title in Dubidam with the English title beneath in italic Garamond; a sentence that says what the book is (the full argument in print, with its apparatus and sources); the format, pages, publisher and date, and ISBN in the typewriter face; an oxblood Order button carrying the price, opening the publisher's page; and the About the book link. On phones the panel stacks and centres.

## [2.17.1] - 2026-09-20

### Changed

- The share marks are now the supplied Minimalist Social Icons pack (Facebook, X, WhatsApp, Telegram, single-path 24×24 glyphs), kept in `assets/icons/` with the pack's README and inlined by `paulus_icon()` with the fill set to the current colour, in place of the marks I had drawn. The pack has no email glyph, so the theme keeps an email mark drawn to the same convention. Telegram is added to the row.

## [2.17.0] - 2026-09-20

Four reading aids, taken from a review of a long-form site and set inside the theme as it stands.

### Added

- The side rail lists the article's own sections ("In this article") above the parts of the chapter and the apparatus, and it now follows the reader: the rail's inner block is sticky, and the section on screen is marked with a rule in the accent colour. The marking is done by `assets/js/reader.js`, about 1 KB, deferred, loaded on articles only; the theme's first front-end script. Every h2 in an article receives an id through a content filter, so the links work with the script disabled.
- Reading time beside the byline in the title panel: "About 13 minutes," at 220 words a minute, in the typewriter face.
- Share links under the byline: Facebook, X, WhatsApp and email, as plain links with small line-drawn marks in ringed roundels, no scripts and no tracking.
- The reading-order block (study questions, previous, next) is now a full-width limestone band across the page under a meander, set off from the text, in place of a ruled block inside the column.

## [2.16.1] - 2026-09-20

### Added

- Every New Testament passage quoted on the site, 112 of them across the chapters, now carries the original Greek above the King James text: the SBL Greek New Testament (Holmes, 2010), taken verbatim from the published text with the apparatus sigla removed, verse by verse, in the same block as the English and cited with it in one footnote ("Acts 9:3–9 (SBLGNT; KJV)"). Old Testament passages keep the King James Version alone. The Greek is set in EB Garamond, whose Greek range the subset fonts carry, above a hairline.

## [2.16.0] - 2026-09-20

### Added

- "The flesh: licence, lust and the war within," the third part of The character of Paul. It states its limit first: no source records a sexual act of Paul's, so the deviance is documented in the mind and the teaching. Then: the war within (Romans 7; 1 Corinthians 9:27, "I keep under my body," with the Greek for bruising; Galatians 5:17); the vocabulary of lust (Romans 1:26–27; 1 Corinthians 6:9–11 with <em>malakoi</em> and <em>arsenokoitai</em>, the latter Paul's coinage; Galatians 5:19) against the single sentence of ʿĪsā ibn Maryam on the subject; the doctrine of licence (1 Corinthians 6:12, 10:23; Romans 14:14; Titus 1:15) and ʿAbd al-Jabbār's <em>ibāḥah</em>; its fruit at Corinth (incest, 1 Corinthians 5:1–2; prostitutes, 6:15–16; drunkenness at the supper, 11:21); the Ebionite tradition in Epiphanius that Paul apostatized over the high priest's daughter; Spong's reading of a repressed homosexual who never acted, verified against his text, with the book's citations of Ehrman and Engberg-Pedersen and the circumcision of Timothy; and the Islamic measure, <em>fiṭrah</em>, Sūrah al-Rūm 30:21 and al-Nūr 24:32 in Arabic and Saheeh International, al-Ḥadīd 57:27 on monasticism, and the hadith on turning from the Prophet's practice (Ṣaḥīḥ al-Bukhārī 5063).
- A limestone variant of the horned figure in the cap, for the glossary, so the new part takes the face crop. A study question added; Nock on the sources page.

## [2.15.0] - 2026-09-20

The man and The witnesses each hold six pieces, matching the six counts.

### Added

- The man: "The Roman: name, tongue and citizenship" (Saul becomes Paulus in the house of Sergius Paulus, Acts 13:7–9; Greek to the officer and Hebrew to the crowd, Acts 21:37–40; citizenship produced at Philippi and in Islamicjerusalem, Acts 16:37–39, 22:25–29; the appeal to Caesar, Acts 25:11–12; Eisenman on the Herodian alignment) and "The purse: the collection for Islamicjerusalem" (the tentmaker, Acts 18:3; the right to wages claimed and renounced, 1 Corinthians 9; the fund as the price of fellowship, Galatians 2:10, Romans 15:25–27; the suspicion at Corinth, 2 Corinthians 8:20–21, 12:16–18; Felix waiting for money, Acts 24:26, with Bentham; the messengers who ask no reward, Sūrah al-Shuʿarāʾ).
- The witnesses: "James, the brother of ʿĪsā ibn Maryam" (the pillar named first, Galatians 2:9; the judgment at the council, Acts 15:19–21; faith without works, James 2:17–24 against Romans 3:28, with Luther's "epistle of straw" and its later withdrawal; the stoning in 62, Josephus, Antiquities 20.9.1; the witness effaced, Eisenman) and "The Qurʾān as witness" (4:157 on the crucifixion; 5:72–73, 5:116–117 and 19:30 on the divinity; 9:31 and 57:27 on lords and monasticism; 3:78, 5:14 and 2:79 on altered scripture; 61:14 on the disciples; 15:9 and 5:75), given in Arabic with the Saheeh International rendering, fetched from the text before quotation.
- Each article links to the pages that already carry related material and repeats none of it. Two new image variants, limestone duotones of the portrait and the decayed saint. The witnesses' description names the new pieces.

## [2.14.1] - 2026-09-20

### Fixed

- The character chapter as shipped in 2.14.0 repeated material already on other pages: the lie for the gospel, the guile, "all things to all men," the Temple vow, the anathemas, the ledger of sufferings and the rule of silence, all of which the counts already quote in full. It also carried unreferenced generalizations (slaveholders' pulpits, "every Christian empire," "nineteen centuries") and a sentence that misstated Islamic doctrine on the prophets. Both parts are rebuilt. What the counts already carry is now cited in one paragraph with links to the pages that carry it; the chapter keeps only what is new to the site: Bentham on the Temple vow as perjury and on the Sanhedrin "stratagem," the wish of mutilation (Galatians 5:12), "dogs" and "the concision," the pillars dismissed, the whited wall (Acts 23:3–5), the Jews "contrary to all men" (1 Thessalonians 2:15–16), the Cretan slur of the school, Nietzsche's "genius for hatred," "I robbed other churches," the school's rule for women (1 Timothy 2:11–15) with Shaw, slaves and Onesimus, Romans 13 under Nero with Spong, and the verdicts of Jefferson, Bentham, Shaw and ʿAbd al-Jabbār. The closing paragraph now states the doctrine of <em>ʿiṣmah</em> correctly. Titles: "Perjury, curses and the whited wall" and "Slaves, Caesar and the witnesses of conscience." Every claim carries its reference; the unreferenced generalizations are gone.

## [2.14.0] - 2026-09-20

### Added

- "The character of Paul," a two-part chapter in The man, after The mind. The book scatters Paul's moral record across its chapters; the site gathers it, adds what further research supplied, and references every point in the manner of the other articles.
- Part 1, "Lies, curses and guile": the lie that serves the gospel (Romans 3:7), preaching in pretence (Philippians 1:18), guile admitted (2 Corinthians 12:16), the Temple vow sworn against his own letters (Acts 21, with Bentham's charge of "notorious perjury"), the anathemas on the disciples (Galatians 1:8–9; 1 Corinthians 16:22), the wish that the circumcisers mutilate themselves (Galatians 5:12), "dogs" and "the concision" (Philippians 3:2), the pillars dismissed (Galatians 2:6), the whited wall and the plea of ignorance (Acts 23:3–5, with Bentham's "stratagem"), the Jews "contrary to all men" (1 Thessalonians 2:15–16), the Cretan slur of his school (Titus 1:12–13), Nietzsche's "genius for hatred," and "I robbed other churches" (2 Corinthians 11:8).
- Part 2, "Women, slaves and Caesar": boasting and "imitate me" (2 Corinthians 11; 1 Corinthians 4:15–16 against Matthew 23:9), the rule of silence (1 Corinthians 14:34–35; 1 Timothy 2:11–15; Ephesians 5:22–24) with Shaw's "eternal enemy of Woman," slaves commanded to obey and Onesimus returned (Ephesians 6:5; Colossians 3:22; Philemon), the powers ordained of God under Nero (Romans 13:1–4, with Spong), the verdicts of Jefferson ("first corrupter of the doctrines of Jesus," letter to William Short, 1820), Bentham, Shaw ("a more monstrous imposition") and ʿAbd al-Jabbār, and the test of fruits, opened and closed on Sūrah al-Ṣaff 61:2–3.
- Thirty scripture passages quoted in full from the King James Version; Jefferson, Bentham, Shaw and Nietzsche verified against the Founders Online transcript, the 1823 text and the Project Gutenberg editions before quotation. Five study questions; the two new sources on the sources page.

## [2.13.0] - 2026-09-20

The site addresses a global reader; the regional evidence has its own article.

### Added

- "The religion of Paul in Malaysia and Southeast Asia," a fourth piece in The witnesses, after the epistle: the colonial mission in the region, Christian Zionism's arrival (Buchanan), Penang as laboratory (Dewan Negara Hansard, 19 April 2017), the missionary claims as heard in the region, the 2025 MCMC complaint against Impact Evangelism, the Malay-language literature of reply, and the book's place in it. It carries the "Further reading" list of Malay studies that stood on the sources page.

### Changed

- Answers opens "Christian missionaries everywhere"; the Islamic-tradition article says "in Christian usage" where it said "above all in the Malay world"; Count·VI, part 3 has a section "Christian Zionism" ending on the global point, with a pointer to the regional article in place of the Malaysian paragraph and the MCMC footnote.
- The Pendekar Bujang Lapok comparison in Count·III, part 2 stays. Its footnote now explains the film for readers outside the Malay world: the 1959 P. Ramlee comedy, the three bachelors, Tauke Sampan and his itemized bill, and why the joke lands; the text glosses Jalan Ampas as the studio where the film was made.
- The sources page's "Malaysia and Southeast Asia" section becomes "Regional sources," pointing to the article for the works it carries, and its "Further reading" list moves there.
- The witnesses' section description names the regional article. Existing sites gain the article and the edits at the next admin page load.

## [2.12.2] - 2026-09-20

### Changed

- Final sweep of every piece of the site's own text (articles, pages, theme strings, the 404 template, README) against the house writing style: banned vocabulary, dead phrases, contrastive negation, em dashes and contractions. Thirteen phrases corrected ("in order to," "boast," "honestly," "the fact that," nine instances of "no longer"). What remains of those forms is inside quotations, which stand as written.

## [2.12.1] - 2026-09-20

### Fixed

- A stray fragment of markup (`" width="962" height="1024" fetchpriority="high">`) printed as text under the hero portrait since 2.11.0, from a mis-edited image tag. Removed.

### Changed

- The space between the meander band and the hero is reduced from about 60px to about 28px above the kicker and 24px above the portrait on desktop; the hero's top padding and the text block's inner padding are both tightened. On phones the portrait now sits 16px under the band.

## [2.12.0] - 2026-09-19

### Changed

- Editorial pass over all 27 chapters, the Count I article, the first witnesses and the Answers page against the house style, the apologist's voice and the Faruqian register.
- Faruqian register: one to two analytical Latinisms per chapter, each at the pivot it names and never two in a paragraph: <em>prima facie</em> for evidence that shifts the burden (the persecution record, the five hundred witnesses, the private letters); <em>de jure</em> / <em>de facto</em> for the Temple vow against the letters; <em>ex hypothesi</em> for the Damascus inference and the epileptic reading; <em>ipso facto</em> for the self-conferred title; <em>a fortiori</em> for the ledger of sufferings and for Ibn Ḥazm on <em>isnād</em>; <em>petitio principii</em> for the Ephesus defence; <em>sine qua non</em> for consistency of revelation, for miracles and for deeds; <em>a priori</em> for the canon; <em>ex post</em> for typology and for the martyrdom legends; <em>modus vivendi</em> and <em>raison d'être</em> for the accommodation with paganism; <em>modus operandi</em> for the shifting identity and the personal revelations; <em>qua</em> for Paul as theologian and for the conversion as event and as claim; <em>per se</em>, <em>ab initio</em>, <em>in toto</em>, <em>vis-à-vis</em>, <em>mutatis mutandis</em>, <em>pari passu</em>, <em>non sequitur</em>, <em>ipsissima verba</em>, <em>par excellence</em>, and <em>definiens</em> / <em>definiendum</em> for <em>apostolos</em> against <em>rasūl</em>.
- Thirty sentences of the form "no X, but Y" in the site's own prose, a variant of contrastive negation the earlier sweep did not catch, are rewritten as plain assertions. Quotations that carry the form (the King James text, Tacitus, Wells) stand as written.
- Unedited chapters refresh on existing sites.

## [2.11.1] - 2026-09-19

### Changed

- The front-page lede reads "This website sets out the evidence" in place of "The articles here set out the evidence." Sites still holding the earlier wording are updated once.

## [2.11.0] - 2026-09-19

Fixes for the findings in the PageSpeed Insights report of 19 September 2026 (mobile: performance 69, accessibility 91).

### Changed

- Fonts subset to the character ranges the site uses (Latin, Latin Extended, combining marks, transliteration letters, punctuation, Greek where the face has it). EB Garamond regular 147 → 103 KB, semibold 165 → 113 KB; Sabon Next LT regular 100 → 60 KB, bold 102 → 62 KB; Dubidam 40 → 25 KB each. The Arabic fonts are unchanged. Coverage of ʿ ʾ ḥ ṣ ṭ ā ī ū and Greek verified after subsetting.
- Sabon Next LT regular, Dubidam bold and Cinzel are preloaded, so the fonts painted above the fold no longer wait behind the stylesheets; this shortens the critical chain the report measured at 5.6 s and reduces the layout shift from late fonts.
- The hero portrait is recompressed (247 → 168 KB) and served with `srcset` at 480, 720 and 962 pixels, so a phone downloads about 52 KB, down from 247.
- Contrast: small accent text (labels, breadcrumbs, links, footer headings, rail labels) uses a deeper oxblood, #5c1f10, at 4.5:1 on the Ochre ground where the accent gave 3.4:1; muted text deepens from #4a3d2c (3.8:1) to #3a2f22 (4.7:1); the footer credit and text use ink. Headings keep the lighter oxblood, which meets the 3:1 threshold for large text.
- Touch targets: the footer heading links gain the 44-pixel tap area and footer and series links stand further apart on touch screens.

### Server-side, outside the theme

- Cache lifetimes: the report shows no `Cache-Control` on any asset. Set a one-year lifetime for `/wp-content/themes/paulus/assets/`; file names carry the version.
- Compression: the report shows none applied. Enable gzip or Brotli on the server.
- Google Tag Manager (167 KB) and the sconto.cz analytics requests come from plugins, not the theme.

## [2.10.3] - 2026-09-19

### Changed

- Theme header in style.css: Author is MENJ, Author URI https://menj.blog, Theme URI https://github.com/menj. The author name shown under articles and in structured data is unchanged and comes from Theme options.

## [2.10.2] - 2026-09-19

### Changed

- README.md rewritten where it had fallen behind: site architecture with the series and parts, the self-updating installer, the full shortcode table, the current breakpoints (rail at 1240px, arch and colonnade at 861px), the seven illustrations and their variants, the ornament layer, and the Theme options fields added since 2.0. readme.txt description and installation steps updated; it still described eight articles and a manual install.

## [2.10.1] - 2026-09-19

### Fixed

- Title panels varied in height with the shape of their illustration, from 468px under a wide crop to 790px under a square one. The image now fills a column whose height the text sets, at a minimum of 560px, so every article and page panel stands at the same height; the title size and inner spacing are tightened to match. On stacked layouts the image keeps a 16:9 frame.
- On phones the opening paragraph, with its drop cap, is set ragged right and the drop cap is smaller, which removes the wide word gaps that justification produced beside the diamond.

Checked at 1440, 1240, 1024, 861, 768 and 390 pixels on seventeen page types: no overflow, the rail appears only from 1240px, the arch and colonnade only from 861px.

## [2.10.0] - 2026-09-19

Structured data checked against Google's list of supported types and extended.

### Added

- Organization for the publisher (name, URL, logo, address, telephone, email) and WebSite, on every page, joined by id.
- Person for the author, with the biography and a website (new field under Theme options, Book), referenced by every Article and by the Book.
- BreadcrumbList on section pages and on every page below the front page; it was on articles only.
- Image metadata on the Article image: caption, creator, credit and copyright notice, per Google's image-license guidance.
- Article gains publisher, mainEntityOfPage, language, word count, section, and its position in a series.
- Book gains page count, publication date, availability and seller.

### Not applicable

- Recipe, Event, Job posting, Local business, Product, Software app, Video, Course, Dataset, Discussion forum, Q&A page, Speakable, Vacation rental, Math solver, Employer rating, Paywalled content, Movie, Carousel and Profile page: none describes this site's content, so none is emitted. FAQ markup no longer earns a rich result outside government and health sites, so the answers page carries none.

## [2.9.3] - 2026-09-19

### Changed

- On screens up to 700px the hero stacks with the portrait first, above the kicker and headline, as it did below the buttons before.

## [2.9.2] - 2026-09-19

### Changed

- ʿĪsā ibn Maryam is named in full throughout: every article and page, two article titles, the front-page subheading, section descriptions, summaries and the glossary. Only one meta description keeps the short form, where the full name would exceed 130 characters. The forms ʿĪsā al-Masīḥ and, in the hadith, "ʿĪsā the son of Maryam" stand as they were.
- Sites still holding the earlier default subheading and tagline are updated once; unedited articles refresh.

## [2.9.1] - 2026-09-19

### Changed

- The front-page kicker, THE CASE AGAINST PAUL OF TARSUS, is set in spaced Cinzel capitals, matching the site title and section headings.

## [2.9.0] - 2026-09-19

Enhancements taken from the design review, within the theme as it stands: no change to palette, type, layout or section order.

### Added

- Colonnade: hairline rules part the columns of the front-page listings, the footer columns and the previous/next links, as rules part a roll into columns. Part of the ornament layer, so the Ornament switch governs it.
- One arch per page: on wide screens the title-panel illustration sits under a Roman arch with a thin stone surround. The front-page portrait keeps its cutout.
- A side rail beside the article text on screens 1240px and wider: "In this chapter" with the parts in Roman numerals, then "Apparatus" with jump links to the references, the reading order and the section. Narrower screens keep the parts in the title panel.
- A kicker above the front-page headline, "The case against Paul of Tarsus," editable under Theme options, Front page.
- A letter index on the glossary, with an anchor on the first term of each letter.
- Tabular figures wherever numbers stand as figures: breadcrumbs, labels, book details, timeline dates, tables, footnotes.
- The sources page sets in two columns on wide screens.

### Not taken from the review

- The proposed sand and Tyrian-purple palette and the Merriweather and Playfair type stack; the review itself rejects them, and the site's fonts and colors are your choices.
- Matting the front-page portrait as a plate; the cutout on the ground was your decision.
- The mosaic index and arched buttons, which the review cuts.

## [2.8.3] - 2026-09-19

### Changed

- The boxed series panel of 2.8.2 is gone. The parts of a series now sit in the title panel, on a line under the byline in the same typewriter face as the breadcrumb, each with a small numbered ring; and below the text they appear as a "This series" list in the same form as "More from", beside the previous/next links.

## [2.8.2] - 2026-09-19

### Added

- Series navigation (`[paulus_series_nav]`) on every part of a multi-part chapter, above the text and again below it: the series title with its number of parts, then the parts in order, each linked, with the current one marked. The previous/next links read "Previous part" and "Next part" while inside a series.

## [2.8.1] - 2026-09-19

### Changed

- Featured images reassigned so that each matches the topic of its page. The seven illustrations carry seven readings: the portrait is the man himself; the decayed saint with its cracked halo is false sanctity; the horned figure is deception; the dunce is folly; the "bastard" cap is illegitimacy; the horned figure in the cap is disguise; the grinning horned figure is the deceiver triumphant. Each page takes the reading that fits it, and a variant (face, hands, ink, oxblood, mirror) keeps every image distinct. The Islamic tradition runs in oxblood throughout.

| Page | Image | Reading |
| --- | --- | --- |
| who-was-paul-of-tarsus | paul-portrait | the man himself, as on the cover |
| saul-the-persecutor | paul-horned-ink | hatred without cause, in dark ink |
| the-damascus-road | paul-decayed | the cracked halo of a false vision |
| a-self-appointed-apostle | paul-bastard | a title he gave himself |
| a-psychological-portrait-of-paul | paul-portrait-ink | the brooding face of guilt |
| ego-epilepsy-and-narcissism | paul-horned-grin-face | self-mythologizing, grinning |
| at-the-crossroads-of-the-message | paul-portrait-mirror | two portraits, one reversed |
| paul-against-the-disciples | paul-decayed-face | feigned piety in the Temple |
| by-their-fruits | paul-horned-face | the false prophet unmasked |
| the-foundations-of-pauline-doctrine | paul-horned-bastard | a disguise for the pagans |
| the-letters-of-a-man | paul-portrait-hands | the hands that wrote the letters |
| seven-doctrines | paul-horned-hands | doctrine in the wrong hands |
| revelation-or-borrowing | paul-decayed-oxblood | an angel of light, in oxblood |
| borrowings-from-the-rabbis | paul-decayed-hands | an old scroll copied |
| paul-and-the-new-testament | paul-bastard-ink | an illegitimate ownership |
| the-gospels-and-the-new-covenant | paul-horned-bastard-hands | the gospels in his grip |
| twisting-the-scriptures | paul-horned-grin-hands | scripture twisted, and pleased with it |
| paul-the-father-of-a-new-religion | paul-horned-mirror | the church built in his image |
| the-failed-prophet | paul-dunce | the prophecy that failed |
| the-religion-of-paul-today | paul-horned-grin | the deceiver triumphant today |
| the-first-witnesses | paul-bastard-face | rejected by those who knew ʿĪsā |
| paul-in-the-islamic-tradition | paul-portrait-oxblood | the Islamic record, in oxblood |
| the-early-islamic-record | paul-horned-oxblood | the corrupter named, in oxblood |
| from-ibn-hazm-to-al-faruqi | paul-bastard-oxblood | the modern verdict, in oxblood |
| an-epistle-to-the-churches-of-paul | paul-dunce-hands | a letter in his own form |
| answers | paul-dunce-face | the claims answered |
| the-verdict | paul-horned-bastard-ink | the condemnation |
| reference | paul-portrait-face | the study material |
| chronology | paul-decayed-mirror | time-worn |
| glossary | paul-horned-bastard-face | the vocabulary of the case |
| study-questions | paul-horned-grin-ink | questions to put to him |
| sources | paul-bastard-hands | the scroll of sources |
| site-map | paul-decayed-ink | the whole case at a glance |

## [2.8.0] - 2026-09-19

### Added

- All seven of the illustrations supplied for the site are now bundled: the cover portrait, the decayed saint, the horned figure, the dunce, the "bastard" cap, the horned figure in the cap, and the grinning horned figure. Three were unused before.
- Thirty-two variants made from them in the same style: close-ups of the face, close-ups of the hands and scroll, dark ink duotones, oxblood duotones, and mirrored versions of the three images without lettering. Each carries its own alt text.
- Every article and page now has a featured image, 33 in all, chosen to suit the piece: the persecutor in dark ink, the Damascus road on the decayed saint, the psychological portrait on the horned figures, the self-appointed apostle in the cap, the failed prophet in the dunce cap, the Islamic tradition in oxblood. Featured images an editor has set by hand are left alone.

## [2.7.2] - 2026-09-19

### Changed

- Editorial pass over the 27 chapters against the house writing style and the apologist's voice: body paragraphs hold at most three sentences (quotations, footnotes and tables are left whole); Paul's "apostleship" carries scare quotes as a contested claim; Christian proselytism is <em>dakyah</em>; the city is Islamicjerusalem in the site's own prose, with the Jerusalem Council and quoted texts unchanged. The build script now enforces the paragraph limit.
- Chapters refresh on existing sites unless edited by hand.

## [2.7.1] - 2026-09-19

### Fixed

- The sources page listed about 60 works, missed more than 70 cited in the chapters (among them the hadith collections, al-Qummī, Sayf ibn ʿUmar, al-Thaʿlabī, al-Shahrastānī, Pines, Toland, Wells, Aslan, the psychobiographical and neurological studies, and the Roman writers), and carried two works cited nowhere in the book (F. F. Bruce's <em>Paul: Apostle of the Heart Set Free</em> and Craig Keener's Acts commentary). It is rebuilt from the chapter footnotes and the book's bibliography, in eight sections, with the book's further-reading list of Malay studies.
- A name-expansion slip in 2.6.1 had produced "Isma'il R. Isma'il R. al Faruqi" on the sources page and in the epistle. Corrected.

## [2.7.0] - 2026-09-19

The site now carries the whole book.

### Changed

- Every article is rebuilt as a full English chapter with all the quotations the book gives: 88 scripture passages quoted in full from the King James Version, the Talmudic and midrashic texts in Hebrew and Aramaic with transliteration, the Arabic of the hadith and of al-Qummī, ʿAbd al-Jabbār, Ibn ʿAsākir, Ibn Taymiyyah and al-Shahrastānī, and the English of Martin, Jung, Watt, Aslan, al-Attas, Isma'il R. al Faruqi, Akhtar, Spong, 1 Clement, Tacitus, Pliny and Celsus. The articles grow from about 8,000 words to about 40,000.
- Long chapters are split into parts: The vision (3), The mind (2), Count II (2), Count III (3), Count IV (2), Count V (3), Count VI (3), The Islamic tradition (3). Existing slugs stay on the first part of each series, so no link breaks. The verdict is the epilogue in full.
- Overview pages show one card per series with "In N parts"; section pages list every part with "Part n of N"; the reading order runs part by part; study questions link from every part to the series' questions.
- Two comparison tables from the book (the three Damascus accounts; ʿĪsā and Paul side by side), with horizontal scrolling on phones.
- Scripture quotations are styled as left-ruled blocks with verse numbers; Hebrew and Arabic blocks run right to left.
- Where the book quotes an English source in Malay, the footnote notes that the quotation is rendered from the Malay edition.
- Existing sites get the new chapters on the next admin page load. Articles edited by hand are kept.

### Added

- `content/src/` chapter sources and `build.py`, which fetches scripture verbatim and numbers footnotes.

## [2.6.2] - 2026-09-19

### Changed

- "THE BOOK" in the header, and every other button (Read the charges, Order from Langgam Fikir, Install site content), is set in spaced capitals, slightly smaller to compensate.

## [2.6.1] - 2026-09-19

### Changed

- Isma'il R. al Faruqi is named in full wherever he appears: article text, a section heading, footnotes, the sources page, the answers and a meta description. Unedited articles are refreshed on existing sites.

## [2.6.0] - 2026-09-19

Applies the on-page practices in Google's Search Engine Optimization Starter Guide.

### Added

- Meta descriptions for every article and page, each under 130 characters and ending with a call to action, stored as `_paulus_meta` and editable from a "Search description" box in the editor. The front page's description is a Theme options field.
- Open Graph and Twitter card tags: title, description, URL, type and image.
- Structured data: BreadcrumbList and Article on articles; Book, with ISBN, publisher and offer, on the book page.
- A 404 template with links to every section, the answers, the verdict, the book and the site map, plus a search box.
- An HTML site map page at `/site-map/`, listing the sections with their articles and every page, linked from the footer.
- Descriptive alt text for each bundled illustration, applied to the hero image and to the media library copies.
- The installer sets word-based permalinks (`/%category%/%postname%/`) when a site still uses plain ones.

### Changed

- The book page's title is its `<h1>`; it had none. Article titles in section listings are `<h3>` under the section's `<h2>`, so heading levels no longer skip.
- The earlier Open Graph image function moved into the new `inc/seo.php`, and every head tag there yields to an active SEO plugin.

## [2.5.4] - 2026-09-19

### Changed

- Editorial pass over every English article and page against the house writing style. Sentences that negated one framing before asserting another were rewritten as plain statements, in the profile of Paul, the article on the new religion, The Verdict, Answers and the glossary.
- The theme now records the text of each article as installed. On later updates, an article whose text still matches what the theme shipped is refreshed from the new file; an article edited by hand is left alone. The hashes of every file shipped in 2.5.3 are included, so this works on sites installed earlier.

## [2.5.3] - 2026-09-19

### Fixed

- Structural changes shipped since 1.8.0 (the five-item menu, the third witness, the Roman numeral labels, the new section description) were not reaching sites where the one-time version upgrades had not run. The theme now carries a `content_version` in its manifest and, whenever an administrator loads any page, admin or front end, applies the structure if that version differs from the one recorded. Existing text is never overwritten.
- The witnesses section description is refreshed when it still holds the pre-2.2.0 wording.
- The Content tab shows the structure version on the site beside the one shipped with the theme.

## [2.5.2] - 2026-09-19

### Fixed

- An empty band of about 90 pixels sat between the header and the hero, from top padding on the hero and a block gap WordPress adds above the main content. Both are removed. The portrait now rises to just under the meander band, and the headline block centers on it.

## [2.5.1] - 2026-09-19

### Fixed

- The count roundels showed "COUNT" with no numeral. The numeral was read from the stored label by a byte-level string function, which failed on the middle dot and on sites whose labels still read "Count 3". Labels are now split in PHP into word and numeral, with Arabic digits converted to Roman, and the numeral is real text on the coin, where it had been a CSS attribute.

## [2.5.0] - 2026-09-19

### Added

- Ornament layer (`assets/css/ornament.css`) giving the site a first-century Roman Judaea feel, all drawn in code: parchment grain on the ground and limestone on the header and footer, Herodian meander bands in place of the plain rules, six-petal ossuary rosettes under section titles and around each article's first letter, the six counts numbered on bronze prutah roundels above wave-scroll borders, article titles on an inscribed stone tablet with interpunct breadcrumbs, an oil-lamp flame on the FAQ, and the footer credit in a tabula ansata.
- "First-century ornament" switch under Theme options, Colors, on by default. Colors, fonts and layout are unchanged either way.

### Changed

- The hero portrait is larger: its column is now the wider of the two, the transparent margins are trimmed from the cutout, and the figure aligns to the right edge.

## [2.4.1] - 2026-09-19

### Added

- A 512-pixel site icon cropped from Paul's portrait (`assets/images/site-icon.png`). The installer, and a one-time upgrade on existing sites, sets it as the WordPress site icon when none is set, so WordPress serves it as the favicon, Apple touch icon and app icon. It can be changed under Settings, General, Site Icon.
- Until a site icon exists, the theme links its own icon file as a fallback favicon.

## [2.4.0] - 2026-09-19

### Added

- Cinzel (SIL Open Font License), a typeface modeled on first-century Roman inscriptions, bundled as a variable WOFF2 with its license. It sets the site title in the header and footer, the front-page headline, the section titles (The charges, The man, The witnesses, the answers heading) and the count labels.
- The six counts are numbered in Roman numerals, Count·I to Count·VI, with the interpunct Roman stonecutters used between words. Existing sites are renumbered on the next admin page load.

### Unchanged

- Dubidam keeps the article titles, the hero subheading, FAQ questions and buttons; Sabon Next LT, EB Garamond, Special Elite and Arslan Wessam keep their roles; the Ochre colors stay.

## [2.3.0] - 2026-09-19

### Changed

- The font roles from before 2.0.0 are restored. Dubidam sets headings, the site title, section titles, article titles in lists, FAQ questions and buttons, in normal case. Special Elite sets the labels again: chapter numbers, breadcrumbs, bylines and data labels. The menu is back in Sabon Next LT.
- The Ochre scheme uses its original colors throughout: header, footer, cards and the article title panel sit on the ochre ground with dark ink rules, headings are oxblood, and the dark brown title panel is gone.
- The theme.json palette defaults and the theme screenshot match.

## [2.2.1] - 2026-09-19

### Changed

- Ochre, the book-cover scheme, is the default again, and the theme.json palette defaults match it. Vellum remains available as an option.
- The 2.0.0 upgrade no longer moves sites from Ochre to Vellum. Sites it already moved return to Ochre on the next admin page load.
- The drop-cap diamond is drawn in oxblood in the Ochre and Paper schemes, matching their accent.

## [2.2.0] - 2026-09-19

### Added

- "The first witnesses against Paul," a third article in The witnesses, drawn from chapter 9 of the book: the Ebionites, the Nazarenes, the Elkesaites, the Pseudo-Clementine writings, Symmachus, and the early Arabic source published by Shlomo Pines. It opens the section, so the witnesses run in historical order.
- `paulus_run_install()`, the installer's work separated from the button handler so upgrades can add content.

### Changed

- The witnesses now lists three articles, matching The man, with the labels The early church, The Islamic tradition and Testimony. The section description names all three.
- Existing sites with the content installed get the new article, labels and description on the next admin page load. A section description edited by hand is kept.

## [2.1.0] - 2026-09-19

### Changed

- The answers section on the front page is an FAQ accordion. It shows six questions (set with `count`), each opening to its short answer and a link to the article with the full evidence. It is built on the native `<details>` and `<summary>` elements, so it needs no script and works with the keyboard and screen readers.
- Questions and answers are read from the Answers page (`paulus_answers()`), so editing that page updates the accordion. Footnote markers are left out on the front page; the Answers page keeps them with their references.

## [2.0.0] - 2026-09-19

A redesign modeled on the conventions of a scholarly journal site.

### Added

- Vellum color scheme, now the default: warm cream ground, white header and footer, ink text, rust headings, gold rules and diamonds, and a dark brown title panel. Every scheme gains four colors for this: gold, rule, panel and on-panel.
- Title panel (`[paulus_page_hero]`) on articles, pages and section pages: a meta line with gold diamond separators, the title, the standfirst, a gold rule and the byline. Articles with an illustration show it on the left half; others run the panel full width.
- Drop cap in a gold diamond on the first paragraph of each article.
- "References" heading over the footnotes, set in two columns on wide screens.
- About the author, centered under each article, with a new Theme options field.
- "More from" list of the other articles in the same section (`[paulus_related]`).

### Changed

- Headings move to Sabon Next LT semibold; Dubidam becomes the interface face, uppercase and tracked, for the menu, meta lines, labels, buttons and section headings.
- Section headings on the front page are centered with a short gold rule beneath, and articles are listed as centered cards; the six charges sit on white cards topped with gold.
- White header and footer bars with hairline rules replace the heavy black rules.
- The book panel loses its frame; the cover casts a soft shadow.
- Quotations are centered and italic; section headings inside articles are rust.
- Sites still on the Ochre scheme move to Vellum once; any other choice is kept. Ochre remains available under Theme options, Colors.
- One chapter now opens "The Prophet ʿĪsā" so its drop cap falls on a letter, and the epistle's framing note is marked so it takes no drop cap. Both are updated on existing sites only if never edited.
- Theme screenshot recaptured.

## [1.9.0] - 2026-09-19

### Changed

- "The book" in the header menu is a call-to-action button in the accent color, and in the phone menu it sits below the other links as a button. The installer marks it with the `paulus-nav-cta` class; existing sites get the rebuilt menu on the next admin page load.
- Buttons have rounded corners, set once by the new `--t-radius` variable (6px).
- Running text is justified with automatic hyphenation: article and page prose, footnotes, the glossary, summaries, section descriptions, the hero introduction, the book synopsis and the footer credit. Headings, labels, menus, buttons and Arabic blocks keep their own alignment.

## [1.8.0] - 2026-09-19

### Added

- Footer menu in two columns: "The case" (The man, The charges, The witnesses, The Verdict) and "Reference" (Timeline, Glossary, Study guide, Sources). It is generated from the site structure by the new `[paulus_footer_nav]` shortcode, so it needs no menu editing.

### Changed

- The header menu has five items: The man, The charges, The witnesses, Answers, The book. The Verdict and the Reference dropdown move to the footer.
- With five items the full header menu fits down to 681 pixels, so tablets in portrait show it again. The menu button now takes over from 680 pixels down.
- Existing sites get the new header menu on the next admin page load.

## [1.7.2] - 2026-09-19

### Added

- An admin notice with an install button, shown until the articles and the book page exist.
- A front-page prompt with the same button, visible to administrators only, in place of the empty sections. Installing from the front page returns to the front page.

### Fixed

- A newly activated theme showed only the hero on the front page, with no sign that the content still had to be installed from Theme options.

## [1.7.1] - 2026-09-18

### Fixed

- The hero portrait showed as a square of its own ochre, a shade off the page background in the Ochre scheme and plainly boxed in the others. The hero now uses a transparent cutout (`assets/images/paul-portrait-cutout.webp`) with the background removed and the figure, sword, scroll and cross intact, so it sits on the page in every color scheme.

### Added

- `paulus_hero_image_url()`, which prefers a bundled `-cutout.webp` version of the chosen hero image.

## [1.7.0] - 2026-09-18

### Added

- Page template (`templates/page.html`) for Answers, The Verdict and the reference pages, using the same title, image and prose styles as articles. These pages previously fell back to Twenty Twenty-Five's smaller body text.
- Documented breakpoints: 1024, 860, 700 and 560 pixels.

### Changed

- From 600 to 860 pixels, the menu button replaces the header menu, which had wrapped onto two ragged lines.
- The menu overlay takes the active color scheme, left-aligned in the display font, with the Reference pages indented beneath it. It was white with right-aligned links.
- The hero and book panel stay in two columns down to 701 pixels, so tablets in portrait no longer show a stacked hero with a half-width image.
- Book details fit two or three to a row on tablets and phones.
- Small inline links get a 44-pixel tap area on touch screens.

## [1.6.1] - 2026-09-18

### Changed

- The theme screenshot (`screenshot.png`, 1200 × 900) now shows the front page as the theme renders it, where it previously showed the book cover.

## [1.6.0] - 2026-09-18

### Changed

- Illustrations are cut back to four and placed deliberately: the portrait for the hero and the book, the decayed saint for the Damascus road and the verdict, the horned figure for the psychological portrait, and the dunce for the satirical epistle. The other seven articles have no illustration.
- The front page and section pages list articles as text cards, matching the numbered list of charges.
- The hero image choice in Theme options offers only the four remaining images. A site set to a removed image falls back to the portrait.
- The installer, and a one-time upgrade on the next admin page load, bring article illustrations in line. Only images the theme itself installed are changed; a featured image chosen by hand is kept.

### Added

- `og:image` and `twitter:card` tags for link previews when no SEO plugin (Yoast, Rank Math, All in One SEO, SEOPress) is active.
- `style="text"` and `part="current"` options for `[paulus_parts]`.

### Removed

- The three "BASTARD" caricatures, from the theme and from every place they appeared.
- The portrait gallery and its two Theme options fields. The `[paulus_gallery]` shortcode now outputs nothing.

## [1.5.0] - 2026-09-18

### Added

- Format field (Paperback) in the book details, with the extent (xxvi + 195 pages) and price (RM 45.00) filled in from the book's catalog record.

### Changed

- The book page gives the synopsis, the structure and themes, and the table of contents in Malay, marked `lang="ms"`. The chapter names match the printed book. A short English note introduces them, and the author note stays in English.
- The description in the book panel is now the opening question of the Malay synopsis, set in italic EB Garamond. The Theme options field is renamed "Synopsis (Malay)".
- The title is written "Paulus: Perosak Risalah Al-Masih" throughout, matching the cover.
- Sites still holding the old defaults are updated once on the next admin page load: the synopsis, the title, the empty extent, format and price, and the book page text if it was never edited.

## [1.4.0] - 2026-09-18

### Added

- Answers page: short replies to eight common missionary claims, each linked to the article with the full evidence.
- `[paulus_link]` shortcode, which links to an article or page by slug so links survive permalink changes.
- `[paulus_answers_teaser]` shortcode, used on the front page.
- A numbered "counts" style for `[paulus_parts]`.

### Changed

- The site no longer follows the order of the book. It is arranged as a case file: The man (profile, the vision, the mind), The charges (six numbered counts), and The witnesses (Muslim scholarship and the former follower's letter).
- The epilogue is now a standalone page, The verdict.
- Two articles are retitled for their new place: "Two portraits of ʿĪsā" (Count 1) and "The law as a curse" (Count 3).
- The front page leads with the six counts, then the man, the answers and the witnesses. The hero button reads "Read the charges."
- The study guide is arranged by article, with anchors on article slugs, and the first question of each set now stands on its own.
- The installer rebuilds the primary menu when the structure changes, and takes sections and articles that are no longer used out of the reading order.

### Removed

- The Start here page from new installs. The front page now does its job.

## [1.3.1] - 2026-09-18

### Changed

- The front-page heading is now the site name, Apostle of Doom. The former heading, "Paul of Tarsus and the corruption of the message of ʿĪsā," becomes a subheading beneath it, with its own field in Theme options.
- Sites still showing the old default heading are updated once on the next admin page load. A heading edited by hand is left alone.

## [1.3.0] - 2026-09-18

### Added

- Bundled fonts in `assets/fonts/`, declared in the new `assets/css/fonts.css`: Dubidam for headings, Sabon Next LT for body text, EB Garamond for footnotes and quotations, Special Elite for labels, and Arslan Wessam for Arabic script.
- theme.json presets for the label, Garamond and Arabic families.

### Changed

- Google Fonts removed. The theme makes no requests to third-party font servers.

### Fixed

- The ʿayn in ʿĪsā and other transliterated names now renders from EB Garamond, which has the glyph; it had fallen back to an unrelated system font.

## [1.2.4] - 2026-09-18

### Fixed

- Paragraphs in chapters and pages sat indented from their headings. Prose now uses the theme's content width throughout.
- The publisher's contact block on the book page had large gaps, because WordPress wrapped the shortcode markup in extra paragraphs. Shortcode output is now compacted.
- Chapter illustrations lost the top of the head in the 16:9 crop. The single-chapter image is now 3:2 and anchored near the top.

## [1.2.3] - 2026-09-18

### Changed

- The order link now defaults to the book's page at Langgam Fikir, and the button reads "Order from Langgam Fikir".
- Sites that saved Theme options with an empty order link get the new link once, on the next admin page load. A link set by hand is left alone.

## [1.2.2] - 2026-09-18

### Changed

- Editorial pass on every article, page, excerpt and part description against the house anti-AI writing style. Long paragraphs are split to three sentences at most, and meta commentary ("the subject of this article", "the articles that follow") is cut.
- Vague attributions now name their sources: Hyam Maccoby and Gerd Lüdemann in chapter 1, Arzy and Schurr in chapter 8. Unsupported claims about "neurologists" and "several modern readers" are removed.
- Coordinated triads and an anaphoric run on Start here are rewritten.
- "Saviour" corrected to the US "savior" outside al Faruqi's own term "saviourism."

## [1.2.1] - 2026-09-18

### Added

- The installer sets the site title to Apostle of Doom on its first run, and sets the tagline if it is still empty or the WordPress default. Later runs leave both alone.

### Changed

- Start here and the theme description name the site as Apostle of Doom.

## [1.2.0] - 2026-09-18

### Added

- Site architecture modeled on the book: four parts (The man, The break, The sources, The verdict) as ordered categories, each holding its chapters in reading order.
- Four new chapters from the book: At the crossroads of the message (chapter 1), How Paul came to own the New Testament (chapter 6), the epilogue, and the afterword epistle.
- Start here page with the full table of contents.
- Reference section: timeline, glossary, study questions for every chapter, and sources from the book's bibliography.
- Primary navigation menu created by the installer, with Reference as a submenu.
- Breadcrumbs on chapters, previous and next links in reading order, and a link to each chapter's study questions.
- Part archive template listing chapters in reading order.
- Page excerpts, used as summaries on the Reference index.

### Changed

- The front page groups chapters by part.
- The hero button now leads to Start here.
- The installer updates structure on every run: part assignment, reading order and page hierarchy. The 1.0 chronology page moves under Reference.
- All site text, admin text and documentation use US English spelling and punctuation.

## [1.1.0] - 2026-09-18

### Added

- `[paulus_book_teaser]` shortcode: a compact cover, title and link pointing to the book page.

### Changed

- The book's details (description, bibliographic data, order link and publisher contact) now appear only on the book page. The front page and single articles show the teaser in their place.
- The hero's "About the book" link is hidden when the book page does not exist, where it previously fell back to an anchor on the front page.

## [1.0.1] - 2026-09-18

### Added

- Admin notice when Twenty Twenty-Five is missing or older than 1.5.

### Changed

- Minimum WordPress version raised from 6.5 to 6.7 to match the parent.
- Paulus stylesheets now load after the parent stylesheet.

### Fixed

- Parent templates and patterns lost their colors because the child palette dropped the accent-1 to accent-6 presets. The presets are restored and follow the active Paulus scheme.
- The parent's Manrope and Fira Code font presets are restored as aliases, so parent blocks that reference them keep a defined font.

## [1.0.0] - 2026-09-18

### Added

- Twenty Twenty-Five child theme with front page, single article and Book landing templates.
- Tabbed Theme options screen covering the book, the publisher, colors, the front page and content installation.
- Five named color schemes: Ochre, Oxblood, Ink, Paper and Graphite.
- Starter content installer with 8 articles, a book page and a chronology page, plus bundled illustrations.
- Shortcodes for the hero, the book feature, the portrait gallery and the footer colophon.
