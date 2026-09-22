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

To find which of your pages kept hand-edited text, compare them with the files in `content/articles/`.

## Templates customised in the Site Editor

WordPress stores a template edited in the Site Editor in the database, and from then on ignores the theme's template file. An update to that template in the theme never reaches the site. If you have customised any of these, reset them (Appearance, Editor, Templates, the template's menu, "Reset") or apply the change by hand:

| Template | Changed in | Change |
| --- | --- | --- |
| Single | 2.26.5 | `[paulus_series_nav]` removed from below the text |
| 404 | 2.32.0 | Heading and first paragraph replaced by `[paulus_404_quip]` and a plain paragraph |

## Notes by version

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
