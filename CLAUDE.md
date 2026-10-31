# Working on this repository

Read `FARUQI.md` first. It sets out the Faruqian core, al-tawḥīd as Ismāʿīl Rājī al-Fārūqī expounded it in *Al Tawhid: Its Implications for Thought and Life*, which runs through the themes and content of this site and its eight sister sites. Do not deviate from it.

## Owner decisions

- The horned illustrations of Paul (`paul-horned-*`, with the decayed saint, the dunce and the BASTARD cap) stay. The owner decided on 2026-10-29 to keep them after weighing them against `FARUQI.md`. Do not remove, replace or soften them, and do not raise the question again unless the owner does.
- Every page title reads `<page title, keyword or description> | <site name>` and stays under 60 characters. The theme builds it in `paulus_seo_title()` (`inc/seo.php`) and holds it to `PAULUS_TITLE_MAX`, 50, the stricter limit in force; with " | Apostle of Doom" that leaves 32 characters for a page's `seo_title` in `content/manifest.json`. Any new page, article or section gets a `seo_title` within that, and a changed one is checked before it is committed. The owner set this rule on 2026-10-31.
