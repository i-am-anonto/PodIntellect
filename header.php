<?php
// header.php — modal only (no nav)
if (session_status() === PHP_SESSION_NONE) { session_start(); }

// (optional) helpers
if (file_exists(__DIR__ . '/helpers.php')) {
  require_once __DIR__ . '/helpers.php';
  if (!function_exists('current_user')) { function current_user(){ return $_SESSION['user'] ?? null; } }
} else {
  if (!function_exists('current_user')) { function current_user(){ return $_SESSION['user'] ?? null; } }
}
$user = current_user();
?>

<!-- Modal is hidden by default via inline style -->
<div id="auth-modal"
     class="fixed inset-0 z-[60]"
     style="display:none">
  <div id="auth-close" class="absolute inset-0 bg-black/60"></div>

  <div class="relative z-[61] max-w-md w-[92vw] mx-auto mt-24 bg-gray-900 border border-gray-800 rounded-2xl p-6 text-white shadow-2xl">
    <div class="flex gap-2 mb-4">
      <button class="tab-btn px-3 py-2 rounded-md bg-gray-800" data-tab="login">Login</button>
      <button class="tab-btn px-3 py-2 rounded-md bg-gray-800/60" data-tab="signup">Sign up</button>
    </div>

    <!-- Login -->
    <div id="tab-login" class="tab-pane block">
      <form id="login-form" class="space-y-3">
        <label class="text-sm">Email</label>
        <input name="email" type="email" required class="w-full px-3 py-2 rounded-md bg-gray-800 border border-gray-700">
        <label class="text-sm">Password</label>
        <input name="password" type="password" required minlength="6" class="w-full px-3 py-2 rounded-md bg-gray-800 border border-gray-700">
        <button type="submit" class="w-full py-2 rounded-md bg-emerald-600 hover:bg-emerald-700">Login</button>
      </form>
      <div id="login-msg" class="text-sm text-gray-300 mt-2"></div>
    </div>

    <!-- Signup -->
    <div id="tab-signup" class="tab-pane hidden">
      <form id="signup-form" class="space-y-3">
        <label class="text-sm">Name</label>
        <input name="name" type="text" required class="w-full px-3 py-2 rounded-md bg-gray-800 border border-gray-700">
        <label class="text-sm">Email</label>
        <input name="email" type="email" required class="w-full px-3 py-2 rounded-md bg-gray-800 border border-gray-700">
        <label class="text-sm">Password</label>
        <input name="password" type="password" required minlength="6" class="w-full px-3 py-2 rounded-md bg-gray-800 border border-gray-700">
        <button type="submit" class="w-full py-2 rounded-md bg-blue-600 hover:bg-blue-700">Create account</button>
      </form>
      <div id="signup-msg" class="text-sm text-gray-300 mt-2"></div>
    </div>
  </div>
</div>

<script>
// ===== Modal open/close (no aria-hidden; pure display) =====
const modal   = document.getElementById('auth-modal');
const closeBg = document.getElementById('auth-close');

// expose helpers so any page/button can call them
window.openAuthModal  = function(){ if (modal) modal.style.display = 'block'; }
window.closeAuthModal = function(){ if (modal) modal.style.display = 'none'; }

if (closeBg) closeBg.addEventListener('click', window.closeAuthModal);
document.addEventListener('keydown', (e)=>{ if (e.key === 'Escape') window.closeAuthModal(); });

// ===== Tabs =====
const tabBtns = document.querySelectorAll('.tab-btn');
const panes = { login: document.getElementById('tab-login'), signup: document.getElementById('tab-signup') };
tabBtns.forEach(btn=>{
  btn.addEventListener('click', ()=>{
    tabBtns.forEach(b=> b.classList.toggle('bg-gray-800', b===btn));
    tabBtns.forEach(b=> b.classList.toggle('bg-gray-800/60', b!==btn));
    const tab = btn.dataset.tab;
    panes.login.classList.toggle('hidden', tab !== 'login');
    panes.signup.classList.toggle('hidden', tab !== 'signup');
  });
});

// ===== AJAX auth =====
async function submitAuth(formId, action, msgId){
  const form = document.getElementById(formId);
  const msg  = document.getElementById(msgId);
  if (!form) return;
  form.addEventListener('submit', async (e)=>{
    e.preventDefault();
    msg.textContent = action === 'login' ? 'Signing in...' : 'Creating account...';
    const data = new FormData(form); data.append('action', action);
    try{
      const res = await fetch('auth.php', { method:'POST', body:data });
      const json = await res.json();
      if (json.ok) { window.location.href = json.redirect || 'dashboard.php'; }
      else { msg.textContent = json.error || 'Request failed'; }
    }catch(err){ msg.textContent = 'Network error'; }
  });
}
submitAuth('login-form','login','login-msg');
submitAuth('signup-form','register','signup-msg');
</script>
