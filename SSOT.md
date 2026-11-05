# Paulus: single source of truth

Version 2.77.9. This file holds the fixed facts of the theme and the rules every change follows.

It settles any disagreement between documents on a matter of fact. Where a document and the code or a data file of record disagree, the code or data file is right and the document is fixed, as `README.md`, "Where each fact lives", says. `.claude/FARUQI.md` holds the principle these facts serve; if the two ever pull apart, stop and ask the owner.

The theme `.htaccess` refuses this file to the web server, as it refuses every Markdown file but `README.md`, `UPGRADING.md` and `LICENSES.md`.

## Identity

| Item | Value |
|---|---|
| Site name | Apostle of Doom |
| Domain | https://apostleofdoom.org |
| Theme name | Paulus (`style.css`) |
| Theme folder | `paulus`, all lower case, on the live server and in the zip |
| Text domain | `paulus` |
| Parent theme | Twenty Twenty-Five (`Template: twentytwentyfive`), version 1.5 or later |
| Requirements | WordPress 6.7 or later (tested up to 7.0), PHP 8.0 or later |
| Theme author | MENJ (`Author` and `Theme URI` in `style.css`) |
| Author for schema | Mohd Elfie Nieshaem Juferi (`PAULUS_SCHEMA_AUTHOR_NAME`, `inc/seo.php`) |
| Source book | *Paulus Perosak Risalah Al-Masih*, Mohd Elfie Nieshaem Juferi (Langgam Fikir, 2025; ISBN 978-629-96135-0-3). Its name is never shortened or altered |
| Licence | GPL v2 or later for the code. The articles and illustrations belong to the book's author and publisher |

## Purpose and position

The site presents the Islamic case against Paul of Tarsus as a case file: the man, six charges, the witnesses, answers to missionary claims and the verdict, with a book page. It draws the line between ʿĪsā ibn Maryam and the theology built on him, as `.claude/FARUQI.md` sets out.

The editorial foundation is the thought of Ismāʿīl Rājī al-Fārūqī, and it stays implicit. The site states its principles as its own, grounded in the Qurʾān and the Sunnah, and cites al-Fārūqī only as one scholar among others. `README.md`, "Editorial foundation", has the detail.

## Owner decisions

Copied word for word from `.claude/CLAUDE.md`.

- The horned illustrations of Paul (`paul-horned-*`, with the decayed saint, the dunce and the BASTARD cap) stay. The owner decided on 2026-10-29 to keep them after weighing them against `FARUQI.md`. Do not remove, replace or soften them, and do not raise the question again unless the owner does.
- Every page title reads `<page title, keyword or description> | <site name>` and stays under 60 characters. The theme builds it in `paulus_seo_title()` (`inc/seo.php`) and holds it to `PAULUS_TITLE_MAX`, 50, the stricter limit in force; with " | Apostle of Doom" that leaves 32 characters for a page's `seo_title` in `content/manifest.json`. Any new page, article or section gets a `seo_title` within that, and a changed one is checked before it is committed. The owner set this rule on 2026-10-31.

Other decisions recorded in `README.md` and `LICENSES.md`:

- 2026-10-26: serve a WOFF2 conversion of the KFGQPC HAFS Uthmanic Script, accepting that its licence forbids modifying the font.
- 2026-11-03: keep every schema.org type that describes what a page shows, Google rich result or not.
- The header menu carries none of the four pages under The Precedent.

## Titles and descriptions

| Rule | Value | Where it is held |
|---|---|---|
| Title format | `<seo_title> \| Apostle of Doom` | `paulus_seo_title()`, `inc/seo.php` |
| Whole title | at most 50 characters | `PAULUS_TITLE_MAX` |
| A page's `seo_title` | at most 32 characters | `content/manifest.json`, every part, post, Journal entry and page |
| Front-page title | at most 32 characters, before "\| Apostle of Doom" | Theme Options, Front page (`site_seo_title`) |
| Meta description | at most 130 characters, its closing call to action included | `PAULUS_META_MAX` |
| The book's name | never shortened, the one exception to the title length | |

Check every `seo_title` before a commit. The command exits with 1 when one is missing or too long:

```bash
python3 -c "import json, sys; m=json.load(open('content/manifest.json')); bad=[(x.get('slug'), len(x.get('seo_title') or '')) for k in ('parts','posts','journal','pages') for x in m[k] if not x.get('seo_title') or len(x['seo_title']) > 32]; print(bad or 'all within 32'); sys.exit(1 if bad else 0)"
```

On 2.77.9 the longest is 32 characters ("Was Paul an apostle or disciple?"), and none is missing.

## The content manifest

`content/manifest.json` is the record of what the theme ships.

| Key | Holds | Rule |
|---|---|---|
| `content_version` | The version of the shipped content (2.77.3 on 2.77.9) | The automatic sync runs only when this differs from the site's record; Install or update under Theme Options, Content runs it at any time. Raise it with any change the sync carries: shipped text, titles, search titles, descriptions, images, the structure or the menu. Leave it alone when none of these changes |
| `nav_version` | The version of the header menu | Raise it, with `content_version`, when the menu's shipped links change |
| `parts` | The three sections, in order | Each has `slug`, `name`, `description`, `seo_title`, `meta` and an image |
| `posts` | The 56 articles of the reading path (16 in The Man, 28 in The Charges, 12 in The Witnesses) | Array order is the reading order. `part` names the section, and `label`, `series`, `series_title`, `part_no` and `parts` set the counts. `was` names a text's earlier file, so its recorded hashes carry over; `was_slug` renames a post in place |
| `journal` | Shipped Journal entries | Created once each, recorded in `paulus_journal_shipped`, never made again once deleted |
| `pages` | The pages | `order` and, for a moved page, `was_parent` |
| `prior_hashes` | MD5 of every earlier shipped text, keyed `<file>#r<release>` | Add the old hash whenever a shipped text changes, so an untouched page still takes the new one |
| `prior_meta` | Every earlier shipped description and title | Add the old value whenever one changes, and raise `content_version`, so installed sites take the new one; a value typed by hand is kept |

Each text lives in `content/articles/<name>.json` as `{"name": …, "format": "wordpress-post-content", "md5": …, "content": …}`. `paulus_content_file()` is the only reader: it opens only that folder, and refuses a file that does not name itself or whose `md5` does not match its `content`. After an edit, set `md5` to the MD5 of the new text.

The archive of Dale B. Martin's notes (`dale-b-martin-luke-versus-paul.json`) is verbatim and is never edited, restyled or renumbered.

## Folders and files

| Path | Fact |
|---|---|
| `content/articles/` | The only content files the installer reads, JSON only |
| `content/images.json` | Figure licences, image titles and alt text |
| `assets/images/` | Illustrations as AVIF; `sizes.json` gives their dimensions |
| `assets/images/church/` | The figures, with `commons-meta.json` |
| `assets/social/` | Link-preview copies |
| `assets/files.json` | The images the release ships in `assets/images/`, `assets/images/church/` and `assets/social/`, the folders the image clean-up manages, with the version they belong to. Its `version` must equal `style.css`, or the clean-up removes nothing |
| `assets/docs/` | The PDF of Martin's notes, served on purpose |
| `inc/data/network-links.json` | The phrase map for contextual links. It is kept byte for byte the same in every theme that carries it: change it in all of them or in none |
| `dropins/` | Sources of the three wp-content error pages |
| `errors/` | Static 403, 500, 502, 503 and 504 pages for a site at the domain root |
| `docs/` | The keyword research. Not shipped |
| `tests/`, `tools/`, `bin/` | Checks and scripts. `tests/` and `tools/` are not shipped; every PHP script in them exits outside the command line |
| `.claude/` | Contributor rules and skills. Not shipped, refused by the web server, removed from a live site |
| `CHANGELOG.md` | Not shipped: `.gitattributes` leaves it out of the zip. This is the theme's one exception to the shared documentation standard in `.claude/skills/theme-docs/SKILL.md`, which says the docs ship |
| `README.md`, `UPGRADING.md`, `LICENSES.md` | Shipped and served on purpose: Theme Options links the first two |

On a live site the first request after a version change removes the dot entries, `tests`, `tools` and `docs` from the theme folder, unless the site is `local` or `development` or defines `PAULUS_KEEP_DOTFILES`.

## Names that must not change

| Kind | Names |
|---|---|
| Option | `paulus_options` (every Theme Options field), `paulus_journal_shipped` |
| Post meta | `_paulus_order`, `_paulus_label`, `_paulus_series`, `_paulus_series_title`, `_paulus_part_no`, `_paulus_parts` |
| Term meta | `paulus_order` |
| Constants | `PAULUS_HIDE_LOGIN`, `PAULUS_SECURITY_LOCK`, `PAULUS_SPAM_THRESHOLD`, `PAULUS_KEEP_DOTFILES`, `PAULUS_ALLOW_CORE_FILES`, `PAULUS_SCHEMA_AUTHOR_NAME`, `PAULUS_SCHEMA_AUTHOR_URL` |
| `.htaccess` markers | `Block xmlrpc.php`, `Paulus: deny direct core files`, `Paulus maintenance` |
| Files in wp-content | `paulus-errors/`, `paulus-error-page.css`, `paulus-maintenance.html`, `paulus-incidents.log.php` |
| Maintenance flag | `.paulus-maintenance` in the site's folder |

## Contributor rules

### Writing

All English prose follows the `anti-ai-writing-style` skill: no contractions, no em dashes, none of the banned words or negative parallelisms, and short paragraphs. Quotations and reproduced texts keep their authors' wording.

ʿĪsā ibn Maryam is named so in the site's own voice, with the written honorific the theme turns into calligraphy. The Koine Greek comes only from `paulus_greek_marks()`.

### Public wording

`README.md`, `UPGRADING.md`, `LICENSES.md` and `readme.txt` are served, and the pages are public. None of them says that the site is related to other sites or belongs to a group of them. Links to other sites are ordinary links.

### Images

The illustrations, figures, the hero and the portraits are AVIF. WebP is kept for link previews (`assets/social/`), the publisher logo and the site icon, the pixel icons (`arrow-next`, `envelope`) and the fallback copies of the two calligraphy masks. No PNG or JPEG ships, apart from the three control images of Lightbox2 in `assets/vendor/lightbox2/images/`.

Each figure shows its credit and its licence, recorded in `content/images.json`. On 2.77.9 these are the Pexels License (141 figures), the public domain or CC0 (55), Creative Commons attribution licences, BY or BY-SA (34), the Pixabay Content License (1), and "All rights reserved" for the 4 images the author made himself (the reader photographs, the book in hand and the portrait of Dale B. Martin). `LICENSES.md` sets out the terms. A new figure from outside these needs the owner's say.

### Release checklist

1. The version is the same in `style.css`, `readme.txt` (Stable tag and changelog), the zip name and the At a glance table in `README.md`, `assets/files.json`, the top entry of `CHANGELOG.md` and the version line at the top of this file. `PAULUS_VERSION` reads `style.css`.
2. `content_version` moves only when shipped content changes, and `prior_hashes` and `prior_meta` record what it replaced.
3. Every `seo_title` is 32 characters or fewer (the check above).
4. `CHANGELOG.md` has the new entry, `readme.txt` the short form, and `UPGRADING.md` a note when the owner must act; its roadmap drops what shipped.
5. `README.md` covers any new hook, filter, constant, setting, file or rule.
6. Every path named in a document exists.
7. Every test in `tests/` passes, and `php bin/build-error-pages.php --check` passes.

## Credits that must stay

- `LICENSES.md`, whole: the four built-in plugin authors, Lightbox2 and html2pdf.js with their libraries, the typefaces and the KFGQPC licence decision.
- The licence files beside each bundled library and font.
- The credit line beneath every figure and in its structured data.
- The book's bibliographic data on the book page.
