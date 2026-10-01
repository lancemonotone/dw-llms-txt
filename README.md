# llms.txt

WordPress plugin that serves a dynamic [`llms.txt`](https://llmstxt.org/) map of live site content for AI agents.

Author: Rus Miller.

## What it does

- Registers a public `/llms.txt` endpoint (rewrite + `REQUEST_URI` fallback)
- Builds plain-text sections from WordPress content (primary menu navigation, selected CPT archives)
- Caches the rendered body in a transient (invalidated on content/menu/settings changes)
- Settings → **llms.txt**: choose menu location, content types, sitemap link, plus live preview
- Extensible via `llms_txt_document_sections` and `llms_txt_menu_locations` filters

## Install

1. Copy this folder to `wp-content/plugins/llms-txt/` (folder name can vary)
2. Activate **llms.txt**
3. Visit Settings → **llms.txt** to choose what appears, then open `https://yoursite.example/llms.txt` (flush permalinks once if needed)

## Structure

```text
llms-txt.php           Bootstrap (glob classes/)
classes/
  class.endpoint.php   /llms.txt response
  class.document.php   Content → markdown body
  class.config.php     Stored option reads
  class.catalog.php    Post-type / menu / archive discovery
  class.options.php    Options page + Settings API register/sanitize
  class.cache.php      Transient cache + invalidation
  class.admin.php      Options page UI + preview + notices
```

Classes that register hooks self-wire with `new ClassName();` at file bottom. Constructors only add hooks; collaborators are created inside hooked methods (or via `llms_txt_options_page`). `Config`, `Catalog`, and `Document` are plain helpers with no self-wire.

## License

GPL-2.0-or-later (same as WordPress), unless noted otherwise in file headers.
