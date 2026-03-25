# n8n Webhook Chat

A WordPress plugin that embeds a fully customizable chat widget connected to any webhook endpoint — built for [n8n](https://n8n.io/) AI agent workflows, but compatible with any HTTP backend.

![License: GPL-2.0+](https://img.shields.io/badge/license-GPL--2.0%2B-blue)
![WordPress](https://img.shields.io/badge/WordPress-5.8%2B-21759b)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-777bb4)

---

## Features

- **Floating launcher** — a corner button that opens a popup chat window
- **Inline iframe mode** — embed a self-contained chat window anywhere via shortcode
- **Connects to any webhook** — point it at an n8n webhook, a custom API, or any HTTP endpoint
- **Session IDs** — auto-generated per-conversation session IDs, passable as `{session_id}` in request body fields
- **Dot-notation response parsing** — read nested JSON replies like `data.message.text`
- **Markdown & URL linkification** — bot replies support `[label](url)` links and bare URLs
- **Typing indicator** — animated dots while waiting for a response
- **Per-page visibility rules** — restrict the widget to specific URL paths
- **Fully styled from the admin panel** — colors, fonts, size, position, avatar, and more
- **No external dependencies** — no npm, no build step, vanilla JS + PHP

---

## Requirements

- WordPress 5.8+
- PHP 7.4+
- An HTTP webhook endpoint (e.g. an n8n workflow with a Webhook node)

---

## Installation

1. Clone or download this repo into `wp-content/plugins/webhook-chat/`
2. Activate the plugin in **Plugins → Installed Plugins**
3. Go to **Settings → Webhook Chat** and enter your webhook URL

---

## n8n Setup

1. In n8n, create a new workflow and add a **Webhook** node
2. Set the method to **POST** and note the webhook URL
3. Add your AI agent logic (e.g. OpenAI, Anthropic, or a custom chain)
4. Return a JSON response with a reply field, e.g.:
   ```json
   { "reply": "Hello! How can I help you?" }
   ```
5. Paste the webhook URL into **Settings → Webhook Chat → Webhook URL**

### n8n Session Tracking

To maintain conversation context across messages, pass the session ID through to your n8n workflow. In **Extra Body Fields**, add:

```
action=sendMessage
sessionId={session_id}
```

Then use `sessionId` in your n8n workflow to look up or store conversation history.

---

## Configuration

Navigate to **Settings → Webhook Chat** in the WordPress admin. Settings are organized into four tabs:

### Webhook

| Setting | Default | Description |
|---|---|---|
| Webhook URL | _(empty)_ | The endpoint that receives chat messages |
| HTTP Method | `POST` | `POST` or `GET` |
| Timeout | `60` | Seconds to wait for a response (10–300) |
| Extra Headers | _(empty)_ | One per line: `Header-Name: value` (e.g. `Authorization: Bearer token123`) |
| Request Field | `chatInput` | JSON key used to send the user's message |
| Response Field | `reply` | JSON key to read the bot reply from. Supports dot notation: `data.text` |
| Extra Body Fields | _(empty)_ | Additional key=value pairs sent with every request. Use `{session_id}` as a placeholder |

### Appearance

Colors (primary, user bubble, bot bubble), font size, border radius, window width/height, and position (`bottom-right`, `bottom-left`, `bottom-center`).

### Content

Chat title, subtitle, welcome message, input placeholder, send button label, launcher icon (`chat`, `support`, or `bot`), optional launcher label, and bot avatar URL.

### Behaviour

| Setting | Default | Description |
|---|---|---|
| Display Mode | `launcher` | `launcher` = floating button; `iframe` = always-open embed via shortcode |
| Iframe Height | `600` | Height in px when using iframe mode |
| Open on Page Load | off | Auto-open the chat window on arrival (launcher mode only) |
| Show Timestamps | on | Show time below each message |
| Show Avatar | on | Show bot avatar beside messages |
| Typing Indicator | on | Show animated dots while waiting for a response |
| Restrict to Pages | _(empty)_ | One URL path per line (e.g. `/contact/`). Leave blank to show on all pages |

---

## Shortcode

Use `[webhook_chat]` to embed the chat widget inline on any page or post.

In **iframe** display mode, this renders a self-contained `<iframe>` that auto-resizes to fit its content.

In **launcher** mode, the shortcode renders the floating launcher inline (useful for page builders).

---

## File Structure

```
webhook-chat/
├── webhook-chat.php          # Plugin bootstrap
├── includes/
│   ├── class-chat.php        # Core logic: enqueue, AJAX handler, shortcode, embed endpoint
│   └── class-settings.php   # Admin settings page + sanitization
├── templates/
│   ├── chat-window.php       # Launcher button + chat popup markup
│   └── iframe-page.php       # Bare HTML page served inside the iframe
└── assets/
    ├── css/chat.css          # All widget styles (CSS custom properties)
    └── js/chat.js            # Chat UI logic (vanilla JS)
```

---

## Security

- All settings are sanitized on save (`esc_url_raw`, `sanitize_hex_color`, allowlists, etc.)
- AJAX requests are protected by a WordPress nonce (`wc_chat_nonce`)
- `REQUEST_URI` is parsed with `wp_parse_url` before path comparison
- The iframe endpoint sets `X-Frame-Options: SAMEORIGIN` and a `frame-ancestors 'self'` CSP header
- `postMessage` resize events are scoped to the site origin (not `*`)
- Bot replies are sanitized with `wp_kses` before output, allowing only `<br>`, `<strong>`, `<em>`, and `<a>` tags

---

## License

GPL-2.0+. See [LICENSE](https://www.gnu.org/licenses/gpl-2.0.html).
