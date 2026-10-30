# Upgrading Paulus

How to move a live site to a new version, and what each update does and does not change. Read the section for every version between the one installed and the one you are installing.

## The routine for every update

1. Back up the database. The structure sync writes to posts, post meta, term meta and options.
2. Upload the new zip under Appearance, Themes, Add New, and choose "Replace current with uploaded". Zips are named `paulus-<version>.zip`, with dots in the version.
3. Open any page as an administrator. The structure sync runs once, because the manifest's `content_version` differs from the one recorded on the site. Appearance, Theme Options, Content shows both versions afterwards; they should match.
4. Clear every page cache (plugin, server and CDN).

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

## Notes by version

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
