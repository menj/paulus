# Upgrading Paulus

How to move a live site to a new version, and what each update does and does not change. Read the section for every version between the one installed and the one you are installing.

## The routine for every update

1. Back up the database. The structure sync writes to posts, post meta, term meta and options.
2. Upload the new zip under Appearance, Themes, Add New, and choose "Replace current with uploaded". Zips are named `paulus-<version>.zip`, with dots in the version.
3. Open any page as an administrator. The structure sync runs once when the manifest's `content_version` differs from the one recorded on the site; a release that leaves it alone runs no sync. Appearance, Theme Options, Content shows both versions afterwards; they should match.
4. Clear every page cache (plugin, server and CDN).

To update over FTP instead:

1. Switch Maintenance mode on (Theme Options, Maintenance), and turn on hidden files in the FTP program (in FileZilla: Server, Force showing hidden files), so the `.htaccess` files go up.
2. Upload the new `paulus` folder into `wp-content/themes/` as `paulus-new`.
3. Rename the live folder to `paulus-old` and at once `paulus-new` to `paulus`, with Appearance, Themes closed, at a quiet hour. A page asked for between the two renames finds no theme, and that screen switches the site to another theme if it finds the folder missing.
4. Open any page as an administrator and check the site. If WordPress switched to another theme during the renames, activate Paulus again under Appearance, Themes; while another theme runs, end maintenance by deleting the file `.paulus-maintenance` in the site's folder over FTP.
5. Switch Maintenance mode off and delete `paulus-old`.

An upload straight into the live folder works under Maintenance mode. The theme's own check covers only the PHP files in `inc/`, so keep maintenance on until the last file is up.

Never upload WordPress core to a live folder without `wp-config.php` in place. The installer answers anyone in that state.

## What the sync changes, and what it leaves alone

The sync writes the theme's text and settings only where the site still holds something the theme shipped. Anything edited by hand stays as it is.

| Field | Updated when |
| --- | --- |
| Article and page body | It still matches a shipped version (`prior_hashes` in the manifest, or the hash recorded at install) |
| Title, excerpt | Each still matches what the site last received for it |
| Search title, meta description | The field is empty or still holds a value the theme shipped (`prior_meta` in the manifest) |
| Section search title and description | As above, on the section's term meta |
| Front-page meta description option | The saved option still holds an earlier shipped default |
| Category, menu order, page template | Each still matches what the site last received |
| Featured image | It is one the theme installed; an image set by hand is kept |

To find which of your pages kept hand-edited text, compare them with the `content` of the files in `content/articles/` (JSON from 2.62.38).

## Templates customised in the Site Editor

WordPress stores a template edited in the Site Editor in the database, and from then on ignores the theme's template file. An update to that template in the theme never reaches the site. If you have customised any of these, reset them (Appearance, Editor, Templates, the template's menu, "Reset") or apply the change by hand:

| Template | Changed in | Change |
| --- | --- | --- |
| Single | 2.26.5 | `[paulus_series_nav]` removed from below the text |
| 404 | 2.32.0 | Heading and first paragraph replaced by `[paulus_404_quip]` and a plain paragraph |
| Front page | 2.77.7 | Sections reordered as a story (the man, the charges, the witnesses, answers, the Islamic dilemma, the book), each wrapped in `[paulus_story section="…" step="…"]`, which now prints the section wrapper the block groups used to |

## Notes by version

### 2.77.24 to 2.77.25

Upload the whole theme folder. No setting changes. WCAG 2.2 AA register (scratch WordPress, `WP_DEBUG` on with an empty log; axe-core with wcag2a, wcag2aa, wcag21a, wcag21aa, wcag22aa and best-practice tags at 1280 and 375 px on 12 templates and the open search panel and menu; keyboard walk, 320 px reflow, text-spacing override):

| Id | Severity | WCAG | Component | Finding | Status |
|---|---|---|---|---|---|
| PA-01 | Major | 3.1.2 | Article content | Inline Arabic and Greek words without `lang` | Fixed in `inc/script-lang.php` |
| PA-02 | Minor | 1.3.1 | wp-login.php | No `main` landmark in core's login markup | Manual review, core markup |

Everything else passed: no axe violations on the front end, no hidden focus, no reflow overflow at 320 px, `prefers-reduced-motion` honoured. Manual review still open: a screen-reader pass over the lead and list tabs, the lightbox and the PDF export. The code audit found no state-changing handler without nonce and capability check and no remote assets. Licences are in `LICENSES.md`. Folder name: nothing in the code assumes `paulus`; none renamed.

### 2.77.23 to 2.77.24

Upload the whole theme folder. The new file is `assets/css/mobile-first.css`. Nothing else to do. Audit: scratch WordPress with seeded long titles, URLs, a wide table, code, an embed, a gallery and comments; Playwright at 320, 360, 375, 390, 414, 480, 768, 1024 and 1280 px over 14 page types, axe-core 4.

| Id | Severity | Risk of fix | Finding | Status |
|---|---|---|---|---|
| MF-01 | High | Low | A long word in a hero title, standfirst, chapter title, excerpt or snippet widened the page (scrollWidth 937 to 2515) on four page types, at every width | Fixed |
| MF-02 | High | Medium | Bare tables and `pre` in pages and posts overflowed and had no keyboard scroll (`scrollable-region-focusable`) | Fixed (wrapper filter and CSS) |
| MF-03 | Medium | Low | 73 targets under 24 px at 375 px (timeline links, contents list, footnotes, heading anchors) | 38 left (skip link, core markup, inline links) |
| MF-04 | Low | Low | Footnote text at 15.2 px | Kept at 15 px minimum, flagged |
| MF-05 | Low | Medium | Kickers, labels and colophon at 12 to 13.6 px | Manual review, design choice |
| MF-06 | Low | Medium | Chronology page layout shift 0.075 at 375 px | Manual review |
| MF-07 | Info | None | Header tools row at 320 px fits (portrait, search, menu); no change needed | Verified |

Overflow count per width before and after: 4 to 0 at every one of the nine widths. axe: `scrollable-region-focusable` gone, nothing new; the login page keeps core's `landmark-one-main` and `region`. Desktop pages grow 5 to 74 px where list links take the 24 px minimum.

Remaining risks: the filter changes the markup of tables in `the_content` (a labelled group wrapper); the `anywhere` wrapping could split an unbroken string in a narrow card. Follow-up: raise the 12 to 13.6 px labels, reserve space on the chronology page.

### 2.77.22 to 2.77.23

Upload the whole theme folder. The new file is `assets/js/lead-list-enter.js`. The Journal tab gains three lead and list settings, all on, with no accent override. Nothing else to do.

### 2.77.18 to 2.77.19

- Upload the whole theme folder. Five articles change their English quotations from the King James Version to the English Standard Version, and `content_version` moves to 2.77.19, so the structure sync runs and refreshes each of them where it still matches a shipped text. Text edited by hand stays. Nothing else changes.

### 2.77.17 to 2.77.18

- Upload the whole theme folder. Four pages change their English quotations from the King James Version to the English Standard Version, and `content_version` moves to 2.77.18, so the structure sync runs and refreshes each of them where it still matches a shipped text. Text edited by hand stays. Nothing else changes.

### 2.77.16 to 2.77.17

- Upload the whole theme folder. Six articles change their English quotations from the King James Version to the English Standard Version, and `content_version` moves to 2.77.17, so the structure sync runs and refreshes each of them where it still matches a shipped text. Text edited by hand stays. Nothing else changes.

### 2.77.15 to 2.77.16

- Upload the whole theme folder. Seven articles change their English quotations from the King James Version to the English Standard Version, and `content_version` moves to 2.77.16, so the structure sync runs and refreshes each of them where it still matches a shipped text. Text edited by hand stays. Nothing else changes.

### 2.77.14 to 2.77.15

- Upload the whole theme folder. Nothing else changes. On a site in a subfolder, put a copy of `assets/images/favicon.ico` in the domain root (or, with a Site Icon set, that icon converted to an ICO file named `favicon.ico`), since browsers ask for `/favicon.ico` there.

### 2.77.13 to 2.77.14

- Upload the whole theme folder. The new files are `inc/lead-list.php`, `assets/css/lead-list.css` and `tests/lead-list-test.php`. `content_version` stays at 2.77.3, so the structure sync does not run.
- (Superseded in 2.77.20: the block is for featured entries only.) The Journal opens with the newest entry large and the next five beside it. It needs no setting. The Journal template is unchanged, so a copy customised in the Site Editor shows it too, as long as it keeps `[paulus_journal_list]`.

### 2.77.12 to 2.77.13

- Upload the whole theme folder. The new files are in `assets/images/`: `favicon.ico`, `favicon.svg`, `favicon-32.png`, `apple-touch-icon.png`, `icon-192.png`, `icon-512.png` and `site.webmanifest`. Open one admin page so the error pages are written again with the icon.
- The installer sets the site icon to the portrait, so most sites keep WordPress's own icon tags. The theme's set covers a site with no site icon. If a `favicon.ico` file sits in the site root, the web server sends it and the theme never answers.

### 2.77.11 to 2.77.12

- Upload the whole theme folder. No file is added or removed, and `content_version` stays at 2.77.3, so the structure sync does not run.
- The Study guide is an FAQPage of its questions again, with its `Quiz`, as 2.77.10 built it. Search Console will report its questions as FAQ items missing their answers once Google reads the page again. The owner keeps the markup for generative, answer and AI search (the decision of 2026-11-03), so leave that report as it is.

### 2.77.10 to 2.77.11

- Upload the whole theme folder. No file is added or removed, and `content_version` stays at 2.77.3, so the structure sync does not run.
- The Study guide is a WebPage again, with its `Quiz`, and no longer an FAQPage. Search Console reported each of its questions as an FAQ item missing its answer; that report clears once Google reads the page again. You can ask for this under URL Inspection.

### 2.77.9 to 2.77.10

- Upload the whole theme folder. The new files are `LICENSE`, `inc/article.php`, `inc/hardening.php`, `assets/css/article.css` and, in a working copy, `tests/article-test.php`. `content_version` stays at 2.77.3, so the structure sync does not run.
- Articles show their dates under the byline at once. Key takeaways and "Last reviewed" show once you fill them in the Key takeaways and review box on an article's screen.
- Theme Options, Reading, has two new switches, both on: Key takeaways, and the one line before the third section. Security has "Hide the user list from visitors", on.
- The REST API's user routes now answer 404 to visitors who are not logged in, and `?author=` addresses go to the About the author page. If a service outside the site reads `/wp-json/wp/v2/users` without logging in, switch the Security option off.
- The theme is now under the GNU General Public License, version 3 or later.

### 2.77.8 to 2.77.9

- Nothing to run. The release adds `SSOT.md` and changes the documents only. `content_version` stays at 2.77.3, so the structure sync does not run.
- As after any version change, the first request removes development files from the theme folder, and the first admin page runs the image housekeeping and rewrites the error pages.

### 2.77.7 to 2.77.8

- **Upload the whole theme folder, the new `.htaccess` in it included** (FTP clients often hide files that begin with a dot; turn on "show hidden files"). On the first page load afterwards the theme removes `.git`, `.claude`, `tests`, `tools` and `docs` from its folder on the server.
- If you run the theme from a git working copy on a live site, add `define( 'PAULUS_KEEP_DOTFILES', true );` to `wp-config.php` first.
- The first admin page writes the "Paulus: deny direct core files" block into the site's `.htaccess`. If a plugin or the host needs one of those files by address, add `define( 'PAULUS_ALLOW_CORE_FILES', true );` to `wp-config.php`; the next admin page within the hour takes the block out.

### 2.77.6 to 2.77.7

- **Upload the whole theme folder.** Nothing to run, and no article changes.
- A front page customised in the Site Editor keeps its own order and shows no step lines. Reset it to take the new order, or wrap each section in `[paulus_story]` by hand.

### 2.77.5 to 2.77.6

- **Upload the whole theme folder.** The first admin page writes the copies of the static error pages into `wp-content/paulus-errors/`.
- If `.htaccess` carries the old `ErrorDocument` lines, which point at the theme's `errors/` folder, replace them with the lines from Theme Options, Content. The old lines still work on a site at the domain root.

### 2.77.0 to 2.77.1

- **Upload the whole theme folder.** Nothing to run.

### 2.76.0 to 2.77.0

- **Upload the whole theme folder.** The new files are `errors/*.html` and `bin/build-error-pages.php`. The first admin page rewrites the error pages in `wp-content` with the new database wording.
- To use the static pages, add the `ErrorDocument` lines from Theme Options, Content, to `.htaccess`.

### 2.75.1 to 2.76.0

- **Upload the whole theme folder.** The new file is `assets/js/unlist-quick-edit.js`.
- The first page load after the update rebuilds the rewrite rules once. Clear caches afterwards so old `?paged=` search addresses are not served from a cache.
- If Reading Progress Bar, Unlist Posts & Pages or Pretty Search Permalinks is active, the theme leaves that feature to the plugin. Deactivate the plugin to use the theme's own.

### 2.69.0 to 2.70.0

- **Upload the whole theme folder.** The new file is `inc/renamed.php`; without it the maintenance page shows until the upload is whole.
- On the first page load as an administrator, the sync renames the 2023 edition to `/appendices/the-first-edition/`. The old address answers with a 301, as do `/site-map/` and the old Ibn Hazm address. Clear caches afterwards.

### 2.65.7 to 2.66.3

- **Upload the whole theme folder.** This release combines the 2.66.x work (the Security tab, the contextual links and sixty new photographs; see below) with the 2.65.x releases of `main`. Read the three notes below, 2.66.1 to 2.66.2, 2.66.0 to 2.66.1 and 2.65.6 to 2.66.0, before updating: the Security tab is on by default and changes how XML-RPC and the REST API answer.
- The articles that gained photographs refresh on the first page load as an administrator, where they still match a shipped text. Clear caches afterwards.

### 2.67.3 to 2.67.4

- Upload the whole theme folder, including the new `inc/watch-report.php` and `assets/css/watch.css`; a partial upload without the first shows the maintenance page until it is there. Nothing in the content changes.
- The panel looks different on the dashboard; no setting is involved.

### 2.67.2 to 2.67.3

- Upload the whole theme folder. Nine pages change their English quotations from the KJV to the ESV; the sync refreshes each where it still matches a shipped text, and hand-edited text stays.

### 2.67.1 to 2.67.2

- Upload the whole theme folder. Ten articles change (seven converted to the ESV, three with corrected captions); the sync refreshes each where it still matches a shipped text, and hand-edited text stays.

### 2.67.0 to 2.67.1

- Upload the whole theme folder. Six articles change their English quotations from the KJV to the ESV; the sync refreshes each where it still matches a shipped text, and hand-edited text stays. Nothing else changes.

### 2.66.8 to 2.67.0

- Upload the whole theme folder. The three new Journal entries are created on the first page load as an administrator after the update; WordPress holds each as scheduled and publishes it on its date, which relies on WordPress's scheduler running (a visit to the site on or after the day). Upload before 8 November 2026 so the first goes out on time.
- The Sources page and "Was Paul a Pharisee or a Sadducee?" refresh where they still match a shipped text; hand-edited text stays.

### 2.66.7 to 2.66.8

- Upload the whole theme folder. The new Journal entry is created on the first page load as an administrator after the update, with its featured image and search title; nothing existing is changed.

### 2.66.6 to 2.66.7

- Upload the whole theme folder: eight articles need the new files in `assets/images/church/`. The sync refreshes them where they still match a shipped text; hand-edited text stays.

### 2.66.5 to 2.66.6

- Upload the whole theme folder: ten articles need the new files in `assets/images/church/`. The sync refreshes them where they still match a shipped text; hand-edited text stays.

### 2.66.4 to 2.66.5

- Upload the whole theme folder: ten articles need the new files in `assets/images/church/`. The sync refreshes them where they still match a shipped text; hand-edited text stays.

### 2.66.3 to 2.66.4

- Upload the whole theme folder: ten articles need the new files in `assets/images/church/`. The sync refreshes them where they still match a shipped text; hand-edited text stays.

### 2.66.1 to 2.66.2

- Upload the whole theme folder: ten more articles need the new files in `assets/images/church/`. The sync refreshes them where they still match a shipped text; hand-edited text stays.

### 2.66.0 to 2.66.1

- **Upload the whole theme folder.** The old `assets/social/*.jpg`, the four PNGs in `assets/images/` and `screenshot.png` are replaced by WebP files, and ten articles gain figures that need the new files in `assets/images/church/`. The sync refreshes the ten articles where they still match a shipped text; hand-edited text stays.
- A site that set its own image address to a `.jpg` link preview or `site-icon.png` of the theme should point to the `.webp` file.

### 2.65.6 to 2.66.0

- **New files** `inc/security.php`, `inc/security-detector.php`, `inc/contextual-links.php` and their `assets/css` and `assets/js` files. Upload the whole theme folder; a partial upload shows the maintenance page until they are there.
- **The Security tab is on by default.** XML-RPC and REST changes from outside a logged-in browser stop at once. If a lock breaks a tool you rely on, `define( 'PAULUS_SECURITY_LOCK', false );` in `wp-config.php` turns the XML-RPC and REST locks off without changing the options.
- After updating, change every administrator password, press "Log out all sessions" and "Revoke all application passwords" on the Security tab, and run the spam scan.

### 2.65.6 to 2.65.7

- **A short passage added to "The measure"**, with a source note to al Faruqi's foreword to *Toward Islamic Anthropology*. An unedited copy refreshes on the first page load as an administrator (or press Install or update under Appearance, Theme Options, Content); hand-edited text stays. Clear caches afterwards.

### 2.65.5 to 2.65.6

- **New featured images** on four pages. They change on the first page load as an administrator (or Install or update under Appearance, Theme Options, Content), on any page whose current featured image is one the theme installed; one you chose by hand is kept. Four new images go into the media library. Clear caches afterwards.

### 2.65.4 to 2.65.5

- **A short addition to each of three articles**: one phrase in two of them and one added sentence in The failed prophet (Tarsus and a persona for every audience; The failed prophet; No prophet between me and ʿĪsā ibn Maryam). Unedited copies refresh on the first page load as an administrator; a copy edited by hand keeps its text. Clear caches afterwards.

### 2.65.3 to 2.65.4

- **Text added to three pages.** Unedited copies of An invitation to the Christian reader, Original sin, Who is Christ? and About the author refresh on the first page load as an administrator (or press Install or update under Appearance, Theme Options, Content). A copy edited by hand keeps its text. Clear caches afterwards.
- **YouTube address.** The About the author page refreshes the same way. On the first admin page load, Theme Options' author YouTube field and the link in the author note change to https://www.youtube.com/@TheMuslimApologist if they still hold an address the theme shipped (`themuslimapologist.online` or `youtube.com/TheMuslimApologist/videos`). If you typed your own address there, it is kept; check Theme Options, Book, afterwards.

### 2.65.2 to 2.65.3

- **Renamed footer column.** The footer column and Sitemap group added in 2.65.0 and 2.65.2 now read "The Precedent", and "The case" reads "The Case" in the footer and the Sitemap. Nothing to do but clear caches.

### 2.65.1 to 2.65.2

- **New footer column.** "The message of ʿĪsā" appears in the footer as soon as the theme is uploaded, because the footer menu is built from the site structure; no menu needs editing and the header menu does not change. Clear caches.

### 2.65.0 to 2.65.1

- **Link fix in four pages.** Unedited copies of Was Paul married?, The apostle to the Gentiles, Paul's missionary journeys and The Islamic dilemma and the letters of Paul refresh on the first page load as an administrator. A copy edited by hand keeps its text; change `#s-two-pauls` to `#s-two-pauls-the-man-and-the-canon` in it yourself.

### 2.64.1 to 2.65.0

- **Four new pages** (`original-sin`, `jesus-in-his-own-words`, `the-prayer-jesus-taught`, `an-invitation-to-christians`), created by the structure sync on the first page load as an administrator, with their featured images. They appear in the Sitemap under "The message of ʿĪsā, without Paul". No existing text changes, and no menu changes: link them from a menu by hand if you want them there. Clear caches afterwards.

### 2.63.2 to 2.63.3

- **Font file swapped back to WOFF2.** `assets/fonts/kfgqpc-hafs-uthmanic-script.ttf` is replaced by `kfgqpc-hafs-uthmanic-script.woff2`. An upload over the old folder leaves the TTF behind; nothing reads it, and it can be deleted by FTP. Clear caches so visitors fetch the new `fonts.css`.

### 2.63.1 to 2.63.2

- **New file** `inc/quran-text.php`. Upload the whole theme folder; a partial upload without it shows the maintenance page until the file is there. No setting changes.

### 2.63.0 to 2.63.1

- **Font file swapped.** `assets/fonts/kfgqpc-hafs-uthmanic-script.woff2` is replaced by `kfgqpc-hafs-uthmanic-script.ttf` and its licence text. An upload over the old folder leaves the WOFF2 behind; nothing reads it, and it can be deleted by FTP. Clear caches so visitors fetch the new `fonts.css`.

### 2.62.41 to 2.62.42

- **Hidden files go.** On activation, and on the first admin page after the update, the theme deletes the hidden files and folders at the top of its folder (`.git`, `.github`, `.gitignore` and the like). If you run the theme from a git working copy on a live site, add `define( 'PAULUS_KEEP_DOTFILES', true );` to `wp-config.php` first; a site set to `WP_ENVIRONMENT_TYPE` `local` or `development` keeps them without it.

### 2.62.40 to 2.62.41

- **Uploading by FTP.** Switch on Maintenance mode first (Appearance, Theme Options, Maintenance), upload, then switch it off. Upload the contents of the release zip, not a copy of the repository: the zip leaves out the files the site does not need. While maintenance is on, visitors see "Back shortly" and you, signed in, see the site. If the admin is unreachable, delete the file `.paulus-maintenance` in the site's folder by FTP to end it.
- **Updating WordPress itself.** Prefer Dashboard, Updates, which takes seconds. If it must go by FTP, switch on Maintenance mode first: the rule the theme writes into `.htaccess` keeps the maintenance page up while WordPress cannot run.
- **New in wp-content:** `paulus-maintenance.html`, `paulus-error-page.css` and, after the first error, `paulus-incidents.log.php`. All are the theme's and go when it is switched off, except the incident log.

### 2.62.39 to 2.62.40

- **Security headers** are on by default (Theme Options, Login). If the site is shown inside a frame on another site, or your host already sends these headers, switch them off there. The HTTPS header tells browsers to use HTTPS for a year, so leave it on only if the site stays on HTTPS.
- **Images and fonts:** the WebP images and the single-file EB Garamond fonts are replaced. Upload the zip as a whole theme; over FTP, the old WebP files are removed by the weekly clean-up.

### 2.62.38 to 2.62.39

- **Nothing to do.** The texts are now named by their `.json` files everywhere. On the first page load after the update the theme renames its own records of them once, so your edited articles stay protected and the dashboard's list of kept edits still works. The texts themselves are unchanged.

### 2.62.37 to 2.62.38

- **Content files are JSON.** The texts in `content/articles/` are now `.json` files, and the `.html` files are gone. Upload the new zip as a whole theme (Appearance, Themes, Add New, replace), not over the old folder by FTP: an FTP copy leaves the old `.html` files behind. They are harmless, since nothing reads them, but you can delete them. Your articles are not touched: each text is byte for byte what it was, so an unedited article is still recognised as unedited.
- **Build files removed.** `content/src/`, `build.py` and `sblgnt/` are no longer in the theme. The site never read them, so nothing changes on it; an FTP copy over the old folder leaves them behind, and they can be deleted.
- **Five new figures, and three photographs on the book page,** reach the articles that hold them on the first admin page load.

### 2.62.35 to 2.62.36

- **Rank Math.** A title or description you typed into Rank Math's own box for a post, page or section now shows; before, the theme's replaced it. Clear Rank Math's field on any page where you want the theme's text back. Nothing else to do.

### 2.62.32 to 2.62.33

- **Author archive.** `/author/<name>/` now redirects permanently to the About the Author page. If you had linked it anywhere, link the author's page instead. Nothing else to do: the other changes are head tags and heading levels.

### 2.62.31 to 2.62.32

- **Links in the text.** The first mention of twenty-eight phrases (the Ebionites, the Islamic Dilemma, Matthew 5:17, the Talmud and the rest) in an article, an entry or a page now links to the page on bestofislam.org or injil.help that treats it. On by default; the switch, the limit and the phrases are on the new Links tab of Theme Options. The stored text is not changed.

### 2.62.29 to 2.62.31

- **Application passwords** are off from 2.62.30. A site that uses one (a mobile app, a publishing tool, a connector) must switch them back on under Appearance, Theme Options, Login, or that tool is refused at the REST API and XML-RPC until it does.
- **Error pages** are now written when an administrator opens an admin page, not on a visitor's page load. After updating, open the dashboard once so the pages carry the new version; on a network, only the main site writes them, so open its dashboard. A write that fails (wp-content not writable) is tried again on the next admin page load.
- **Search base.** If clean search addresses returned a page not found on your site, the first admin page load after the update rebuilds the rewrite rules.
- Nothing else to do: the other changes of 2.62.31 are fixes with no new options or files.

### 2.62.18 to 2.62.29

- **Error pages.** The first page load after the update copies `maintenance.php`, `db-error.php` and `php-error.php` into `wp-content/`. A file with one of those names that the theme did not write is left alone; one written by Paulus (or by the Abrahamic theme, whose files say they are safe to delete) is replaced. Switching to another theme removes them.
- **New pages and entries.** The sync adds `/questions/` and `/paul-at-a-glance/` (structure version 2.62.22) and the Journal entry "Did Paul ever meet Jesus?". Texts you edited by hand are kept: an unedited article or page refreshes where it still matches a shipped version, and `prior_hashes` now lists the earlier shipped texts of the files changed since 2.62.17, so a site that never recorded a hash still refreshes. The articles that hold Arabic carry new `quran` and `hadith` classes, and the Akyol section and the new figures come in the same way.
- **Sync lock.** The structure sync now claims its lock with an option (`paulus_sync_lock`) instead of a transient, so two simultaneous administrator requests cannot both run it; a lock over a minute old is taken over. Nothing to do unless a sync ever stops with a lock still set, in which case delete that option.
- **Images.** `assets/files.json` is written for the current version again, so the weekly clean-up of images a release no longer ships works; it lists every image in `assets/images`, `assets/images/church` and `assets/social`. Add any image you place there by hand to the list, or it is removed.
- **Fonts and styles.** New files: `dubidam-arabic-*.woff2` and `kfgqpc-hafs-uthmanic-script.woff2` in `assets/fonts/`, and the calligraphy `assets/images/alayhi-al-salam.avif`. Clear caches so visitors fetch the new `theme.css` (the stylesheet address carries the version).
- **Reading progress.** New options on Theme Options, Reading: which pages show the bar, its position, height and colours. The defaults keep the old behaviour.
- **Licences.** The `licenses/` folder is gone; `LICENSES.md` at the root replaces it.

### 2.39.0

The portrait and its variants are redrawn. On the first page load as an administrator after updating, the structure sync copies the eight new portrait files into the media library and points the existing attachments at them; featured images change in place. Clear the page cache and any CDN afterwards. A site icon set under Site Identity is not touched; the bundled fallback icon is redrawn.

### 2.49.0

The footer gains a secondary bar, and the theme drafts four pages for it: Privacy Policy, Terms of Use, DMCA and Contact. They arrive as drafts, listed on the dashboard under "To review". Open each, read the note at the top of the text (visible in the code editor, hidden from readers), confirm or correct what it lists, and publish; its link then appears in the bar. The Privacy Policy mentions the Google tag found on the site; if you use another analytics service, or plugins that load outside services, add them before publishing. If WordPress's own draft Privacy Policy was still untouched, the theme's text replaces it; a privacy page you had written is kept. If you have customised the footer in the Site Editor, reset that part or add a Shortcode block with `[paulus_footer_legal]`.

### 2.48.0

The Journal leaves the header menu and takes its own block in the header, beside THE BOOK. The header part (`parts/header.html`) carries it; if you have customised the header in the Site Editor, reset that part to the theme's version, or add a Shortcode block with `[paulus_journal_canton]` between the Navigation block and the search. The menu is rebuilt once to drop the Journal link that 2.46 added, and only if the menu still holds the theme's own links.

### 2.47.0

Three pages move from Reference to the new Appendices section: *Paul in the churches*, *“Luke” versus Paul: the notes of Dale B. Martin* and *Why Luke does not seem to know Paul's letters*. On the next admin page load the sync creates the Appendices page and moves the three pages under it in place, keeping their content and any edits. Their old addresses under `/reference/` redirect permanently to the new ones under `/appendices/`. Clear the page cache afterwards. If an SEO plugin keeps its own redirects, nothing needs adding there.

### 2.46.0

The Journal arrives. Its addresses work at once: the theme refreshes the address rules itself after the update, with no visit to Settings, Permalinks. The header menu gains a Journal link on the next admin page load, unless the menu has been edited in the Site Editor, in which case it is kept and the dashboard widget says so. In Rank Math, under Sitemap Settings, switch on the Journal post type so its entries are listed.

### 2.33.5 and 2.33.6

Open any admin page once as an administrator after updating. A one-time repair restores the footer description, the footer badges, the front-page search title and the hero kicker where they are empty. Check the Front page tab of Theme Options afterwards: other fields a site had never saved may also have been emptied between 2.30.0 and 2.33.4, and those are left for you to refill, since an empty field may be deliberate.

### 2.33.0

No action beyond the routine. The front-page hero and the article title panel are built in PHP, so the new author lines appear even where the templates were customised in the Site Editor.

### 2.32.2

Removes two figures that repeated works already on the site and adds a Roman military diploma from Judaea to *The Roman*. Two image files are deleted from the theme; nothing on the site refers to them after the sync.

### 2.32.0 and 2.32.1

The 404 excuse is chosen when the page is built. A page cache that stores 404 responses serves one excuse until it clears, and the "Hear another excuse" link then returns the same one. Exclude 404 responses from the cache if you want the rotation.

2.32.1 turns 26 in-text references into links. Articles whose body you have edited keep their old, unlinked text.

### 2.30.0

Titles and descriptions follow the site format: "<page title> | Apostle of Doom" in 50 characters or fewer, and descriptions of 120 to 130 characters.

- With Rank Math or Yoast active, the theme now supplies the title and description to the plugin, including the Open Graph and X tags. Titles and descriptions you typed into the plugin's own fields are no longer used. Move any you want to keep into the theme's "Search title and description" box, which respects the limits.
- After the sync, check a few pages with the plugin's preview or in the page source.

### 2.29.0 and 2.31.0

Most articles are rebuilt from source: footnotes renumbered in reading order (2.29.0), and passages relying on disputed letters reattributed (2.31.0). An article whose body was edited by hand keeps its old numbering and wording; rebuild it from `content/src` and paste it in, or leave it.

### 2.26.5 to 2.28.2

No action beyond the routine. The Single template change in 2.26.5 is listed above.

## Roadmap

Everything below is a plan, open to change. Each item says why it is here. An item leaves this list when it ships, and goes into `CHANGELOG.md`.

### Next

- Mobile follow-ups from the 2.77.24 audit: raise the 12 to 13.6 px kickers, labels and colophon to 14 px, and reserve space on the chronology page (layout shift 0.075 at 375 px). Both change the design, so they wait for the owner.
- Finish the move to the ESV. Articles written before 1 November 2026 still quote the King James Version beneath the Greek, and the Sources page says so. Each moves across whole, picture captions included, so no article mixes two translations. The largest are left for last: *ʿĪsā in his own words*, *The prayer ʿĪsā taught*, *Original sin*, *The Greek body and the Jewish bed* and *Justification by faith*.
- Key takeaways for the shipped articles. The box shows only an article's own points, and none of the 56 articles in `content/manifest.json` has any yet; a `takeaways` field in the manifest, synced like the excerpt, would fill them (with `prior_hashes` and a new `content_version`).
- Keep the `.htaccess` blocks on a network. `paulus_sec_remove_htaccess()` takes them out on any theme switch, while the error pages already stay for a site that still runs Paulus.
- Run the tests on every push. The repository has nine test files and no CI workflow.

### Later

- Licences for Sabon Next LT and the Dubidam fonts, which `readme.txt` lists as not free for a public site. This waits on the owner.
- Test on WordPress 7.1 and raise "Tested up to", which reads 7.0.
- Find what still sends reports to `/wp-json/wp-perf-shield/v1/csp-report`. The logs from 26 October to 4 November show 746 such requests answered 404, from a plugin that is no longer on the site.

### Ideas

- A pinned lead among the featured entries. The newest featured entry leads; a pinned one would need a field and a rule for when it stops leading.

- Refuse `wp-admin/includes/*.php` and `wp-config.php` by address in the core-file block. `wp-config.php` holds the database password, and the error logs show bots asking for core files by address.
- Send `/wp-content/uploads/2026/09/paul-dunce-face.jpg` and the like to the AVIF illustration. The same logs show 26 requests for that one file, answered 404.
- Confirm the terms of Arslan Wessam with its author, as `LICENSES.md` asks.
- `lastReviewed` for articles. "Last reviewed" shows in the byline, but an article is described by its Article node, which has no such property, and the page has no WebPage node to carry it.

