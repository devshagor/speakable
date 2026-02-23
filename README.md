# WP Speech

A WordPress plugin that adds a browser-based text-to-speech player to your posts and pages using the **Web Speech API** — completely free, no API keys, no external services. Also exposes an optional **REST API** for React Native and mobile apps.

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
- **Optional REST API** for React Native / mobile app integration (disabled by default)
- **Zero external dependencies** — no third-party scripts, fonts, or services
- **Accessible** — ARIA labels, roles, keyboard navigation, and live regions
- **Mobile-optimised** — 44 px minimum touch targets, Chrome Android keep-alive fix

---

## Admin Menu

The plugin adds a top-level **WP Speech** menu to the WordPress sidebar with three pages:

```
WP Speech
├── Settings
│   ├── Voice tab     — voice, speed, pitch, volume
│   ├── Display tab   — post types, button colour, position, player features
│   ├── Preview tab   — live voice test + player mockup
│   └── API tab       — enable/disable REST API
├── Analytics         — feature status, post counts, config summary
└── Help              — FAQ and quick links
```

---

## Installation

1. Upload the `wpspeech` folder to `/wp-content/plugins/`
2. Activate the plugin via **Plugins** in WordPress admin
3. Go to **WP Speech → Settings** to configure voice and display options
4. Visit any single post to see the player in action

---

## Settings Reference

### Voice Tab (`WP Speech → Settings → Voice`)

| Setting | Default | Range |
|---------|---------|-------|
| Voice | Browser Default | Varies by device / OS |
| Speed | 1.0x | 0.5 – 2.0 |
| Pitch | 1.0 | 0.0 – 2.0 |
| Volume | 1.0 | 0.0 – 1.0 |

> Voices are provided by the visitor's operating system. The saved voice name is a preference — if it is unavailable on a visitor's device, their browser default is used.

### Display Tab (`WP Speech → Settings → Display`)

| Setting | Default | Options |
|---------|---------|---------|
| Enable on Post Types | Post | Any registered public post type |
| Button Colour | `#d60017` | Any hex colour |
| Button Position | Before Content | Before / After |
| Progress Bar | On | On / Off |
| Speed Control | On | On / Off |
| Sticky Player | On | On / Off |

### Preview Tab (`WP Speech → Settings → Preview`)

Type or paste any text and click **Play Preview** to hear the current voice settings spoken aloud. The player mockup below updates live when you change the button colour.

### API Tab (`WP Speech → Settings → API`)

Toggle the REST API on or off. All endpoints are shown here for reference. The API is **disabled by default**.

---

## Gutenberg Block

Search for **"WP Speech Player"** in the block inserter to place the player at any exact position within your content. When the block is present in a post, global auto-insertion via the `the_content` filter is automatically skipped for that post to prevent duplicate players.

---

## How It Works

1. The `the_content` filter injects the player HTML on singular post views (or use the Gutenberg block for manual placement)
2. JavaScript extracts the article text from the DOM and splits it into sentences
3. Each sentence is spoken via `SpeechSynthesisUtterance`, chained through the `onend` event
4. Sentence-by-sentence playback avoids Chrome's known 15-second utterance timeout

---

## REST API

The REST API is **opt-in** and must be enabled from **WP Speech → Settings → API**. All endpoints are read-only and expose only published post content — the same content already visible to any site visitor. No authentication is required.

**Base URL:** `https://yoursite.com/wp-json/wpspeech/v1`

---

### `GET /speech/{id}`

Returns article content as clean plain text split into sentences, ready for native TTS.

**Parameters:**

| Param | Type | Required | Description |
|-------|------|----------|-------------|
| `id` | integer | Yes | WordPress post ID |

**Response:**

```json
{
  "post_id": 42,
  "title": "Getting Started with WP Speech",
  "plain_text": "WP Speech adds a text-to-speech player to your posts...",
  "sentences": [
    "WP Speech adds a text-to-speech player to your posts.",
    "It uses the Web Speech API built into modern browsers.",
    "..."
  ],
  "sentence_count": 28,
  "word_count": 450,
  "estimated_duration_seconds": 180,
  "tts_settings": {
    "speech_rate": 1.0,
    "pitch": 1.0,
    "volume": 1.0,
    "voice_name": ""
  },
  "excerpt": "WP Speech adds a text-to-speech player to your posts...",
  "featured_image": "https://yoursite.com/wp-content/uploads/cover.jpg",
  "author": "John Doe",
  "date": "2025-06-15T10:30:00+00:00"
}
```

**Error responses:**

| Status | Code | Reason |
|--------|------|--------|
| 404 | `wpspeech_post_not_found` | Post doesn't exist or isn't published |
| 403 | `wpspeech_not_enabled` | TTS not enabled for this post type |

---

### `GET /settings`

Returns the current TTS configuration. Use these values to configure the native TTS engine in your app.

**Response:**

```json
{
  "tts_settings": {
    "speech_rate": 1.0,
    "pitch": 1.0,
    "volume": 1.0,
    "voice_name": ""
  },
  "enabled_post_types": ["post", "page"]
}
```

---

### `GET /posts`

Returns a paginated list of posts that have TTS enabled.

**Parameters:**

| Param | Type | Default | Description |
|-------|------|---------|-------------|
| `post_type` | string | `post` | Post type slug |
| `per_page` | integer | `10` | Results per page (max 50) |
| `page` | integer | `1` | Page number |
| `search` | string | `""` | Search query |

**Response:**

```json
{
  "posts": [
    {
      "id": 42,
      "title": "Getting Started with WP Speech",
      "excerpt": "WP Speech adds a text-to-speech player...",
      "word_count": 450,
      "estimated_duration_seconds": 180,
      "featured_image": "https://yoursite.com/wp-content/uploads/cover.jpg",
      "author": "John Doe",
      "date": "2025-06-15T10:30:00+00:00",
      "speech_endpoint": "https://yoursite.com/wp-json/wpspeech/v1/speech/42"
    }
  ],
  "total": 25,
  "total_pages": 3,
  "page": 1,
  "per_page": 10
}
```

---

## React Native Integration

### Using `expo-speech` (Expo)

```bash
npx expo install expo-speech
```

```jsx
import * as Speech from 'expo-speech';
import { useState, useEffect } from 'react';
import { View, Text, TouchableOpacity, ActivityIndicator } from 'react-native';

const SITE_URL = 'https://yoursite.com';

export function ArticlePlayer({ postId }) {
  const [sentences, setSentences] = useState([]);
  const [settings, setSettings]   = useState({});
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isPlaying, setIsPlaying] = useState(false);
  const [loading, setLoading]     = useState(true);
  const [title, setTitle]         = useState('');

  useEffect(() => {
    fetch(`${SITE_URL}/wp-json/wpspeech/v1/speech/${postId}`)
      .then(res => res.json())
      .then(data => {
        setSentences(data.sentences);
        setSettings(data.tts_settings);
        setTitle(data.title);
        setLoading(false);
      });
  }, [postId]);

  const speakFrom = (index) => {
    if (index >= sentences.length) {
      setIsPlaying(false);
      setCurrentIndex(0);
      return;
    }
    setCurrentIndex(index);
    setIsPlaying(true);
    Speech.speak(sentences[index], {
      rate:    settings.speech_rate || 1.0,
      pitch:   settings.pitch       || 1.0,
      volume:  settings.volume      || 1.0,
      onDone:  () => speakFrom(index + 1),
      onError: () => { setIsPlaying(false); setCurrentIndex(0); },
    });
  };

  if (loading) return <ActivityIndicator />;

  return (
    <View>
      <Text>{title}</Text>
      <TouchableOpacity onPress={() => isPlaying ? Speech.stop() || setIsPlaying(false) : speakFrom(currentIndex)}>
        <Text>{isPlaying ? 'Pause' : 'Listen'}</Text>
      </TouchableOpacity>
      <Text>{currentIndex} / {sentences.length}</Text>
    </View>
  );
}
```

---

### Using `react-native-tts` (Bare React Native)

```bash
npm install react-native-tts
cd ios && pod install
```

```jsx
import Tts from 'react-native-tts';
import { useState, useEffect } from 'react';

const SITE_URL = 'https://yoursite.com';

export function useArticleTTS(postId) {
  const [sentences, setSentences] = useState([]);
  const [currentIndex, setCurrentIndex] = useState(0);
  const [isPlaying, setIsPlaying] = useState(false);

  useEffect(() => {
    fetch(`${SITE_URL}/wp-json/wpspeech/v1/speech/${postId}`)
      .then(res => res.json())
      .then(data => {
        setSentences(data.sentences);
        Tts.setDefaultRate(data.tts_settings.speech_rate || 1.0);
        Tts.setDefaultPitch(data.tts_settings.pitch       || 1.0);
      });

    const onFinish = Tts.addEventListener('tts-finish', () => {
      setCurrentIndex(prev => {
        const next = prev + 1;
        if (next < sentences.length) { Tts.speak(sentences[next]); return next; }
        setIsPlaying(false);
        return 0;
      });
    });

    return () => onFinish.remove();
  }, [postId]);

  return {
    play:  () => { setIsPlaying(true);  Tts.speak(sentences[currentIndex]); },
    stop:  () => { setIsPlaying(false); Tts.stop(); setCurrentIndex(0); },
    isPlaying,
    progress: sentences.length ? Math.round((currentIndex / sentences.length) * 100) : 0,
  };
}
```

---

## Browser Support

| Feature | Chrome 33+ | Safari 7+ | Firefox 49+ | Edge 14+ |
|---------|:----------:|:---------:|:-----------:|:--------:|
| Speech Synthesis | ✓ | ✓ | ✓ | ✓ |
| Pause / Resume | ✓ | iOS: limited | ✓ | ✓ |

---

## File Structure

```
wpspeech/
├── wpspeech.php                          # Plugin bootstrap, constants, activation hook
├── uninstall.php                         # Removes plugin data on deletion
├── readme.txt                            # WordPress.org listing
├── package.json                          # npm config for building blocks
├── .gitignore
├── assets/
│   ├── css/
│   │   ├── wpspeech-admin.css            # Admin dashboard styles
│   │   └── wpspeech-frontend.css         # Frontend player styles
│   └── js/
│       ├── wpspeech-admin.js             # Admin: tabs, sliders, voice picker, preview
│       └── wpspeech-frontend.js          # Frontend: Web Speech API player
├── includes/
│   ├── class-wpspeech-admin.php          # WP Speech menu + Settings / Analytics / Help pages
│   ├── class-wpspeech-frontend.php       # the_content filter, asset enqueue
│   ├── class-wpspeech-blocks.php         # Gutenberg block registration
│   └── class-wpspeech-rest-api.php       # REST API endpoints
├── src/
│   └── blocks/
│       └── wpspeech-player/
│           ├── block.json                # Block metadata
│           ├── index.js                  # Block editor script
│           ├── editor.css                # Block editor styles
│           └── render.php                # Server-side render
└── languages/
    └── wpspeech.pot                      # Translation template (103 strings)
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
- `expo-speech` or `react-native-tts` for React Native integration

---

## License

GPL v2 or later — see [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html)
