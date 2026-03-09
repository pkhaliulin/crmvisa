<!DOCTYPE html>
<html lang="{{ $locale ?? 'ru' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>VisaBor — {{ __('landing.maint_title') }}</title>
<meta name="robots" content="noindex, nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap" rel="stylesheet">
<style>
:root {
  --navy: #071a10;
  --green: #22c55e;
  --blue-bright: #16a34a;
  --cyan: #059669;
  --red: #ef4444;
  --text-primary: #0a1f12;
  --text-muted: #7c9a8a;
  --text-secondary: #3f5949;
  --border: #d4e5d9;
  --gray-50: #f7faf8;
  --font-display: 'Sora', sans-serif;
  --font-body: 'DM Sans', sans-serif;
}
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
  font-family: var(--font-body);
  min-height: 100vh;
  display: flex; align-items: center; justify-content: center;
  background: var(--navy);
  color: white;
  padding: 24px;
}
.disabled-page {
  text-align: center;
  max-width: 440px;
  width: 100%;
}
.disabled-logo {
  font-family: var(--font-display);
  font-size: 2.2rem;
  font-weight: 800;
  color: white;
  margin-bottom: 16px;
  letter-spacing: -0.03em;
}
.disabled-logo span { color: var(--cyan); }
.disabled-logo .dot {
  display: inline-block;
  width: 8px; height: 8px;
  background: var(--cyan);
  border-radius: 50%;
  margin-left: 6px;
  vertical-align: middle;
}
.disabled-title {
  font-family: var(--font-display);
  font-size: 1.5rem;
  font-weight: 700;
  margin-bottom: 10px;
}
.disabled-desc {
  color: rgba(255,255,255,0.5);
  font-size: 0.95rem;
  margin-bottom: 40px;
  line-height: 1.6;
}
.login-card {
  background: rgba(255,255,255,0.06);
  border: 1px solid rgba(255,255,255,0.12);
  border-radius: 24px;
  padding: 36px;
  text-align: left;
}
.login-card h3 {
  font-family: var(--font-display);
  font-weight: 700;
  font-size: 1.15rem;
  color: white;
  margin-bottom: 20px;
  text-align: center;
}
.phone-row {
  display: flex;
  border: 2px solid rgba(255,255,255,0.15);
  border-radius: 12px;
  overflow: hidden;
  transition: border-color 0.2s;
  margin-bottom: 12px;
}
.phone-row:focus-within { border-color: var(--cyan); }
.phone-prefix {
  padding: 14px 16px;
  font-weight: 600;
  color: white;
  background: rgba(255,255,255,0.06);
  border-right: 1px solid rgba(255,255,255,0.12);
  font-size: 1rem;
}
.phone-input {
  flex: 1;
  padding: 14px 16px;
  border: none;
  outline: none;
  font-size: 1rem;
  font-weight: 500;
  color: white;
  background: transparent;
  letter-spacing: 0.05em;
}
.phone-input::placeholder { color: rgba(255,255,255,0.25); }
.pin-input {
  width: 100%;
  padding: 14px;
  border: 2px solid rgba(255,255,255,0.15);
  border-radius: 12px;
  text-align: center;
  font-size: 2rem;
  font-weight: 700;
  letter-spacing: 0.5em;
  color: white;
  background: transparent;
  outline: none;
  transition: border-color 0.2s;
  margin-bottom: 4px;
}
.pin-input:focus { border-color: var(--cyan); }
.pin-input::placeholder { color: rgba(255,255,255,0.2); }
.otp-row { display: flex; gap: 10px; justify-content: center; margin-bottom: 12px; }
.otp-box {
  width: 60px; height: 68px;
  text-align: center;
  font-size: 1.6rem;
  font-weight: 700;
  border: 2px solid rgba(255,255,255,0.15);
  border-radius: 12px;
  outline: none;
  color: white;
  background: transparent;
  transition: border-color 0.2s;
}
.otp-box:focus { border-color: var(--cyan); }
.btn-main {
  width: 100%;
  padding: 14px;
  background: linear-gradient(135deg, #15803d, #16a34a);
  color: white;
  border: none;
  border-radius: 12px;
  font-size: 1rem;
  font-weight: 600;
  cursor: pointer;
  font-family: var(--font-display);
  transition: transform 0.2s, box-shadow 0.2s;
}
.btn-main:hover { transform: translateY(-1px); box-shadow: 0 8px 24px rgba(22,163,74,0.3); }
.btn-main:disabled { opacity: 0.4; cursor: default; transform: none; box-shadow: none; }
.btn-link {
  background: none; border: none; color: rgba(255,255,255,0.4);
  font-size: 0.85rem; cursor: pointer; font-family: var(--font-body);
  transition: color 0.2s;
}
.btn-link:hover { color: rgba(255,255,255,0.7); }
.error-msg {
  font-size: 0.85rem;
  color: var(--red);
  display: none;
  margin-top: 4px;
  margin-bottom: 4px;
}
.step { display: none; }
.step.active { display: block; }
.support-text {
  margin-top: 24px;
  font-size: 0.8rem;
  color: rgba(255,255,255,0.25);
  text-align: center;
}
.lang-toggle {
  display: inline-flex;
  border-radius: 8px;
  border: 1px solid rgba(255,255,255,0.15);
  overflow: hidden;
  margin-bottom: 32px;
}
.lang-item {
  font-family: var(--font-display);
  font-size: 0.75rem;
  font-weight: 600;
  color: rgba(255,255,255,0.35);
  padding: 5px 12px;
  text-decoration: none;
  transition: all 0.2s;
  line-height: 1;
}
.lang-item.active { background: rgba(255,255,255,0.15); color: white; }
</style>
</head>
<body>

<div class="disabled-page">
  <div class="lang-toggle">
    <a href="/locale/ru" class="lang-item {{ ($locale ?? 'ru') === 'ru' ? 'active' : '' }}">RU</a>
    <a href="/locale/uz" class="lang-item {{ ($locale ?? 'ru') === 'uz' ? 'active' : '' }}">UZ</a>
  </div>
  <div class="disabled-logo">visa<span>bor</span><span class="dot"></span></div>
  <div class="disabled-title">{{ __('landing.maint_title') }}</div>
  <div class="disabled-desc">{{ __('landing.maint_desc') }}</div>

  <div class="login-card">
    <h3 id="cardTitle">{{ __('landing.maint_login_title') }}</h3>

    <!-- Step 1: Phone -->
    <div id="stepPhone" class="step active">
      <div class="phone-row">
        <span class="phone-prefix">+998</span>
        <input id="phoneInput" class="phone-input" type="tel" inputmode="numeric" placeholder="97 123 45 67" maxlength="12"
          oninput="formatPhoneInput(this)" onkeydown="phoneKeydown(event)" onkeyup="if(event.key==='Enter')sendOtp()">
      </div>
      <p id="phoneError" class="error-msg"></p>
      <button id="btnSendOtp" class="btn-main" onclick="sendOtp()" style="margin-top:12px;">{{ __('landing.maint_get_code') }}</button>
      <p style="margin-top:14px; text-align:center;">
        <button class="btn-link" onclick="showStep('loginPin')">{{ __('landing.maint_login_pin') }}</button>
      </p>
    </div>

    <!-- Step 2: OTP -->
    <div id="stepOtp" class="step">
      <p style="margin-bottom:16px; font-size:0.9rem; color:rgba(255,255,255,0.5); text-align:center;">
        {{ __('landing.maint_code_sent') }} <strong id="otpPhone" style="color:white;"></strong>
      </p>
      <div class="otp-row">
        <input class="otp-box" type="tel" inputmode="numeric" maxlength="1" oninput="otpInput(this,0)" onkeydown="otpKeydown(event,0)">
        <input class="otp-box" type="tel" inputmode="numeric" maxlength="1" oninput="otpInput(this,1)" onkeydown="otpKeydown(event,1)">
        <input class="otp-box" type="tel" inputmode="numeric" maxlength="1" oninput="otpInput(this,2)" onkeydown="otpKeydown(event,2)">
        <input class="otp-box" type="tel" inputmode="numeric" maxlength="1" oninput="otpInput(this,3)" onkeydown="otpKeydown(event,3)">
      </div>
      <p id="otpError" class="error-msg" style="text-align:center;"></p>
      <button id="btnVerifyOtp" class="btn-main" onclick="verifyOtp()" disabled style="margin-top:12px;">{{ __('landing.maint_confirm') }}</button>
      <div style="margin-top:14px; display:flex; justify-content:space-between; font-size:0.85rem;">
        <button class="btn-link" onclick="showStep('phone')">{{ __('landing.maint_change_number') }}</button>
        <span id="resendWrap"><span style="color:rgba(255,255,255,0.35);">{{ __('landing.maint_resend_in') }} <span id="resendTimer">60</span>s</span></span>
      </div>
    </div>

    <!-- Step 3: Set PIN -->
    <div id="stepPin" class="step">
      <p style="margin-bottom:16px; font-size:0.9rem; color:rgba(255,255,255,0.5);">{{ __('landing.maint_set_pin_hint') }}</p>
      <input id="pinInput" class="pin-input" type="tel" inputmode="numeric" maxlength="4" placeholder="----"
        oninput="this.value=this.value.replace(/\D/g,'').slice(0,4)">
      <button class="btn-main" onclick="setPin()" style="margin-top:12px;">{{ __('landing.maint_save_pin') }}</button>
      <button class="btn-link" onclick="authFinish()" style="display:block; width:100%; margin-top:8px; padding:10px;">{{ __('landing.maint_skip') }}</button>
    </div>

    <!-- Step 4: Login by PIN -->
    <div id="stepLoginPin" class="step">
      <div class="phone-row" style="margin-bottom:12px;">
        <span class="phone-prefix">+998</span>
        <input id="loginPhoneInput" class="phone-input" type="tel" inputmode="numeric" placeholder="97 123 45 67" maxlength="12"
          oninput="formatPhoneInput(this)" onkeydown="phoneKeydown(event)">
      </div>
      <input id="loginPinInput" class="pin-input" type="tel" inputmode="numeric" maxlength="4" placeholder="----"
        oninput="this.value=this.value.replace(/\D/g,'').slice(0,4)"
        onkeyup="if(event.key==='Enter')loginWithPin()">
      <p id="loginPinError" class="error-msg"></p>
      <button class="btn-main" onclick="loginWithPin()" style="margin-top:12px;">{{ __('landing.maint_login_btn2') }}</button>
      <p style="margin-top:14px; text-align:center;">
        <button class="btn-link" onclick="showStep('phone')">{{ __('landing.maint_login_sms') }}</button>
      </p>
    </div>
  </div>

  <p class="support-text">{{ __('landing.maint_support') }}</p>
</div>

<script>
const T = @json(collect(__('landing'))->only([
    'maint_login_title','maint_get_code','maint_sending','maint_sms_error',
    'maint_confirm','maint_checking','maint_wrong_code','maint_enter_full_number',
    'maint_enter_pin','maint_wrong_pin','maint_login_error','maint_resend_in',
    'maint_resend_now','maint_otp_title','maint_pin_title','maint_pin_login_title',
]));

// Если уже авторизован — перенаправить в ЛК
(function() {
  try {
    if (localStorage.getItem('public_token') && localStorage.getItem('public_user')) {
      window.location.href = '/me/cases';
    }
  } catch(e) {}
})();

const API_BASE = '/api/v1/public';
let authToken = null;
let authUser = null;
let resendInterval = null;

function showStep(step) {
  document.querySelectorAll('.step').forEach(s => s.classList.remove('active'));
  const map = { phone:'stepPhone', otp:'stepOtp', pin:'stepPin', loginPin:'stepLoginPin' };
  const titles = {
    phone: T.maint_login_title,
    otp: T.maint_otp_title,
    pin: T.maint_pin_title,
    loginPin: T.maint_pin_login_title,
  };
  document.getElementById(map[step]).classList.add('active');
  document.getElementById('cardTitle').textContent = titles[step] || T.maint_login_title;
  if (step === 'phone') setTimeout(() => document.getElementById('phoneInput').focus(), 100);
  if (step === 'otp') setTimeout(() => document.querySelectorAll('.otp-box')[0].focus(), 100);
  if (step === 'loginPin') setTimeout(() => document.getElementById('loginPhoneInput').focus(), 100);
}

function formatPhoneInput(el) {
  const raw = el.value.replace(/\D/g, '').slice(0, 9);
  let r = '';
  if (raw.length > 0) r += raw.slice(0, 2);
  if (raw.length > 2) r += ' ' + raw.slice(2, 5);
  if (raw.length > 5) r += ' ' + raw.slice(5, 7);
  if (raw.length > 7) r += ' ' + raw.slice(7, 9);
  el.value = r;
}

function phoneKeydown(e) {
  const ok = ['Backspace','Delete','Tab','ArrowLeft','ArrowRight','Home','End','Enter'];
  if (ok.includes(e.key) || (e.key >= '0' && e.key <= '9')) return;
  e.preventDefault();
}

function getPhoneDigits(inputId) {
  return document.getElementById(inputId).value.replace(/\D/g, '');
}

function showError(id, msg) {
  const el = document.getElementById(id);
  el.textContent = msg; el.style.display = 'block';
}
function hideError(id) {
  document.getElementById(id).style.display = 'none';
}

function otpInput(el, i) {
  el.value = el.value.replace(/\D/g, '').slice(-1);
  if (el.value) el.style.borderColor = 'var(--cyan)';
  const boxes = document.querySelectorAll('.otp-box');
  if (el.value && i < 3) boxes[i+1].focus();
  const code = Array.from(boxes).map(b => b.value).join('');
  document.getElementById('btnVerifyOtp').disabled = code.length < 4;
  if (code.length === 4) verifyOtp();
}

function otpKeydown(e, i) {
  if (e.key === 'Backspace' && !e.target.value && i > 0) {
    const boxes = document.querySelectorAll('.otp-box');
    boxes[i-1].value = ''; boxes[i-1].style.borderColor = 'rgba(255,255,255,0.15)';
    boxes[i-1].focus();
  }
}

function clearOtpBoxes() {
  document.querySelectorAll('.otp-box').forEach(b => { b.value = ''; b.style.borderColor = 'rgba(255,255,255,0.15)'; });
  document.getElementById('btnVerifyOtp').disabled = true;
}

function startResendTimer() {
  let sec = 60;
  const wrap = document.getElementById('resendWrap');
  wrap.innerHTML = '<span style="color:rgba(255,255,255,0.35);">' + T.maint_resend_in + ' <span id="resendTimer">' + sec + '</span>s</span>';
  clearInterval(resendInterval);
  resendInterval = setInterval(() => {
    sec--;
    const t = document.getElementById('resendTimer');
    if (t) t.textContent = sec;
    if (sec <= 0) {
      clearInterval(resendInterval);
      wrap.innerHTML = '<button class="btn-link" onclick="sendOtp()" style="color:white; font-weight:600;">' + T.maint_resend_now + '</button>';
    }
  }, 1000);
}

async function sendOtp() {
  const digits = getPhoneDigits('phoneInput');
  if (digits.length < 9) { showError('phoneError', T.maint_enter_full_number); return; }
  hideError('phoneError');
  const phone = '+998' + digits;
  const btn = document.getElementById('btnSendOtp');
  btn.textContent = T.maint_sending; btn.disabled = true;
  try {
    const r = await fetch(API_BASE + '/auth/send-otp', {
      method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'},
      body: JSON.stringify({ phone })
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.message || T.maint_sms_error);
    document.getElementById('otpPhone').textContent = phone;
    showStep('otp');
    clearOtpBoxes();
    startResendTimer();
  } catch(e) {
    showError('phoneError', e.message || T.maint_sms_error);
  } finally {
    btn.textContent = T.maint_get_code; btn.disabled = false;
  }
}

async function verifyOtp() {
  const boxes = document.querySelectorAll('.otp-box');
  const code = Array.from(boxes).map(b => b.value).join('');
  if (code.length < 4) return;
  hideError('otpError');
  const phone = '+998' + getPhoneDigits('phoneInput');
  const btn = document.getElementById('btnVerifyOtp');
  btn.textContent = T.maint_checking; btn.disabled = true;
  try {
    const r = await fetch(API_BASE + '/auth/verify-otp', {
      method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'},
      body: JSON.stringify({ phone, code })
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.message || T.maint_wrong_code);
    authToken = d.data.token;
    authUser = d.data.user;
    localStorage.setItem('public_token', authToken);
    localStorage.setItem('public_user', JSON.stringify(authUser));
    if (d.data.is_new) {
      showStep('pin');
    } else {
      authFinish();
    }
  } catch(e) {
    showError('otpError', e.message || T.maint_wrong_code);
    clearOtpBoxes();
    setTimeout(() => document.querySelectorAll('.otp-box')[0].focus(), 100);
  } finally {
    btn.textContent = T.maint_confirm; btn.disabled = false;
  }
}

async function setPin() {
  const pin = document.getElementById('pinInput').value;
  if (pin.length < 4) return;
  try {
    await fetch(API_BASE + '/auth/set-pin', {
      method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json','Authorization':'Bearer ' + authToken},
      body: JSON.stringify({ pin })
    });
  } catch(e) {}
  authFinish();
}

async function loginWithPin() {
  const digits = getPhoneDigits('loginPhoneInput');
  const pin = document.getElementById('loginPinInput').value;
  if (digits.length < 9) { showError('loginPinError', T.maint_enter_full_number); return; }
  if (pin.length < 4) { showError('loginPinError', T.maint_enter_pin); return; }
  hideError('loginPinError');
  const phone = '+998' + digits;
  try {
    const r = await fetch(API_BASE + '/auth/login', {
      method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'},
      body: JSON.stringify({ phone, pin })
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.message || T.maint_wrong_pin);
    localStorage.setItem('public_token', d.data.token);
    localStorage.setItem('public_user', JSON.stringify(d.data.user));
    authFinish();
  } catch(e) {
    showError('loginPinError', e.message || T.maint_login_error);
  }
}

function authFinish() {
  window.location.href = '/me/cases';
}
</script>

</body>
</html>
