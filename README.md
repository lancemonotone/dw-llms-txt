# D|W llms.txt

WordPress plugin that serves a dynamic [`llms.txt`](https://llmstxt.org/) map of live site content for AI agents.

Built for [Destination Williamstown](https://destinationwilliamstown.org/). Author: Rus Miller.

## What it does

- Registers a public `/llms.txt` endpoint (rewrite + `REQUEST_URI` fallback)
- Builds plain-text sections from WordPress content (pages, venues, events, trip ideas, etc.)
- Caches the rendered body in a transient (invalidated from admin when needed)
- Small admin UI to clear cache / inspect status

## Install

1. Copy this folder to `wp-content/plugins/dw-llms-txt/`
2. Activate **D|W llms.txt**
3. Visit `https://yoursite.example/llms.txt` (flush permalinks once if needed)

## Structure

```text
dw-llms-txt.php          Bootstrap
includes/
  class-plugin.php       Wiring
  class-endpoint.php     /llms.txt response
  class-document.php     Content → markdown body
  class-cache.php        Transient cache
  class-admin.php        Admin helpers
```

## License

GPL-2.0-or-later (same as WordPress), unless noted otherwise in file headers.
