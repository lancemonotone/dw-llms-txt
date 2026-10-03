# llms.txt

WordPress plugin that serves a dynamic [`llms.txt`](https://llmstxt.org/) map of live site content for AI agents.

Author: Rus Miller.

## What it does

- Registers a public `/llms.txt` rewrite endpoint
- Builds plain-text sections from WordPress content (chosen menu navigation, selected CPT archives, selected taxonomies)
- Until settings are saved: site title/tagline only (no invented menu, types, taxonomies, or sitemap)
- Caches the rendered body in a transient (invalidated on content/menu/settings changes)
- Settings → **llms.txt**: choose menu location, content types, taxonomies, sitemap link, plus live preview
- Extensible via `Plugin::hook( 'document_sections' )` (resolves to `llms_txt_document_sections`)

## Install

1. Copy this folder to `wp-content/plugins/llms-txt/` (folder name can vary)
2. Activate **llms.txt** (activation flushes permalinks)
3. Visit Settings → **llms.txt**, save what should appear, then open `https://yoursite.example/llms.txt`

## Structure

```text
llms-txt.php           Bootstrap + Plugin identity (document name, slugs, hooks)
classes/
  class.endpoint.php   Public document response
  class.document.php   Content → markdown body
  class.config.php     Stored option reads
  class.catalog.php    Post-type / taxonomy / menu / archive discovery
  class.options.php    Options page + Settings API register/sanitize
  class.cache.php      Transient cache + invalidation
  class.admin.php      Options page UI + preview + notices
```

Hook classes bootstrap themselves (`new ClassName()` at the bottom of the file). `Config`, `Catalog`, and `Document` are helpers only. Change the public filename or prefixes on `Plugin` in `llms-txt.php` only.

## License

GPL-2.0-or-later (same as WordPress), unless noted otherwise in file headers.
