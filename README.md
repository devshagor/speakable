# Speakable

**Version:** 1.0.1 · **License:** GPL-2.0-or-later · **WordPress:** 6.0+ · **PHP:** 7.4+ · **Author:** ThemeShape

A WordPress plugin that adds a browser-based text-to-speech player to your posts and pages using the **Web Speech API**. No API keys, no external services, no usage costs — voices come from the visitor's own device.

---

## Features

- **Listen / Pause / Resume / Stop** controls with an animated wave indicator
- **Reads the post title first**, then the article body — scoped to the player's own container so related-post titles and meta are never read
- **Adjustable playback speed** (0.75x – 2x) via an in-player dropdown
- **Progress bar** with a sentence counter (`current / total`)
- **Sticky mini-player** that appears at the bottom of the screen while the main player is scrolled out of view
- **Voice, pitch, volume, and default speed** configurable from the admin dashboard
- **Enable / disable per post type** — Posts, Pages, custom post types, anything `public`
- **Customisable button colour** with a live preview in the admin
- **Player position** — before or after the post content
- **Gutenberg block** — place the player at any exact position inside a post; suppresses global auto-insertion to avoid duplicates
- **Live voice preview** in the admin so settings can be tested before saving
- **Analytics page** — overview of TTS-enabled post counts, active features, and current configuration
- **Help page** — FAQ and quick links
- **Mobile-optimised** — 44 px touch targets and a Chrome Android keep-alive that works around the 15-second `speechSynthesis` idle timeout
- **Accessible** — ARIA labels and roles, keyboard navigation, live regions, and a graceful fallback message in browsers that do not support speech synthesis
- **Zero external dependencies** — no third-party scripts, fonts, or services

---

## Admin Menu

The plugin adds a top-level **Speakable** menu to the WordPress sidebar:

```
Speakable
├── Settings
│   ├── Voice tab     — voice, speed, pitch, volume
│   ├── Display tab   — post types, button colour, position, player feature toggles
│   └── Preview tab   — live voice test and a player mockup
├── Analytics         — feature status, post counts, configuration summary
└── Help              — FAQ and quick links
```

---

## Installation

1. Upload the `speakable` folder to `/wp-content/plugins/`, or install the zip from **Plugins → Add New → Upload Plugin**
2. Activate **Speakable** from the **Plugins** screen
3. Open **Speakable → Settings** to configure voice, display, and post types
4. Visit any single post of an enabled post type to see the player

---

## Settings Reference

### Voice (`Speakable → Settings → Voice`)

| Setting | Default | Range |
|---------|---------|-------|
| Voice | Browser Default | Varies by device / OS |
| Speed | 1.0x | 0.5 – 2.0 |
| Pitch | 1.0 | 0.0 – 2.0 |
| Volume | 1.0 | 0.0 – 1.0 |

> Voices are provided by the visitor's operating system. The saved voice name is a preference — if the chosen voice is unavailable on a visitor's device, their browser default is used instead.

### Display (`Speakable → Settings → Display`)

| Setting | Default | Options |
|---------|---------|---------|
| Enable on post types | Post | Any registered public post type |
| Button colour | `#d60017` | Any hex colour |
| Button position | Before content | Before / After |
| Progress bar | On | On / Off |
| Speed control | On | On / Off |
| Sticky player | On | On / Off |

### Preview (`Speakable → Settings → Preview`)

Type or paste any text and click **Play Preview** to hear the current voice settings spoken aloud. A player mockup below the preview updates live as you change the button colour.

---

## Gutenberg Block

Search for **"Speakable Player"** in the block inserter to drop the player at any exact position inside a post. When the block is present in a post, global auto-insertion via the `the_content` filter is automatically skipped for that post so the player never appears twice.

---

## How It Works

1. On singular views of an enabled post type, the `the_content` filter injects the player HTML (or, if the Gutenberg block is present, the block renders the player at its chosen position and auto-insertion is suppressed for that post).
2. The frontend JavaScript locates the player, walks up to its nearest article container (`.entry-content`, `.post-content`, `.single-post-body`, or the surrounding `<article>`), and extracts the text from that scope only.
3. The post title is prepended to the extracted text, and the result is split into sentences.
4. Each sentence is spoken via `SpeechSynthesisUtterance`, chained through the `onend` event. Speaking sentence-by-sentence avoids Chrome's known 15-second utterance timeout.

---

## Browser Support

| Feature | Chrome 33+ | Safari 7+ | Firefox 49+ | Edge 14+ |
|---------|:----------:|:---------:|:-----------:|:--------:|
| Speech synthesis | ✓ | ✓ | ✓ | ✓ |
| Pause / Resume | ✓ | iOS: limited | ✓ | ✓ |

In browsers without `speechSynthesis` support, the player replaces itself with a friendly fallback message instead of rendering broken controls.

---

## File Structure

```
speakable/
├── speakable.php                              # Plugin bootstrap, constants, activation hook
├── uninstall.php                              # Removes plugin options on deletion
├── readme.txt                                 # WordPress.org listing
├── package.json                               # npm scripts for build / zip
├── .gitignore
├── assets/
│   ├── css/
│   │   ├── speakable-admin.css                # Admin dashboard styles
│   │   └── speakable-frontend.css             # Frontend player styles
│   └── js/
│       ├── speakable-admin.js                 # Admin: tabs, sliders, voice picker, preview
│       └── speakable-frontend.js              # Frontend: Web Speech API player + sticky mini-player
├── includes/
│   ├── class-speakable-admin.php              # Speakable menu + Settings / Analytics / Help pages
│   ├── class-speakable-frontend.php           # the_content filter, asset enqueue
│   └── class-speakable-blocks.php             # Gutenberg block registration
├── src/                                       # Human-readable block source (ships with the plugin)
│   └── blocks/
│       └── speakable-player/
│           ├── block.json                     # Block metadata
│           ├── index.js                       # Block editor script
│           ├── editor.css                     # Block editor styles
│           └── render.php                     # Server-side render template
├── build/                                     # wp-scripts output; regenerated from src/
│   └── blocks/
│       └── speakable-player/
└── languages/
    └── speakable.pot                          # Translation template
```

---

## Development

The maintained development location is this repository: <https://github.com/devshagor/speakable>

The contents of `build/blocks/speakable-player/` are produced from `src/blocks/speakable-player/` using `@wordpress/scripts`. All PHP under `includes/` and all CSS/JS under `assets/` ship unminified.

```bash
# Install dependencies
npm install

# Build block assets (outputs to build/blocks/)
npm run build

# Watch for changes during development
npm run start

# Lint JavaScript
npm run lint:js

# Lint CSS
npm run lint:css

# Package a release zip into production/speakable.zip
npm run zip
```

---

## Changelog

### 1.0.1

- **Fix:** the player no longer reads related-post titles or meta. Content extraction is now scoped to the player's own article container.
- **Improvement:** the post title is read before the article body.
- **Docs:** added a Source Code and Development section to `readme.txt` pointing to this repository, and bundled the human-readable `src/` alongside the build output in the release zip.

### 1.0.0

- Initial release: browser-based TTS using the Web Speech API, admin dashboard with Voice / Display / Preview tabs, voice / speed / pitch / volume controls, per-post-type toggle, customisable button colour and position, progress bar, in-player speed control, sticky mini-player, Gutenberg block for manual placement, Analytics and Help pages, accessible markup, mobile-optimised touch targets.

---

## Requirements

- WordPress 6.0+
- PHP 7.4+
- A modern browser with `speechSynthesis` support (Chrome, Safari, Firefox, Edge)

---

## License

GPL v2 or later — see <https://www.gnu.org/licenses/gpl-2.0.html>.
