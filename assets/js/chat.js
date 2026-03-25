/* Webhook Chat — frontend script */
(function () {
  'use strict';

  var cfg = window.wcChat || {};
  var STORAGE_KEY = 'wcc_history';

  /* ── helpers ──────────────────────────────────── */
  function $(sel, ctx) { return (ctx || document).querySelector(sel); }
  function $$(sel, ctx) { return Array.from((ctx || document).querySelectorAll(sel)); }

  function formatTime(date) {
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
  }

  function saveHistory(messages) {
    try { sessionStorage.setItem(STORAGE_KEY, JSON.stringify(messages)); } catch (e) {}
  }

  function loadHistory() {
    try { return JSON.parse(sessionStorage.getItem(STORAGE_KEY)) || []; } catch (e) { return []; }
  }

  function getSessionId() {
    var key = 'wcc_session_id';
    try {
      var id = sessionStorage.getItem(key);
      if (!id) {
        // Generate UUID v4
        id = 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
          var r = Math.random() * 16 | 0;
          return (c === 'x' ? r : (r & 0x3 | 0x8)).toString(16);
        });
        sessionStorage.setItem(key, id);
      }
      return id;
    } catch (e) { return ''; }
  }

  /* ── DOM references ───────────────────────────── */
  function initChat(wrapper) {
    var launcherBtn  = $('.wcc-launcher-btn', wrapper);
    var window_      = $('.wcc-window', wrapper);
    var messagesEl   = $('.wcc-messages', wrapper);
    var inputEl      = $('.wcc-input', wrapper);
    var sendBtn      = $('.wcc-send-btn', wrapper);
    var headerClose  = $('.wcc-header-close', wrapper);
    var badge        = $('.wcc-badge', wrapper);

    if (!window_ || !messagesEl) return;

    var embedMode   = !!cfg.embedMode;
    var isOpen      = embedMode; // always open in embed mode
    var isWaiting   = false;
    var unreadCount = 0;
    var history     = loadHistory();

    /* ── open / close ─────────────────────────── */
    function openChat() {
      isOpen = true;
      window_.setAttribute('aria-hidden', 'false');
      if (launcherBtn) launcherBtn.classList.add('is-open');
      unreadCount = 0;
      if (badge) badge.classList.remove('visible');
      setTimeout(function () { if (inputEl) inputEl.focus(); }, embedMode ? 0 : 280);
    }

    function closeChat() {
      if (embedMode) return; // cannot close in embed mode
      isOpen = false;
      window_.setAttribute('aria-hidden', 'true');
      if (launcherBtn) launcherBtn.classList.remove('is-open');
    }

    function toggleChat() {
      isOpen ? closeChat() : openChat();
    }

    if (!embedMode) {
      if (launcherBtn) launcherBtn.addEventListener('click', toggleChat);
      if (headerClose) headerClose.addEventListener('click', closeChat);

      // Close on outside click (launcher mode only)
      document.addEventListener('click', function (e) {
        if (!isOpen) return;
        if (!wrapper.contains(e.target)) closeChat();
      });
    } else {
      // In embed mode hide the close button so UI is clean
      if (headerClose) headerClose.style.display = 'none';
    }

    /* ── message rendering ────────────────────── */
    function appendMessage(role, text, opts) {
      opts = opts || {};
      var row = document.createElement('div');
      row.className = 'wcc-msg-row ' + role + (opts.error ? ' wcc-error-row' : '');

      // Avatar (bot only)
      if (role === 'bot' && cfg.showAvatar) {
        var av = document.createElement('div');
        av.className = 'wcc-avatar';
        if (cfg.avatarUrl) {
          var img = document.createElement('img');
          img.src = cfg.avatarUrl;
          img.alt = '';
          av.appendChild(img);
        } else {
          av.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 2a5 5 0 100 10A5 5 0 0012 2zm0 12c-5.33 0-8 2.67-8 4v2h16v-2c0-1.33-2.67-4-8-4z"/></svg>';
        }
        row.appendChild(av);
      }

      var wrap = document.createElement('div');
      wrap.className = 'wcc-bubble-wrap';

      var bubble = document.createElement('div');
      bubble.className = 'wcc-bubble';
      bubble.innerHTML = text; // server already runs wp_kses; we trust it

      // Force every link to open in a new tab — essential inside iframes
      bubble.querySelectorAll('a[href]').forEach(function (a) {
        a.setAttribute('target', '_blank');
        a.setAttribute('rel', 'noopener noreferrer');
      });

      wrap.appendChild(bubble);

      if (cfg.showTimestamps) {
        var ts = document.createElement('div');
        ts.className = 'wcc-timestamp';
        ts.textContent = formatTime(opts.time || new Date());
        wrap.appendChild(ts);
      }

      row.appendChild(wrap);
      messagesEl.appendChild(row);
      scrollToBottom();
      return row;
    }

    function scrollToBottom() {
      messagesEl.scrollTop = messagesEl.scrollHeight;
    }

    /* ── typing indicator ─────────────────────── */
    var typingRow = null;

    function showTyping() {
      if (!cfg.typingIndicator || typingRow) return;

      typingRow = document.createElement('div');
      typingRow.className = 'wcc-msg-row bot wcc-typing-row';

      if (cfg.showAvatar) {
        var av = document.createElement('div');
        av.className = 'wcc-avatar';
        if (cfg.avatarUrl) {
          var img = document.createElement('img');
          img.src = cfg.avatarUrl;
          img.alt = '';
          av.appendChild(img);
        } else {
          av.innerHTML = '<svg viewBox="0 0 24 24"><path d="M12 2a5 5 0 100 10A5 5 0 0012 2zm0 12c-5.33 0-8 2.67-8 4v2h16v-2c0-1.33-2.67-4-8-4z"/></svg>';
        }
        typingRow.appendChild(av);
      }

      var tb = document.createElement('div');
      tb.className = 'wcc-typing-bubble';
      tb.innerHTML = '<span class="wcc-dot"></span><span class="wcc-dot"></span><span class="wcc-dot"></span>';
      typingRow.appendChild(tb);

      messagesEl.appendChild(typingRow);
      scrollToBottom();
    }

    function hideTyping() {
      if (typingRow) {
        typingRow.remove();
        typingRow = null;
      }
    }

    /* ── send message ─────────────────────────── */
    function sendMessage() {
      if (isWaiting) return;
      var text = inputEl ? inputEl.value.trim() : '';
      if (!text) return;

      // Display user message
      appendMessage('user', escapeHtml(text));
      history.push({ role: 'user', text: text, time: Date.now() });
      saveHistory(history);

      // Clear input & disable
      inputEl.value = '';
      inputEl.style.height = '';
      sendBtn.disabled = true;
      isWaiting = true;

      showTyping();

      // AJAX request
      var fd = new FormData();
      fd.append('action', 'wc_chat_send');
      fd.append('nonce', cfg.nonce);
      fd.append('message', text);
      fd.append('session_id', getSessionId());

      fetch(cfg.ajaxUrl, { method: 'POST', body: fd })
        .then(function (res) { return res.json(); })
        .then(function (data) {
          hideTyping();
          var replyText;
          if (data.success) {
            replyText = data.data.reply;
            history.push({ role: 'bot', text: replyText, time: Date.now() });
            saveHistory(history);
          } else {
            replyText = data.data && data.data.message ? data.data.message : 'Something went wrong.';
            appendMessage('bot', escapeHtml(replyText), { error: true });
            return;
          }
          appendMessage('bot', replyText);

          // Unread badge if closed
          if (!isOpen) {
            unreadCount++;
            if (badge) {
              badge.textContent = unreadCount;
              badge.classList.add('visible');
            }
          }
        })
        .catch(function (err) {
          hideTyping();
          appendMessage('bot', 'Network error — please try again.', { error: true });
          console.error('[Webhook Chat]', err);
        })
        .finally(function () {
          isWaiting = false;
          sendBtn.disabled = false;
          if (inputEl) inputEl.focus();
        });
    }

    /* ── events ───────────────────────────────── */
    if (sendBtn) {
      sendBtn.addEventListener('click', sendMessage);
    }

    if (inputEl) {
      inputEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
          e.preventDefault();
          sendMessage();
        }
      });

      // Auto-grow textarea
      inputEl.addEventListener('input', function () {
        this.style.height = '';
        this.style.height = Math.min(this.scrollHeight, 100) + 'px';
      });
    }

    /* ── restore session history ──────────────── */
    if (history.length) {
      history.forEach(function (msg) {
        appendMessage(msg.role, msg.role === 'user' ? escapeHtml(msg.text) : msg.text, { time: new Date(msg.time) });
      });
    } else if (cfg.welcomeMessage) {
      // Show welcome message without storing in history
      appendMessage('bot', escapeHtml(cfg.welcomeMessage));
    }

    /* ── init state ───────────────────────────── */
    if (embedMode || cfg.openOnLoad) {
      openChat();
    }
  }

  /* ── escape helper ────────────────────────────── */
  function escapeHtml(str) {
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/\n/g, '<br>');
  }

  /* ── boot ─────────────────────────────────────── */
  function boot() {
    $$('.wcc-chat-root').forEach(initChat);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot);
  } else {
    boot();
  }
})();
