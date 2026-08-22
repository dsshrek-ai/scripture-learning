// Fill this in after deploying api/api.php (see SETUP.md).
const CONFIG = {
  API_URL: "https://seniorfamily.org/scripture-api/api.php",
};

function isConfigured() {
  return CONFIG.API_URL && !CONFIG.API_URL.includes("YOUR_");
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str ?? '';
  return div.innerHTML;
}

function showPlaceholder(container, message) {
  container.innerHTML = `<p class="placeholder-note">${message}</p>`;
}

// ---- Auth ----
//
// Every page in this app requires login — unlike the choir app, there's no
// public/anonymous content here, since scripture progress is inherently
// personal. AUTH_READY resolves once a logged-in, authorized user is
// confirmed; every API call awaits it first. Login uses the same
// credentials as My Apps Hub (shared users/sessions tables), gated by an
// app_access grant for 'scripture-learning'.

const AUTH_KEY = 'scriptureAuth';
let auth = null; // { token, displayName }

function loadAuth() { try { return JSON.parse(localStorage.getItem(AUTH_KEY)); } catch (e) { return null; } }
function saveAuth(a) { localStorage.setItem(AUTH_KEY, JSON.stringify(a)); }
function clearAuth() { localStorage.removeItem(AUTH_KEY); }

function authHeaders() {
  const headers = { 'Content-Type': 'application/json' };
  if (auth && auth.token) headers['Authorization'] = `Bearer ${auth.token}`;
  return headers;
}

// Calls an action with the current auth header attached. GET when no
// payload is given, POST otherwise — matches how every action in api.php
// expects to be called.
async function callApi(action, payload) {
  if (!isConfigured()) throw new Error('not-configured');
  if (payload) {
    const res = await fetch(CONFIG.API_URL, {
      method: 'POST',
      headers: authHeaders(),
      body: JSON.stringify({ action, ...payload }),
    });
    return res.json();
  }
  const res = await fetch(`${CONFIG.API_URL}?action=${encodeURIComponent(action)}`, {
    headers: authHeaders(),
  });
  return res.json();
}

// GET with query-string params (for listScriptures?filter=...).
async function callApiGet(action, params) {
  if (!isConfigured()) throw new Error('not-configured');
  const qs = new URLSearchParams({ action, ...(params || {}) });
  const res = await fetch(`${CONFIG.API_URL}?${qs}`, { headers: authHeaders() });
  return res.json();
}

async function checkAccess() {
  try {
    const result = await callApi('checkAccess');
    return !!result.ok;
  } catch (e) {
    return false;
  }
}

// Hides every existing body child, shows a login card, and resolves once
// login + access are confirmed — same hide/restore mechanic used elsewhere
// in this app family for a blocking gate screen.
function showLoginGate(message) {
  return new Promise((resolve) => {
    const hidden = Array.from(document.body.children);
    hidden.forEach(el => { el.dataset.gateHidden = el.style.display; el.style.display = 'none'; });

    const overlay = document.createElement('div');
    overlay.id = 'auth-gate-overlay';
    overlay.innerHTML = `
      <div class="card" style="max-width:400px; margin:3rem auto;">
        <h2>Scripture Learning</h2>
        <p class="placeholder-note">Same login as My Apps Hub. Not signed up yet? Sign up there first, then ask to be granted access to Scripture Learning.</p>
        <label for="gate-email">Email</label>
        <input type="email" id="gate-email" autocomplete="username">
        <label for="gate-pw">Password</label>
        <input type="password" id="gate-pw" autocomplete="current-password">
        <p id="gate-error" class="placeholder-note" style="display:none;"></p>
        <p><button id="gate-submit" class="btn">Log In</button></p>
      </div>
    `;
    document.body.appendChild(overlay);

    const emailEl = overlay.querySelector('#gate-email');
    const pwEl = overlay.querySelector('#gate-pw');
    const errorEl = overlay.querySelector('#gate-error');
    const submitBtn = overlay.querySelector('#gate-submit');

    if (message) {
      errorEl.textContent = message;
      errorEl.style.display = '';
    }

    async function attempt() {
      const username = emailEl.value.trim();
      const password = pwEl.value;
      errorEl.style.display = 'none';
      if (!username || !password) return;
      submitBtn.disabled = true;
      try {
        const data = await callApi('login', { username, password });
        if (!data.token) {
          errorEl.textContent = data.error || 'Incorrect email or password.';
          errorEl.style.display = '';
          submitBtn.disabled = false;
          return;
        }
        auth = { token: data.token, displayName: data.displayName || username };
        saveAuth(auth);
        if (await checkAccess()) {
          overlay.remove();
          hidden.forEach(el => { el.style.display = el.dataset.gateHidden; delete el.dataset.gateHidden; });
          resolve();
        } else {
          clearAuth();
          auth = null;
          errorEl.textContent = "You're logged in, but not authorized for Scripture Learning. Ask to be granted access.";
          errorEl.style.display = '';
          submitBtn.disabled = false;
        }
      } catch (e) {
        errorEl.textContent = 'Something went wrong logging in. Please try again.';
        errorEl.style.display = '';
        submitBtn.disabled = false;
      }
    }

    submitBtn.addEventListener('click', attempt);
    pwEl.addEventListener('keydown', (ev) => { if (ev.key === 'Enter') attempt(); });
  });
}

async function ensureAuth() {
  // Single sign-on: My Apps Hub launches this app with ?token=... when the
  // visitor is already logged in there, so this skips the login screen.
  const ssoToken = new URLSearchParams(window.location.search).get('token');
  if (ssoToken) {
    auth = { token: ssoToken, displayName: '' };
    saveAuth(auth);
    window.history.replaceState({}, document.title, window.location.pathname);
  } else {
    auth = loadAuth();
  }
  if (auth && await checkAccess()) return;
  auth = null;
  clearAuth();
  await showLoginGate();
}

async function logOut() {
  try { await callApi('logout', { token: auth && auth.token }); } catch (e) {}
  clearAuth();
  window.location.reload();
}

const AUTH_READY = ensureAuth();

// Adds a "Log Out" link to the footer, once auth is confirmed.
AUTH_READY.then(() => {
  const footer = document.querySelector('.site-footer');
  if (!footer) return;
  const link = document.createElement('a');
  link.href = '#';
  link.textContent = 'Log Out';
  link.style.marginLeft = '0.75rem';
  link.addEventListener('click', (e) => { e.preventDefault(); logOut(); });
  footer.appendChild(document.createTextNode(' '));
  footer.appendChild(link);
});
