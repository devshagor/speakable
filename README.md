# Speakable

A WordPress plugin that adds a browser-based text-to-speech player to your posts and pages using the **Web Speech API** — completely free, no API keys, no external services.

---

## Features

- **Play / Pause / Stop** controls with animated wave indicator
- **Adjustable playback speed** (0.75x – 2x) via in-player dropdown
- **Progress bar** with sentence counter
- **Sticky mini-player** — stays visible while scrolling during playback
- **Voice, pitch, volume, and speed** configurable from the admin dashboard
- **Enable/disable per post type** — Posts, Pages, custom types, etc.
- **Customisable button colour** with live preview in admin
- **Player position** — before or after post content
- **Gutenberg block** — place the player at any exact position in content
- **Live preview** — test voice settings in the admin before saving
- **Analytics page** — overview of active features and post counts
- **Zero external dependencies** — no third-party scripts, fonts, or services
- **Accessible** — ARIA labels, roles, keyboard navigation, and live regions
- **Mobile-optimised** — 44 px minimum touch targets, Chrome Android keep-alive fix

---

## Admin Menu

The plugin adds a top-level **Speakable** menu to the WordPress sidebar with three pages:

```
Speakable
├── Settings
│   ├── Voice tab     — voice, speed, pitch, volume
│   ├── Display tab   — post types, button colour, position, player features
│   └── Preview tab   — live voice test + player mockup
├── Analytics         — feature status, post counts, config summary
└── Help              — FAQ and quick links
```

---

## Installation

1. Upload the `speakable` folder to `/wp-content/plugins/`
2. Activate the plugin via **Plugins** in WordPress admin
3. Go to **Speakable → Settings** to configure voice and display options
4. Visit any single post to see the player in action

---

## Settings Reference

### Voice Tab (`Speakable → Settings → Voice`)

| Setting | Default | Range |
|---------|---------|-------|
| Voice | Browser Default | Varies by device / OS |
| Speed | 1.0x | 0.5 – 2.0 |
| Pitch | 1.0 | 0.0 – 2.0 |
| Volume | 1.0 | 0.0 – 1.0 |

> Voices are provided by the visitor's operating system. The saved voice name is a preference — if it is unavailable on a visitor's device, their browser default is used.

### Display Tab (`Speakable → Settings → Display`)

| Setting | Default | Options |
|---------|---------|---------|
| Enable on Post Types | Post | Any registered public post type |
| Button Colour | `#d60017` | Any hex colour |
| Button Position | Before Content | Before / After |
| Progress Bar | On | On / Off |
| Speed Control | On | On / Off |
| Sticky Player | On | On / Off |

### Preview Tab (`Speakable → Settings → Preview`)

Type or paste any text and click **Play Preview** to hear the current voice settings spoken aloud. The player mockup below updates live when you change the button colour.

---

## Gutenberg Block

Search for **"Speakable Player"** in the block inserter to place the player at any exact position within your content. When the block is present in a post, global auto-insertion via the `the_content` filter is automatically skipped for that post to prevent duplicate players.

---

## How It Works

1. The `the_content` filter injects the player HTML on singular post views (or use the Gutenberg block for manual placement)
2. JavaScript extracts the article text from the DOM and splits it into sentences
3. Each sentence is spoken via `SpeechSynthesisUtterance`, chained through the `onend` event
4. Sentence-by-sentence playback avoids Chrome's known 15-second utterance timeout

---

## Browser Support

| Feature | Chrome 33+ | Safari 7+ | Firefox 49+ | Edge 14+ |
|---------|:----------:|:---------:|:-----------:|:--------:|
| Speech Synthesis | ✓ | ✓ | ✓ | ✓ |
| Pause / Resume | ✓ | iOS: limited | ✓ | ✓ |

---

## File Structure

```
speakable/
├── speakable.php                          # Plugin bootstrap, constants, activation hook
├── uninstall.php                         # Removes plugin data on deletion
├── readme.txt                            # WordPress.org listing
├── package.json                          # npm config for building blocks
├── .gitignore
├── assets/
│   ├── css/
│   │   ├── speakable-admin.css            # Admin dashboard styles
│   │   └── speakable-frontend.css         # Frontend player styles
│   └── js/
│       ├── speakable-admin.js             # Admin: tabs, sliders, voice picker, preview
│       └── speakable-frontend.js          # Frontend: Web Speech API player
├── includes/
│   ├── class-speakable-admin.php          # Speakable menu + Settings / Analytics / Help pages
│   ├── class-speakable-frontend.php       # the_content filter, asset enqueue
│   └── class-speakable-blocks.php         # Gutenberg block registration
├── src/
│   └── blocks/
│       └── speakable-player/
│           ├── block.json                # Block metadata
│           ├── index.js                  # Block editor script
│           ├── editor.css                # Block editor styles
│           └── render.php                # Server-side render
└── languages/
    └── speakable.pot                      # Translation template (103 strings)
```

---

## Development

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
```

---

## Requirements

- WordPress 6.0+
- PHP 7.4+
- A modern browser for the web player (Chrome, Safari, Firefox, Edge)

---

## License

GPL v2 or later — see [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html)
