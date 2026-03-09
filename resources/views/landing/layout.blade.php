<!DOCTYPE html>
<html lang="{{ $locale ?? 'ru' }}">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
@yield('meta')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Sora:wght@300;400;500;600;700;800&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap" rel="stylesheet">
<style>
:root {
  --navy: #071a10;
  --navy-mid: #0c1f14;
  --navy-light: #12291c;
  --blue: #15803d;
  --blue-bright: #16a34a;
  --cyan: #059669;
  --cyan-light: #6ee7b7;
  --teal: #0d9488;
  --green: #22c55e;
  --amber: #f59e0b;
  --red: #ef4444;
  --white: #ffffff;
  --gray-50: #f7faf8;
  --gray-100: #f0f5f1;
  --gray-200: #dde8e0;
  --gray-400: #8a9e90;
  --gray-600: #3f5549;
  --gray-800: #1a2e22;
  --text-primary: #0a1f12;
  --text-secondary: #3f5949;
  --text-muted: #7c9a8a;
  --surface: #ffffff;
  --surface-2: #f7faf8;
  --border: #d4e5d9;
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.08), 0 1px 2px rgba(0,0,0,0.04);
  --shadow-md: 0 4px 16px rgba(0,0,0,0.08), 0 2px 6px rgba(0,0,0,0.05);
  --shadow-lg: 0 20px 60px rgba(0,0,0,0.1), 0 8px 24px rgba(0,0,0,0.06);
  --shadow-blue: 0 8px 32px rgba(22,163,74,0.25);
  --radius: 16px;
  --radius-sm: 10px;
  --radius-lg: 24px;
  --radius-xl: 32px;
  --font-display: 'Sora', sans-serif;
  --font-body: 'DM Sans', sans-serif;
}

* { margin: 0; padding: 0; box-sizing: border-box; }

html { scroll-behavior: smooth; }

body {
  font-family: var(--font-body);
  color: var(--text-primary);
  background: var(--white);
  line-height: 1.6;
  overflow-x: hidden;
}

/* TYPOGRAPHY */
h1, h2, h3, h4, h5 { font-family: var(--font-display); font-weight: 700; line-height: 1.2; }
h1 { font-size: clamp(2.2rem, 5vw, 4rem); letter-spacing: -0.02em; }
h2 { font-size: clamp(1.7rem, 3.5vw, 2.8rem); letter-spacing: -0.015em; }
h3 { font-size: clamp(1.2rem, 2vw, 1.6rem); letter-spacing: -0.01em; }

/* UTILITY */
.container { max-width: 1200px; margin: 0 auto; padding: 0 24px; }
.container-wide { max-width: 1400px; margin: 0 auto; padding: 0 24px; }

.tag {
  display: inline-flex; align-items: center; gap: 6px;
  font-family: var(--font-display); font-size: 0.72rem; font-weight: 600;
  letter-spacing: 0.08em; text-transform: uppercase;
  padding: 6px 14px; border-radius: 100px;
  background: rgba(22,163,74,0.08); color: var(--blue-bright);
  border: 1px solid rgba(22,163,74,0.18);
}
.tag.cyan { background: rgba(5,150,105,0.08); color: var(--cyan); border-color: rgba(5,150,105,0.2); }
.tag.green { background: rgba(34,197,94,0.08); color: var(--green); border-color: rgba(34,197,94,0.2); }

/* Shimmer */
@keyframes shimmer-drift {
  0%   { transform: translate(0%, 0%); }
  20%  { transform: translate(-30%, -18%); }
  40%  { transform: translate(-12%, -35%); }
  60%  { transform: translate(-35%, -12%); }
  80%  { transform: translate(-18%, -30%); }
  100% { transform: translate(0%, 0%); }
}
@keyframes pulse-glow {
  0%, 100% { box-shadow: 0 8px 32px rgba(22,163,74,0.18); }
  50% { box-shadow: 0 8px 36px rgba(22,163,74,0.3), 0 0 12px rgba(22,163,74,0.08); }
}

.btn {
  display: inline-flex; align-items: center; justify-content: center; gap: 8px;
  font-family: var(--font-display); font-weight: 600; font-size: 0.95rem;
  padding: 14px 28px; border-radius: 100px; border: none; cursor: pointer;
  text-decoration: none; transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
  letter-spacing: -0.01em; white-space: nowrap;
  position: relative; overflow: hidden;
}
.btn-primary {
  background: linear-gradient(135deg, #15803d, #16a34a);
  color: white;
  animation: pulse-glow 6s ease-in-out infinite;
}
.btn-primary::after {
  content: '';
  position: absolute; top: -150%; left: -150%;
  width: 400%; height: 400%;
  background:
    radial-gradient(ellipse 40% 50% at 38% 40%, rgba(255,255,255,0.32) 0%, transparent 65%),
    radial-gradient(ellipse 30% 42% at 68% 60%, rgba(255,255,255,0.25) 0%, transparent 65%),
    radial-gradient(ellipse 35% 30% at 55% 33%, rgba(255,255,255,0.20) 0%, transparent 60%);
  pointer-events: none;
  animation: shimmer-drift 6s ease-in-out infinite;
}
.btn-primary:hover {
  transform: translateY(-2px) scale(1.02);
  box-shadow: 0 16px 48px rgba(22,163,74,0.35), 0 0 20px rgba(22,163,74,0.1);
}
.btn-secondary {
  background: white; color: var(--text-primary);
  border: 1.5px solid var(--border); box-shadow: var(--shadow-sm);
}
.btn-secondary:hover { border-color: var(--blue-bright); color: var(--blue-bright); transform: translateY(-1px); }
.btn-ghost { background: transparent; color: var(--blue-bright); padding: 10px 0; }
.btn-ghost:hover { gap: 12px; }
.btn-lg { padding: 18px 36px; font-size: 1.05rem; }
.btn-sm { padding: 10px 20px; font-size: 0.85rem; }

/* SECTION */
.section { padding: 100px 0; }
.section-sm { padding: 60px 0; }
.section-header { text-align: center; margin-bottom: 64px; }
.section-header .tag { margin-bottom: 20px; }
.section-header h2 { margin-bottom: 16px; }
.section-header p { font-size: 1.15rem; color: var(--text-secondary); max-width: 580px; margin: 0 auto; }

/* FADE IN ANIMATIONS */
.fade-up { opacity: 0; transform: translateY(32px); transition: all 0.7s cubic-bezier(0.4,0,0.2,1); }
.fade-up.visible { opacity: 1; transform: translateY(0); }
.fade-up-delay-1 { transition-delay: 0.1s; }
.fade-up-delay-2 { transition-delay: 0.2s; }
.fade-up-delay-3 { transition-delay: 0.3s; }
.fade-up-delay-4 { transition-delay: 0.4s; }

/* ===================== NAVBAR ===================== */
.navbar {
  position: fixed; top: 0; left: 0; right: 0; z-index: 1000;
  background: rgba(255,255,255,0.85); backdrop-filter: blur(20px);
  border-bottom: 1px solid rgba(226,232,240,0.6);
  transition: all 0.3s ease;
}
.navbar-inner {
  display: flex; align-items: center; justify-content: space-between;
  height: 68px; padding: 0 32px; max-width: 1300px; margin: 0 auto;
}
.logo {
  font-family: var(--font-display); font-weight: 800; font-size: 1.4rem;
  color: var(--text-primary); text-decoration: none; letter-spacing: -0.03em;
  display: flex; align-items: center; gap: 8px;
}
.logo-dot { width: 8px; height: 8px; background: var(--blue-bright); border-radius: 50%; margin-top: 2px; }
.logo span { color: var(--blue-bright); }
.nav-links { display: flex; align-items: center; gap: 8px; }
.nav-link {
  font-family: var(--font-display); font-size: 0.88rem; font-weight: 500;
  color: var(--text-secondary); text-decoration: none; padding: 8px 14px; border-radius: 8px;
  transition: all 0.2s;
}
.nav-link:hover { color: var(--text-primary); background: var(--gray-100); }
.nav-actions { display: flex; align-items: center; gap: 12px; }
.nav-lang {
  font-family: var(--font-display); font-size: 0.82rem; font-weight: 600;
  color: var(--text-secondary); cursor: pointer; padding: 6px 12px; border-radius: 8px;
  border: 1px solid var(--border); background: white; transition: all 0.2s;
  text-decoration: none;
}
.nav-lang:hover { border-color: var(--blue-bright); color: var(--blue-bright); }
.nav-lang.active { color: var(--blue-bright); font-weight: 700; }

/* ===================== HERO ===================== */
.hero {
  min-height: 100vh; display: flex; align-items: center;
  background: linear-gradient(160deg, #f0f9f4 0%, #ffffff 40%, #f8fafc 100%);
  padding-top: 80px; position: relative; overflow: hidden;
}
.hero::before {
  content: ''; position: absolute; top: -200px; right: -200px;
  width: 700px; height: 700px; border-radius: 50%;
  background: radial-gradient(circle, rgba(22,163,74,0.06) 0%, transparent 70%);
  pointer-events: none;
}
.hero::after {
  content: ''; position: absolute; bottom: -100px; left: -100px;
  width: 500px; height: 500px; border-radius: 50%;
  background: radial-gradient(circle, rgba(6,182,212,0.05) 0%, transparent 70%);
  pointer-events: none;
}
.hero-inner {
  display: grid; grid-template-columns: 1fr 1fr; gap: 80px;
  align-items: center; padding: 80px 0;
}
.hero-badge {
  display: inline-flex; align-items: center; gap: 8px;
  font-family: var(--font-display); font-size: 0.8rem; font-weight: 600;
  color: var(--blue-bright); margin-bottom: 24px;
  background: rgba(22,163,74,0.06); border: 1px solid rgba(22,163,74,0.12);
  padding: 8px 16px; border-radius: 100px; letter-spacing: 0.03em;
}
.hero-badge .dot { width: 6px; height: 6px; background: var(--green); border-radius: 50%; animation: pulse-dot 2s infinite; }
@keyframes pulse-dot {
  0%, 100% { opacity: 1; transform: scale(1); }
  50% { opacity: 0.5; transform: scale(0.8); }
}
.hero h1 { color: var(--text-primary); margin-bottom: 24px; }
.hero h1 em { font-style: normal; color: var(--blue-bright); }
.hero-sub {
  font-size: 1.15rem; color: var(--text-secondary); margin-bottom: 40px;
  line-height: 1.7; max-width: 520px;
}
.hero-actions { display: flex; align-items: center; gap: 16px; flex-wrap: wrap; margin-bottom: 48px; }
.hero-trust {
  display: flex; align-items: center; gap: 24px;
  border-top: 1px solid var(--border); padding-top: 32px;
}
.trust-item { display: flex; align-items: center; gap: 8px; }
.trust-icon { font-size: 1.1rem; }
.trust-text { font-size: 0.82rem; color: var(--text-secondary); font-weight: 500; }
.trust-text strong { color: var(--text-primary); font-weight: 700; }

/* HERO VISUAL */
.hero-visual { position: relative; display: flex; justify-content: center; }
.score-card {
  background: white; border-radius: var(--radius-xl); padding: 40px;
  box-shadow: var(--shadow-lg); border: 1px solid var(--border);
  min-width: 380px; position: relative; z-index: 2;
}
.score-card-header {
  display: flex; align-items: center; justify-content: space-between; margin-bottom: 32px;
}
.score-card-title { font-family: var(--font-display); font-size: 0.85rem; font-weight: 600; color: var(--text-secondary); }
.score-live { display: flex; align-items: center; gap: 6px; font-size: 0.78rem; font-weight: 600; color: var(--green); }
.score-live .live-dot { width: 6px; height: 6px; background: var(--green); border-radius: 50%; animation: pulse-dot 1.5s infinite; }

.ring-wrapper { display: flex; justify-content: center; margin-bottom: 28px; }
.score-ring { position: relative; width: 180px; height: 180px; }
.score-ring svg { transform: rotate(-90deg); }
.ring-bg { fill: none; stroke: var(--gray-100); stroke-width: 12; }
.ring-fill {
  fill: none; stroke: url(#ringGrad); stroke-width: 12;
  stroke-linecap: round; stroke-dasharray: 502;
  stroke-dashoffset: 502; animation: ring-anim 2s 0.5s cubic-bezier(0.4,0,0.2,1) forwards;
}
@keyframes ring-anim { to { stroke-dashoffset: 125; } }
.ring-center {
  position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%);
  text-align: center;
}
.ring-percent {
  font-family: var(--font-display); font-size: 2.6rem; font-weight: 800;
  color: var(--text-primary); line-height: 1; display: block;
  counter-reset: pct 0; animation: count-up 2s 0.5s ease-out forwards;
}
@keyframes count-up { from { opacity: 0; } to { opacity: 1; } }
.ring-label { font-size: 0.72rem; font-weight: 600; color: var(--text-muted); text-transform: uppercase; letter-spacing: 0.06em; margin-top: 4px; }

.score-insights { display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px; }
.insight-row { display: flex; align-items: center; gap: 10px; font-size: 0.83rem; padding: 8px 12px; background: var(--gray-50); border-radius: 8px; }
.insight-icon { font-size: 0.9rem; }
.insight-text { color: var(--text-secondary); flex: 1; }
.insight-badge { font-weight: 700; font-family: var(--font-display); font-size: 0.78rem; padding: 3px 8px; border-radius: 6px; }
.badge-green { background: rgba(16,185,129,0.1); color: var(--green); }
.badge-amber { background: rgba(245,158,11,0.1); color: var(--amber); }
.badge-red { background: rgba(239,68,68,0.1); color: var(--red); }

.score-cta { width: 100%; text-align: center; }

/* Floating cards */
.float-card {
  position: absolute; background: white; border-radius: var(--radius);
  box-shadow: var(--shadow-md); border: 1px solid var(--border);
  padding: 14px 18px; font-family: var(--font-display); font-size: 0.8rem; font-weight: 600;
  z-index: 3; animation: float 4s ease-in-out infinite;
}
@keyframes float { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-8px); } }
.float-card-1 { top: 20px; right: -60px; animation-delay: 0s; color: var(--green); }
.float-card-2 { bottom: 60px; left: -70px; animation-delay: 1.5s; color: var(--blue-bright); }
.float-card-icon { font-size: 1.1rem; margin-right: 6px; }

/* ===================== SCORING SECTION ===================== */
.scoring-section {
  padding: 120px 0;
  background: linear-gradient(180deg, var(--white) 0%, #f0f9f4 50%, var(--white) 100%);
}
.scoring-wrapper {
  display: grid; grid-template-columns: 1fr 1fr; gap: 80px; align-items: start;
}
.scoring-left h2 { margin-bottom: 20px; }
.scoring-left p { color: var(--text-secondary); font-size: 1.05rem; line-height: 1.7; margin-bottom: 40px; }

.questionnaire {
  background: white; border-radius: var(--radius-xl);
  border: 1.5px solid var(--border); box-shadow: var(--shadow-md);
  overflow: hidden;
}
.q-header {
  padding: 24px 28px; border-bottom: 1px solid var(--border);
  background: linear-gradient(135deg, #059669, #15803d);
  display: flex; align-items: center; justify-content: space-between;
}
.q-header-title { color: white; font-family: var(--font-display); font-weight: 700; font-size: 1rem; }
.q-progress-text { color: rgba(255,255,255,0.7); font-size: 0.82rem; font-weight: 500; }
.q-progress-bar { height: 3px; background: rgba(255,255,255,0.2); position: relative; }
.q-progress-fill { height: 100%; background: var(--cyan-light); width: 0%; transition: width 0.5s ease; border-radius: 2px; }

.q-body { padding: 32px 28px; }
.q-step { display: none; }
.q-step.active { display: block; }
.q-question { font-family: var(--font-display); font-size: 1.05rem; font-weight: 600; color: var(--text-primary); margin-bottom: 20px; line-height: 1.4; }
.q-options { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.q-option {
  padding: 14px 16px; border-radius: var(--radius-sm);
  border: 1.5px solid var(--border); cursor: pointer;
  font-family: var(--font-display); font-size: 0.88rem; font-weight: 500;
  color: var(--text-secondary); transition: all 0.2s; display: flex; align-items: center; gap: 8px;
  background: white;
}
.q-option:hover { border-color: var(--blue-bright); color: var(--blue-bright); background: rgba(22,163,74,0.04); }
.q-option.selected { border-color: var(--blue-bright); background: rgba(22,163,74,0.06); color: var(--blue-bright); }
.q-option .opt-icon { font-size: 1.1rem; }

.q-footer {
  padding: 20px 28px; border-top: 1px solid var(--border);
  display: flex; align-items: center; justify-content: space-between;
}
.q-nav { display: flex; gap: 10px; }
.q-btn-back { background: transparent; border: 1.5px solid var(--border); color: var(--text-secondary); }
.q-btn-next { background: var(--blue-bright); color: white; border: none; box-shadow: var(--shadow-blue); }

/* Score Result */
.score-result { display: none; padding: 32px 28px; text-align: center; }
.score-result.show { display: block; }
.result-ring { position: relative; width: 160px; height: 160px; margin: 0 auto 24px; }
.result-ring svg { transform: rotate(-90deg); }
.result-ring-center {
  position: absolute; top: 50%; left: 50%; transform: translate(-50%,-50%); text-align: center;
}
.result-percent { font-family: var(--font-display); font-size: 2.4rem; font-weight: 800; color: var(--text-primary); }
.result-label { font-size: 0.72rem; color: var(--text-muted); text-transform: uppercase; font-weight: 600; letter-spacing: 0.05em; }
.result-title { font-family: var(--font-display); font-size: 1.2rem; font-weight: 700; margin-bottom: 8px; }
.result-desc { color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 24px; }

.result-tags { display: flex; flex-wrap: wrap; justify-content: center; gap: 8px; margin-bottom: 24px; }
.rtag { font-size: 0.78rem; font-weight: 600; font-family: var(--font-display); padding: 5px 12px; border-radius: 100px; }
.rtag-green { background: rgba(16,185,129,0.1); color: var(--green); }
.rtag-amber { background: rgba(245,158,11,0.1); color: var(--amber); }

.phone-input-wrap { position: relative; margin-bottom: 16px; }
.phone-input {
  width: 100%; padding: 14px 16px 14px 50px;
  border: 1.5px solid var(--border); border-radius: var(--radius-sm);
  font-family: var(--font-display); font-size: 0.95rem; outline: none;
  transition: border-color 0.2s; background: var(--gray-50);
}
.phone-input:focus { border-color: var(--blue-bright); background: white; }
.phone-flag { position: absolute; left: 14px; top: 50%; transform: translateY(-50%); font-size: 1.2rem; }

/* ===================== DESTINATIONS ===================== */
.destinations-section { padding: 100px 0; background: var(--gray-50); }

.dest-tabs {
  display: flex; gap: 8px; margin-bottom: 48px; flex-wrap: wrap; justify-content: center;
}
.dest-tab {
  padding: 10px 22px; border-radius: 100px; font-family: var(--font-display);
  font-size: 0.85rem; font-weight: 600; cursor: pointer; border: 1.5px solid var(--border);
  background: white; color: var(--text-secondary); transition: all 0.2s;
}
.dest-tab.active { background: var(--blue-bright); color: white; border-color: var(--blue-bright); }
.dest-tab:hover:not(.active) { border-color: var(--blue-bright); color: var(--blue-bright); }

.dest-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; }

.dest-card {
  background: white; border-radius: var(--radius-lg); border: 1px solid var(--border);
  overflow: hidden; transition: all 0.3s cubic-bezier(0.4,0,0.2,1);
  box-shadow: var(--shadow-sm); cursor: pointer;
}
.dest-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: rgba(22,163,74,0.2); }
.dest-card-top {
  padding: 24px 24px 16px; display: flex; align-items: flex-start; justify-content: space-between;
}
.dest-flag { font-size: 2.8rem; line-height: 1; }
.dest-type-badge { font-family: var(--font-display); font-size: 0.7rem; font-weight: 700; padding: 4px 10px; border-radius: 100px; letter-spacing: 0.05em; }
.type-visa { background: rgba(239,68,68,0.08); color: var(--red); }
.type-free { background: rgba(16,185,129,0.08); color: var(--green); }
.type-easy { background: rgba(245,158,11,0.08); color: var(--amber); }
.type-popular { background: rgba(22,163,74,0.08); color: var(--blue-bright); }

.dest-card-body { padding: 0 24px 24px; }
.dest-country { font-family: var(--font-display); font-size: 1.3rem; font-weight: 700; margin-bottom: 4px; }
.dest-tagline { font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 16px; }

.dest-stats { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
.dest-stat { background: var(--gray-50); padding: 10px 12px; border-radius: var(--radius-sm); }
.dest-stat-label { font-size: 0.72rem; color: var(--text-muted); font-weight: 500; text-transform: uppercase; letter-spacing: 0.05em; }
.dest-stat-value { font-family: var(--font-display); font-size: 0.9rem; font-weight: 700; color: var(--text-primary); margin-top: 2px; }

.dest-score-bar { background: var(--gray-100); border-radius: 100px; height: 6px; margin-bottom: 6px; overflow: hidden; }
.dest-score-fill { height: 100%; border-radius: 100px; transition: width 1s ease; }
.fill-green { background: linear-gradient(90deg, var(--teal), var(--green)); }
.fill-amber { background: linear-gradient(90deg, var(--amber), #f97316); }
.fill-blue { background: linear-gradient(90deg, var(--blue-bright), var(--cyan)); }

.dest-score-text { display: flex; justify-content: space-between; font-size: 0.78rem; margin-bottom: 16px; }
.dest-score-label { color: var(--text-muted); font-weight: 500; }
.dest-score-val { font-family: var(--font-display); font-weight: 700; }
.val-green { color: var(--green); }
.val-amber { color: var(--amber); }
.val-blue { color: var(--blue-bright); }

.dest-actions { display: flex; gap: 8px; }
.dest-btn { flex: 1; text-align: center; }

/* ===================== HOW IT WORKS ===================== */
.how-section { padding: 100px 0; background: white; }
.steps-grid { display: grid; grid-template-columns: repeat(5, 1fr); gap: 0; position: relative; }
.steps-grid::before {
  content: ''; position: absolute; top: 36px; left: 10%; right: 10%;
  height: 2px; background: linear-gradient(90deg, var(--blue-bright), var(--cyan));
  opacity: 0.2; z-index: 0;
}
.step-item { text-align: center; padding: 0 12px; position: relative; z-index: 1; }
.step-num {
  width: 72px; height: 72px; border-radius: 50%;
  background: linear-gradient(135deg, var(--blue-bright), #15803d);
  color: white; font-family: var(--font-display); font-size: 1.4rem; font-weight: 800;
  display: flex; align-items: center; justify-content: center;
  margin: 0 auto 20px; box-shadow: var(--shadow-blue);
  position: relative;
}
.step-num::after {
  content: ''; position: absolute; inset: -4px; border-radius: 50%;
  border: 2px dashed rgba(22,163,74,0.2);
}
.step-icon { font-size: 1.4rem; }
.step-title { font-family: var(--font-display); font-size: 0.95rem; font-weight: 700; margin-bottom: 8px; color: var(--text-primary); }
.step-desc { font-size: 0.82rem; color: var(--text-secondary); line-height: 1.5; }

/* ===================== TRUST ===================== */
.trust-section { padding: 100px 0; background: var(--navy); position: relative; overflow: hidden; }
.trust-section::before {
  content: ''; position: absolute; inset: 0;
  background: radial-gradient(ellipse at 30% 50%, rgba(22,163,74,0.15) 0%, transparent 60%),
              radial-gradient(ellipse at 70% 50%, rgba(6,182,212,0.08) 0%, transparent 60%);
}
.trust-section .container { position: relative; z-index: 1; }
.trust-section .section-header h2 { color: white; }
.trust-section .section-header p { color: rgba(255,255,255,0.6); }

.stats-row {
  display: grid; grid-template-columns: repeat(4, 1fr); gap: 24px; margin-bottom: 80px;
}
.stat-card {
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
  border-radius: var(--radius-lg); padding: 32px 24px; text-align: center;
  backdrop-filter: blur(10px);
}
.stat-num { font-family: var(--font-display); font-size: 2.8rem; font-weight: 800; color: white; margin-bottom: 4px; }
.stat-num span { color: var(--cyan); }
.stat-desc { color: rgba(255,255,255,0.5); font-size: 0.85rem; font-weight: 500; }

.trust-pills { display: flex; flex-wrap: wrap; justify-content: center; gap: 12px; margin-bottom: 60px; }
.trust-pill {
  display: flex; align-items: center; gap: 8px;
  background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
  padding: 10px 20px; border-radius: 100px;
  font-size: 0.85rem; font-weight: 600; color: rgba(255,255,255,0.8);
  font-family: var(--font-display);
}
.trust-pill .pill-icon { font-size: 1rem; }

/* Reviews */
.reviews-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
.review-card {
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
  border-radius: var(--radius-lg); padding: 28px; backdrop-filter: blur(10px);
}
.review-stars { display: flex; gap: 3px; margin-bottom: 14px; }
.star { color: #fbbf24; font-size: 0.9rem; }
.review-text { color: rgba(255,255,255,0.75); font-size: 0.9rem; line-height: 1.65; margin-bottom: 20px; font-style: italic; }
.review-author { display: flex; align-items: center; gap: 12px; }
.review-avatar { width: 40px; height: 40px; border-radius: 50%; background: linear-gradient(135deg, var(--blue-bright), var(--cyan)); display: flex; align-items: center; justify-content: center; font-family: var(--font-display); font-weight: 700; color: white; font-size: 0.9rem; }
.review-name { font-family: var(--font-display); font-weight: 700; color: white; font-size: 0.9rem; }
.review-meta { font-size: 0.78rem; color: rgba(255,255,255,0.4); }

/* ===================== COMPARE SECTION ===================== */
.compare-section { padding: 100px 0; background: var(--gray-50); }
.compare-table {
  background: white; border-radius: var(--radius-xl); overflow: hidden;
  box-shadow: var(--shadow-lg); border: 1px solid var(--border);
}
.compare-header {
  display: grid; grid-template-columns: 2fr 1fr 1fr;
  background: var(--navy); color: white; padding: 20px 32px; gap: 24px;
}
.compare-col { font-family: var(--font-display); font-size: 0.85rem; font-weight: 700; letter-spacing: 0.04em; text-transform: uppercase; }
.compare-col.highlight { color: var(--cyan); }
.compare-row {
  display: grid; grid-template-columns: 2fr 1fr 1fr;
  padding: 16px 32px; gap: 24px; border-bottom: 1px solid var(--border);
  align-items: center; transition: background 0.2s;
}
.compare-row:hover { background: var(--gray-50); }
.compare-row:last-child { border-bottom: none; }
.compare-feature { font-size: 0.9rem; color: var(--text-primary); font-weight: 500; display: flex; align-items: center; gap: 10px; }
.feature-icon { font-size: 1rem; }
.compare-no { color: var(--red); font-size: 1.1rem; }
.compare-yes { color: var(--green); font-size: 1.1rem; }
.compare-yes-text { font-family: var(--font-display); font-size: 0.82rem; font-weight: 700; color: var(--blue-bright); }

/* ===================== AGENCIES SECTION ===================== */
.agencies-section { padding: 100px 0; background: white; }
.agencies-header { text-align: center; margin-bottom: 64px; }
.agencies-header h2 { margin-bottom: 16px; }
.agencies-header p { font-size: 1.15rem; color: var(--text-secondary); max-width: 600px; margin: 0 auto; }
.agencies-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(320px, 1fr)); gap: 24px; margin-bottom: 48px; }
.agency-card {
  background: white; border-radius: var(--radius); border: 1px solid var(--border);
  padding: 28px; transition: all 0.35s cubic-bezier(0.4,0,0.2,1);
  box-shadow: var(--shadow-sm); position: relative; overflow: hidden;
}
.agency-card::before {
  content: ''; position: absolute; top: 0; left: 0; right: 0; height: 4px;
  background: linear-gradient(90deg, var(--blue-bright), var(--cyan));
  opacity: 0; transition: opacity 0.3s;
}
.agency-card:hover { transform: translateY(-4px); box-shadow: var(--shadow-lg); border-color: rgba(22,163,74,0.2); }
.agency-card:hover::before { opacity: 1; }
.agency-card-header { display: flex; align-items: center; gap: 16px; margin-bottom: 16px; }
.agency-avatar {
  width: 52px; height: 52px; border-radius: 14px;
  background: linear-gradient(135deg, var(--blue-bright), #15803d);
  display: flex; align-items: center; justify-content: center;
  color: white; font-family: var(--font-display); font-weight: 800; font-size: 1.1rem;
  flex-shrink: 0;
}
.agency-name { font-family: var(--font-display); font-weight: 700; font-size: 1.05rem; color: var(--text-primary); }
.agency-city { font-size: 0.82rem; color: var(--text-secondary); margin-top: 2px; }
.agency-stats { display: flex; gap: 16px; margin-bottom: 16px; flex-wrap: wrap; }
.agency-stat {
  display: flex; align-items: center; gap: 6px;
  font-size: 0.8rem; color: var(--text-secondary);
  background: var(--gray-50); padding: 5px 10px; border-radius: 100px;
}
.agency-stat-val { font-family: var(--font-display); font-weight: 700; color: var(--text-primary); }
.agency-countries { display: flex; gap: 6px; flex-wrap: wrap; margin-bottom: 16px; }
.agency-country-flag { font-size: 1.2rem; width: 32px; height: 32px; background: var(--gray-50); border-radius: 8px; display: flex; align-items: center; justify-content: center; }
.agency-rating { display: flex; align-items: center; gap: 8px; }
.agency-rating-stars { color: var(--amber); font-size: 0.85rem; letter-spacing: 1px; }
.agency-rating-num { font-family: var(--font-display); font-weight: 700; font-size: 0.88rem; color: var(--text-primary); }
.agency-rating-count { font-size: 0.78rem; color: var(--text-muted); }
.agency-cta { margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--border); display: flex; gap: 10px; }
.agencies-bottom { text-align: center; }
.agencies-bottom p { font-size: 0.95rem; color: var(--text-secondary); margin-bottom: 20px; }

/* ===================== MOBILE APP BLOCK ===================== */
.app-block { padding: 100px 0; background: white; }
.app-block-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 80px; align-items: center;
}
.app-block-text h2 { margin-top: 16px; }
.app-block-text p {
  color: var(--text-secondary); font-size: 1.05rem; line-height: 1.7;
  margin: 16px 0 32px;
}
.app-features { display: flex; flex-direction: column; gap: 20px; margin-bottom: 36px; }
.app-feat { display: flex; align-items: flex-start; gap: 14px; }
.app-feat-icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.1rem; flex-shrink: 0;
}
.app-feat-title { font-family: var(--font-display); font-weight: 700; margin-bottom: 3px; font-size: 0.95rem; }
.app-feat-desc { font-size: 0.88rem; color: var(--text-secondary); line-height: 1.5; }

/* Store badges */
.store-badges { display: flex; gap: 12px; margin-top: 24px; }
.store-badge {
  display: inline-flex; align-items: center; gap: 8px;
  background: var(--navy); color: white; padding: 10px 18px; border-radius: 12px;
  text-decoration: none; transition: transform 0.2s, box-shadow 0.2s;
}
.store-badge:hover { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(0,0,0,0.2); }
.store-badge-icon { font-size: 1.3rem; line-height: 1; }
.store-badge-text { display: flex; flex-direction: column; }
.store-badge-small { font-size: 0.58rem; opacity: 0.7; font-family: var(--font-display); }
.store-badge-name { font-family: var(--font-display); font-weight: 700; font-size: 0.85rem; line-height: 1.2; }

/* SVG iPhone mockup with HTML content inside */
.phone-mockup-wrap {
  display: flex; justify-content: center; align-items: center;
  position: relative;
}
.svg-phone {
  position: relative; width: 300px; margin: 0 auto;
  filter: drop-shadow(0 25px 50px rgba(0,0,0,0.18)) drop-shadow(0 10px 20px rgba(0,0,0,0.1));
}
.svg-phone-frame {
  position: absolute; inset: 0; z-index: 2; pointer-events: none;
}
.svg-phone-content {
  position: relative; z-index: 1;
  margin: 14px 14px;
  border-radius: 36px;
  overflow: hidden;
  background: #000;
}
.svg-phone-inner {
  position: relative;
  padding-top: 0;
}
.svg-phone-content::before {
  content: ''; position: absolute; top: 0; left: 50%; transform: translateX(-50%);
  width: 35%; height: 30px; background: #000; border-radius: 0 0 18px 18px;
  z-index: 15;
}
.svg-phone-content::after {
  content: ''; position: absolute; bottom: 8px; left: 50%; transform: translateX(-50%);
  width: 36%; height: 5px; background: rgba(0,0,0,0.2); border-radius: 5px;
  z-index: 15;
}

/* App screen content */
.app-screen { background: var(--gray-50); padding: 48px 18px 24px; min-height: 540px; }
.phone-header-bar { display: flex; align-items: center; justify-content: space-between; margin-bottom: 14px; }
.phone-logo { font-family: var(--font-display); font-weight: 800; font-size: 0.9rem; color: var(--navy); }
.phone-logo span { color: var(--blue-bright); }
.phone-notif { background: var(--blue-bright); color: white; padding: 10px 14px; border-radius: 12px; font-size: 0.75rem; font-weight: 600; font-family: var(--font-display); margin-bottom: 16px; display: flex; align-items: center; gap: 8px; }
.phone-card { background: white; border-radius: 14px; padding: 14px; margin-bottom: 10px; border: 1px solid var(--border); box-shadow: var(--shadow-sm); }
.phone-card-top { display: flex; align-items: center; justify-content: space-between; margin-bottom: 6px; }
.phone-card-label { font-family: var(--font-display); font-size: 0.7rem; font-weight: 700; color: var(--text-secondary); text-transform: uppercase; letter-spacing: 0.05em; }
.phone-card-status { font-size: 0.68rem; font-weight: 700; font-family: var(--font-display); padding: 3px 8px; border-radius: 100px; }
.status-review { background: rgba(245,158,11,0.1); color: var(--amber); }
.status-ready { background: rgba(16,185,129,0.1); color: var(--green); }
.phone-card-country { font-family: var(--font-display); font-size: 0.88rem; font-weight: 700; color: var(--text-primary); display: flex; align-items: center; gap: 8px; }
.phone-progress { background: var(--gray-100); border-radius: 100px; height: 5px; margin-top: 8px; overflow: hidden; }
.phone-progress-fill { height: 100%; background: linear-gradient(90deg, var(--blue-bright), var(--cyan)); border-radius: 100px; }

/* ===================== TELEGRAM BLOCK ===================== */
.tg-block { padding: 100px 0; background: var(--gray-50); }
.tg-block-grid {
  display: grid; grid-template-columns: 1fr 1fr; gap: 80px; align-items: center;
}
.tg-block-text h2 { margin-top: 16px; }
.tg-block-text p {
  color: var(--text-secondary); font-size: 1.05rem; line-height: 1.7;
  margin: 16px 0 32px;
}
.tg-features { display: flex; flex-direction: column; gap: 20px; }
.tg-feat { display: flex; align-items: flex-start; gap: 14px; }
.tg-feat-icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  color: white; font-size: 1.1rem; flex-shrink: 0;
}
.tg-feat-title { font-family: var(--font-display); font-weight: 700; margin-bottom: 3px; font-size: 0.95rem; }
.tg-feat-desc { font-size: 0.88rem; color: var(--text-secondary); line-height: 1.5; }
.tg-btn {
  display: inline-flex; align-items: center; gap: 10px;
  background: linear-gradient(135deg, #047857, #059669);
  color: white;
  padding: 14px 28px; border-radius: 100px; text-decoration: none;
  font-family: var(--font-display); font-weight: 600; font-size: 0.95rem;
  transition: all 0.25s; margin-top: 32px;
  box-shadow: 0 8px 24px rgba(5,150,105,0.18);
  position: relative; overflow: hidden;
  animation: pulse-glow 7s ease-in-out infinite;
}
.tg-btn::after {
  content: '';
  position: absolute; top: -150%; left: -150%;
  width: 400%; height: 400%;
  background:
    radial-gradient(ellipse 35% 45% at 45% 48%, rgba(255,255,255,0.30) 0%, transparent 65%),
    radial-gradient(ellipse 38% 32% at 62% 42%, rgba(255,255,255,0.22) 0%, transparent 60%),
    radial-gradient(ellipse 28% 38% at 38% 58%, rgba(255,255,255,0.18) 0%, transparent 65%);
  pointer-events: none;
  animation: shimmer-drift 7s ease-in-out infinite;
}
.tg-btn:hover { transform: translateY(-2px); box-shadow: 0 12px 32px rgba(5,150,105,0.35); }

/* Telegram screen */
.tg-screen { background: #e7ebee; min-height: 540px; display: flex; flex-direction: column; }
.tg-header {
  background: linear-gradient(135deg, #059669, #047857); padding: 48px 16px 14px;
  display: flex; align-items: center; gap: 10px; color: white;
}
.tg-avatar {
  width: 36px; height: 36px; border-radius: 50%; background: rgba(255,255,255,0.2);
  display: flex; align-items: center; justify-content: center; font-size: 0.9rem;
  font-weight: 700; flex-shrink: 0;
}
.tg-header-info { flex: 1; }
.tg-header-name { font-family: var(--font-display); font-weight: 700; font-size: 0.85rem; }
.tg-header-status { font-size: 0.68rem; opacity: 0.75; }
.tg-messages { padding: 12px; display: flex; flex-direction: column; gap: 8px; flex: 1; }
.tg-msg {
  max-width: 88%; padding: 10px 12px; border-radius: 12px;
  font-size: 0.75rem; line-height: 1.5; position: relative;
}
.tg-msg.bot {
  background: white; color: var(--text-primary); align-self: flex-start;
  border-bottom-left-radius: 4px; box-shadow: 0 1px 2px rgba(0,0,0,0.06);
}
.tg-msg.user {
  background: #d4f0dd; color: var(--text-primary); align-self: flex-end;
  border-bottom-right-radius: 4px;
}
.tg-msg-time { font-size: 0.58rem; color: var(--text-muted); margin-top: 3px; text-align: right; }
.tg-msg strong { font-weight: 700; }
.tg-msg-icon { margin-right: 4px; }
.tg-input-bar {
  padding: 10px 12px; background: #f0f0f0; display: flex; align-items: center; gap: 8px;
  border-top: 1px solid #ddd;
}
.tg-input-bar input {
  flex: 1; border: none; background: white; border-radius: 18px; padding: 8px 14px;
  font-size: 0.75rem; outline: none; font-family: var(--font-body);
}
.tg-send-btn {
  width: 30px; height: 30px; border-radius: 50%; background: #059669;
  border: none; color: white; font-size: 0.8rem; cursor: pointer;
  display: flex; align-items: center; justify-content: center;
}

/* ===================== CABINET SECTION ===================== */
.cabinet-section { padding: 100px 0; background: linear-gradient(135deg, #071a10 0%, #0c1f14 100%); }
.cabinet-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 80px; align-items: center; }
.cabinet-section h2 { color: white; }
.cabinet-section p { color: rgba(255,255,255,0.6); }
.cabinet-features { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; margin-top: 40px; }
.cab-feature {
  background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1);
  border-radius: var(--radius); padding: 20px; transition: all 0.3s;
}
.cab-feature:hover { background: rgba(255,255,255,0.08); border-color: rgba(22,163,74,0.3); }
.cab-feature-icon { font-size: 1.4rem; margin-bottom: 10px; }
.cab-feature-title { font-family: var(--font-display); font-size: 0.88rem; font-weight: 700; color: white; margin-bottom: 4px; }
.cab-feature-desc { font-size: 0.8rem; color: rgba(255,255,255,0.45); line-height: 1.5; }

.dashboard-preview {
  background: var(--navy-light); border-radius: var(--radius-xl);
  border: 1px solid rgba(255,255,255,0.08); overflow: hidden;
  box-shadow: 0 40px 80px rgba(0,0,0,0.4);
}
.dash-topbar {
  padding: 16px 24px; border-bottom: 1px solid rgba(255,255,255,0.06);
  display: flex; align-items: center; gap: 8px;
}
.dot-row { display: flex; gap: 6px; }
.dot-btn { width: 12px; height: 12px; border-radius: 50%; }
.dot-red { background: #ef4444; } .dot-amber { background: #f59e0b; } .dot-green { background: #10b981; }
.dash-content { padding: 24px; }
.dash-welcome { font-family: var(--font-display); font-weight: 700; font-size: 1rem; color: white; margin-bottom: 16px; }
.dash-score-row { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 16px; }
.dash-mini-card { background: rgba(255,255,255,0.06); border-radius: var(--radius-sm); padding: 16px; border: 1px solid rgba(255,255,255,0.07); }
.dash-mini-label { font-size: 0.72rem; color: rgba(255,255,255,0.4); font-weight: 600; text-transform: uppercase; letter-spacing: 0.04em; margin-bottom: 6px; }
.dash-mini-val { font-family: var(--font-display); font-size: 1.6rem; font-weight: 800; }
.val-cyan { color: var(--cyan); } .val-white { color: white; }
.dash-dest-list { display: flex; flex-direction: column; gap: 8px; }
.dash-dest-item { display: flex; align-items: center; gap: 12px; background: rgba(255,255,255,0.04); border-radius: var(--radius-sm); padding: 12px 14px; border: 1px solid rgba(255,255,255,0.06); }
.dash-dest-flag { font-size: 1.2rem; }
.dash-dest-name { font-family: var(--font-display); font-size: 0.88rem; font-weight: 600; color: white; flex: 1; }
.dash-dest-pct { font-family: var(--font-display); font-size: 0.8rem; font-weight: 700; }

/* ===================== FAQ ===================== */
.faq-section { padding: 80px 0; background: var(--gray-50); }
.faq-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; max-width: 900px; margin: 0 auto; }
.faq-item {
  background: white; border-radius: var(--radius); border: 1px solid var(--border);
  overflow: hidden; transition: all 0.3s; box-shadow: var(--shadow-sm);
}
.faq-q {
  padding: 20px 24px; font-family: var(--font-display); font-weight: 600; font-size: 0.9rem;
  color: var(--text-primary); cursor: pointer; display: flex; justify-content: space-between;
  align-items: center; gap: 12px; transition: color 0.2s;
}
.faq-q:hover { color: var(--blue-bright); }
.faq-arrow { font-size: 0.8rem; transition: transform 0.3s; color: var(--text-muted); }
.faq-item.open .faq-arrow { transform: rotate(180deg); color: var(--blue-bright); }
.faq-a { display: none; padding: 0 24px 20px; font-size: 0.85rem; color: var(--text-secondary); line-height: 1.65; }
.faq-item.open .faq-a { display: block; }

/* ===================== CTA BANNER ===================== */
.cta-banner { padding: 100px 0; background: linear-gradient(135deg, var(--blue-bright), #15803d, #059669); position: relative; overflow: hidden; }
.cta-banner::before {
  content: ''; position: absolute; inset: 0;
  background: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.03'%3E%3Cpath d='M36 34v-4h-2v4h-4v2h4v4h2v-4h4v-2h-4zm0-30V0h-2v4h-4v2h4v4h2V6h4V4h-4zM6 34v-4H4v4H0v2h4v4h2v-4h4v-2H6zM6 4V0H4v4H0v2h4v4h2V6h4V4H6z'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
}
.cta-inner { text-align: center; position: relative; z-index: 1; }
.cta-banner h2 { color: white; font-size: clamp(1.8rem, 4vw, 3rem); margin-bottom: 16px; }
.cta-banner p { color: rgba(255,255,255,0.75); font-size: 1.1rem; margin-bottom: 40px; max-width: 500px; margin-left: auto; margin-right: auto; }
.cta-actions { display: flex; justify-content: center; gap: 16px; flex-wrap: wrap; }
.btn-white {
  background: white; color: var(--blue-bright); font-weight: 700;
  position: relative; overflow: hidden;
}
.btn-white::after {
  content: '';
  position: absolute; top: -150%; left: -150%;
  width: 400%; height: 400%;
  background:
    radial-gradient(ellipse 36% 46% at 46% 44%, rgba(22,163,74,0.20) 0%, transparent 65%),
    radial-gradient(ellipse 30% 38% at 64% 56%, rgba(22,163,74,0.16) 0%, transparent 60%),
    radial-gradient(ellipse 32% 32% at 40% 36%, rgba(22,163,74,0.12) 0%, transparent 65%);
  pointer-events: none;
  animation: shimmer-drift 8s ease-in-out infinite;
}
.btn-white:hover { transform: translateY(-2px) scale(1.02); box-shadow: 0 12px 40px rgba(0,0,0,0.15); }
.btn-outline-white { background: transparent; color: white; border: 2px solid rgba(255,255,255,0.4); }
.btn-outline-white:hover { background: rgba(255,255,255,0.1); border-color: white; }

/* ===================== FOOTER ===================== */
footer { background: var(--navy); padding: 80px 0 40px; }
.footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 1fr; gap: 40px; margin-bottom: 60px; }
.footer-brand p { color: rgba(255,255,255,0.5); font-size: 0.85rem; line-height: 1.7; margin-top: 16px; max-width: 280px; }
.footer-social { display: flex; gap: 12px; margin-top: 24px; }
.social-btn {
  width: 36px; height: 36px; border-radius: 8px;
  background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.1);
  display: flex; align-items: center; justify-content: center;
  text-decoration: none; font-size: 0.9rem; transition: all 0.2s;
  color: rgba(255,255,255,0.5);
}
.social-btn:hover { background: var(--blue-bright); border-color: var(--blue-bright); color: white; }
.footer-col h4 { font-family: var(--font-display); font-size: 0.82rem; font-weight: 700; color: white; text-transform: uppercase; letter-spacing: 0.08em; margin-bottom: 20px; }
.footer-links { display: flex; flex-direction: column; gap: 10px; }
.footer-link { color: rgba(255,255,255,0.45); font-size: 0.85rem; text-decoration: none; transition: color 0.2s; }
.footer-link:hover { color: white; }
.footer-bottom { border-top: 1px solid rgba(255,255,255,0.08); padding-top: 32px; display: flex; align-items: center; justify-content: space-between; }
.footer-bottom-text { color: rgba(255,255,255,0.3); font-size: 0.8rem; }
.footer-bottom-links { display: flex; gap: 24px; }

/* LOGO in footer */
.footer-logo { font-family: var(--font-display); font-weight: 800; font-size: 1.5rem; color: white; text-decoration: none; letter-spacing: -0.03em; }
.footer-logo span { color: var(--cyan); }

/* ===================== MAINTENANCE MODE ===================== */
.maintenance-overlay {
  display: none; position: fixed; inset: 0; z-index: 9998;
  background: var(--navy);
  align-items: center; justify-content: center; flex-direction: column;
  text-align: center; padding: 24px;
}
.maintenance-overlay.show { display: flex; }
.maintenance-logo { font-family: var(--font-display); font-size: 2rem; font-weight: 800; color: white; margin-bottom: 12px; }
.maintenance-logo span { color: var(--cyan); }
.maintenance-title { font-family: var(--font-display); font-size: 1.6rem; font-weight: 700; color: white; margin-bottom: 12px; }
.maintenance-desc { color: rgba(255,255,255,0.5); font-size: 1rem; margin-bottom: 40px; max-width: 400px; }
.maintenance-login { background: rgba(255,255,255,0.06); border: 1px solid rgba(255,255,255,0.12); border-radius: var(--radius-xl); padding: 40px; max-width: 360px; width: 100%; }
.maintenance-login h3 { font-family: var(--font-display); font-weight: 700; color: white; margin-bottom: 24px; }

.site-status-banner {
  display: none; position: fixed; top: 68px; left: 0; right: 0; z-index: 999;
  background: linear-gradient(135deg, var(--amber), #f97316);
  color: white; text-align: center; padding: 12px;
  font-family: var(--font-display); font-weight: 600; font-size: 0.88rem;
  box-shadow: 0 4px 12px rgba(245,158,11,0.3);
}
.site-status-banner.show { display: block; }

/* ===================== RESPONSIVE ===================== */
@media (max-width: 1024px) {
  .hero-inner { grid-template-columns: 1fr; gap: 40px; }
  .hero-visual { display: none; }
  .scoring-wrapper { grid-template-columns: 1fr; }
  .steps-grid { grid-template-columns: 1fr 1fr; gap: 32px; }
  .steps-grid::before { display: none; }
  .stats-row { grid-template-columns: 1fr 1fr; }
  .reviews-grid { grid-template-columns: 1fr 1fr; }
  .app-block-grid { grid-template-columns: 1fr; gap: 40px; }
  .tg-block-grid { grid-template-columns: 1fr; gap: 40px; }
  .cabinet-grid { grid-template-columns: 1fr; }
  .footer-grid { grid-template-columns: 1fr 1fr; }
  .compare-section { overflow-x: auto; }
}
@media (max-width: 768px) {
  .navbar-inner { padding: 0 20px; }
  .nav-links { display: none; }
  h1 { font-size: 2rem; }
  h2 { font-size: 1.6rem; }
  .section { padding: 70px 0; }
  .hero-inner { padding: 40px 0; }
  .dest-grid { grid-template-columns: 1fr; }
  .steps-grid { grid-template-columns: 1fr; }
  .stats-row { grid-template-columns: 1fr 1fr; }
  .reviews-grid { grid-template-columns: 1fr; }
  .faq-grid { grid-template-columns: 1fr; }
  .footer-grid { grid-template-columns: 1fr; }
  .q-options { grid-template-columns: 1fr; }
  .compare-header, .compare-row { grid-template-columns: 2fr 1fr 1fr; font-size: 0.8rem; padding: 12px 16px; }
  .css-phone { width: 240px; }
  .store-badges { flex-direction: column; }
}

/* Section BG alternating */
.bg-white { background: white; }
.bg-gray { background: var(--gray-50); }
.bg-navy { background: var(--navy); }
</style>
@yield('structured_data')
</head>
<body>

<!-- MAINTENANCE OVERLAY -->
<div class="maintenance-overlay" id="maintenanceOverlay">
  <div class="maintenance-logo">visa<span>bor</span>.uz</div>
  <div class="maintenance-title">Скоро открываемся</div>
  <div class="maintenance-desc">Платформа временно недоступна. Войдите в личный кабинет, чтобы продолжить.</div>
  <div class="maintenance-login">
    <h3>Вход в платформу</h3>
    <div class="phone-input-wrap" style="margin-bottom: 16px;">
      <span class="phone-flag">🇺🇿</span>
      <input type="tel" class="phone-input" placeholder="+998 __ ___ __ __" style="background: rgba(255,255,255,0.06); border-color: rgba(255,255,255,0.15); color: white;">
    </div>
    <a href="javascript:void(0)" onclick="openAuthModal()" class="btn btn-primary btn-lg" style="width:100%;text-decoration:none;">Войти по номеру</a>
    <p style="margin-top:12px; font-size:0.8rem; color:rgba(255,255,255,0.35);">Поддержка: support@visabor.uz</p>
  </div>
</div>

<!-- SITE STATUS BANNER -->
<div class="site-status-banner" id="statusBanner">
  Сайт временно работает в ограниченном режиме. Некоторые функции недоступны.
</div>

<!-- NAVBAR -->
<nav class="navbar" id="navbar">
  <div class="navbar-inner">
    <a href="/" class="logo">visa<span>bor</span><div class="logo-dot"></div></a>
    <div class="nav-links">
      <a href="#scoring" class="nav-link">Проверить шансы</a>
      <a href="#destinations" class="nav-link">Направления</a>
      <a href="#agencies" class="nav-link">Агентства</a>
      <a href="#how" class="nav-link">Как работает</a>
      <a href="#faq" class="nav-link">FAQ</a>
    </div>
    <div class="nav-actions">
      <a href="/locale/ru" class="nav-lang {{ ($locale ?? 'ru') === 'ru' ? 'active' : '' }}">RU</a>
      <a href="/locale/uz" class="nav-lang {{ ($locale ?? 'ru') === 'uz' ? 'active' : '' }}">UZ</a>
      <a href="javascript:void(0)" onclick="openAuthModal()" class="btn btn-secondary btn-sm">Войти</a>
      <a href="#scoring" class="btn btn-primary btn-sm">Проверить шансы</a>
    </div>
  </div>
</nav>

@yield('content')

<!-- FOOTER -->
<footer>
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand fade-up">
        <a href="/" class="footer-logo">visa<span>bor</span>.uz</a>
        <p>AI-платформа для граждан Узбекистана. Умный выбор направления и проверка шансов до подачи визы.</p>
        <div class="footer-social">
          <a class="social-btn" href="#">T</a>
          <a class="social-btn" href="#">W</a>
          <a class="social-btn" href="https://t.me/visaborbot">TG</a>
          <a class="social-btn" href="#">IN</a>
        </div>
      </div>
      <div class="footer-col fade-up">
        <h4>Направления</h4>
        <div class="footer-links">
          <a href="/country/DE" class="footer-link">🇩🇪 Германия</a>
          <a href="/country/IT" class="footer-link">🇮🇹 Италия</a>
          <a href="/country/FR" class="footer-link">🇫🇷 Франция</a>
          <a href="/country/JP" class="footer-link">🇯🇵 Япония</a>
          <a href="/country/KR" class="footer-link">🇰🇷 Южная Корея</a>
          <a href="/country/US" class="footer-link">🇺🇸 США</a>
          <a href="/country/GB" class="footer-link">🇬🇧 Великобритания</a>
          <a href="/country/CA" class="footer-link">🇨🇦 Канада</a>
        </div>
      </div>
      <div class="footer-col fade-up">
        <h4>Клиентам</h4>
        <div class="footer-links">
          <a href="/#how" class="footer-link">Как это работает</a>
          <a href="/#scoring" class="footer-link">Проверить шансы</a>
          <a href="/#destinations" class="footer-link">Сравнить направления</a>
          <a href="/#faq" class="footer-link">FAQ</a>
          <a href="/blog" class="footer-link">Блог</a>
          <a href="/#trust" class="footer-link">Отзывы</a>
          <a href="javascript:void(0)" onclick="openAuthModal()" class="footer-link">Личный кабинет</a>
        </div>
      </div>
      <div class="footer-col fade-up">
        <h4>Доверие</h4>
        <div class="footer-links">
          <a href="#" class="footer-link">AI-оценка шансов</a>
          <a href="#" class="footer-link">Прозрачный путь</a>
          <a href="#" class="footer-link">Проверенные партнёры</a>
          <a href="/privacy" class="footer-link">Политика конфиденциальности</a>
          <a href="/terms" class="footer-link">Пользовательское соглашение</a>
        </div>
      </div>
      <div class="footer-col fade-up">
        <h4>Контакты</h4>
        <div class="footer-links">
          <a href="#" class="footer-link">+998 71 200-00-00</a>
          <a href="#" class="footer-link">info@visabor.uz</a>
          <a href="https://t.me/visaborbot" class="footer-link">Telegram</a>
          <a href="#" class="footer-link">WhatsApp</a>
          <a href="#" class="footer-link">Ташкент, Узбекистан</a>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <span class="footer-bottom-text">&copy; {{ date('Y') }} visabor.uz -- AI Visa Platform. Все права защищены.</span>
      <div class="footer-bottom-links">
        <a href="/privacy" class="footer-link" style="font-size:0.78rem;">Конфиденциальность</a>
        <a href="/terms" class="footer-link" style="font-size:0.78rem;">Соглашение</a>
        <a href="/sitemap.xml" class="footer-link" style="font-size:0.78rem;">Sitemap</a>
      </div>
    </div>
  </div>
</footer>

<script>
// ===== SCROLL ANIMATIONS =====
const io = new IntersectionObserver((entries) => {
  entries.forEach(e => { if (e.isIntersecting) e.target.classList.add('visible'); });
}, { threshold: 0.12 });
document.querySelectorAll('.fade-up').forEach(el => io.observe(el));

// ===== NAVBAR SCROLL =====
window.addEventListener('scroll', () => {
  document.getElementById('navbar').style.boxShadow = window.scrollY > 50 ? '0 4px 20px rgba(0,0,0,0.08)' : '';
});

// ===== SHIMMER: уникальная скорость и фаза для ::after каждой кнопки =====
(function() {
  const btns = document.querySelectorAll('.btn-primary, .tg-btn, .btn-white');
  const style = document.createElement('style');
  let css = '';
  btns.forEach((btn, i) => {
    const cls = 'shimmer-' + i;
    btn.classList.add(cls);
    const dur = 5 + ((i * 2.3) % 5);
    const delay = (i * 1.7) % 4;
    const glowDur = 5 + ((i * 1.9) % 4);
    const glowDelay = (i * 1.4) % 4;
    css += `.${cls}::after { animation: shimmer-drift ${dur.toFixed(1)}s ease-in-out -${delay.toFixed(1)}s infinite !important; }\n`;
    css += `.${cls} { animation: pulse-glow ${glowDur.toFixed(1)}s ease-in-out -${glowDelay.toFixed(1)}s infinite !important; }\n`;
  });
  style.textContent = css;
  document.head.appendChild(style);
})();
</script>

<!-- ===== AUTH MODAL ===== -->
<div id="authModal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.5); backdrop-filter:blur(4px); justify-content:center; align-items:center;" onclick="if(event.target===this)closeAuthModal()">
  <div style="background:white; border-radius:20px; width:100%; max-width:400px; margin:16px; overflow:hidden; box-shadow:0 25px 50px rgba(0,0,0,0.2);">
    <!-- Header -->
    <div style="padding:28px 28px 16px;">
      <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:20px;">
        <span style="font-family:var(--font-display); font-weight:800; font-size:1.2rem; color:var(--navy);">visa<span style="color:var(--green);">bor</span></span>
        <button onclick="closeAuthModal()" style="width:36px; height:36px; border:none; background:var(--gray-50); border-radius:50%; cursor:pointer; font-size:1.1rem; color:var(--text-muted); display:flex; align-items:center; justify-content:center;">&#10005;</button>
      </div>
      <h2 id="authTitle" style="font-size:1.5rem; font-weight:700; color:var(--navy); margin-bottom:4px;">Вход в систему</h2>
      <p id="authSubtitle" style="color:var(--text-muted); font-size:0.9rem;">Введите номер телефона</p>
    </div>
    <!-- Body -->
    <div style="padding:0 28px 28px;">
      <!-- Step: Phone -->
      <div id="stepPhone">
        <div id="phoneWrap" style="display:flex; border:2px solid var(--border); border-radius:12px; overflow:hidden; transition:border-color 0.2s;">
          <span style="padding:14px 16px; font-weight:600; color:var(--navy); background:var(--gray-50); border-right:1px solid var(--border); font-size:1rem;">+998</span>
          <input id="phoneInput" type="tel" inputmode="numeric" placeholder="97 123 45 67" maxlength="12"
            style="flex:1; padding:14px 16px; border:none; outline:none; font-size:1rem; font-weight:500; color:var(--navy); letter-spacing:0.05em;"
            onfocus="document.getElementById('phoneWrap').style.borderColor='var(--green)'"
            onblur="document.getElementById('phoneWrap').style.borderColor='var(--border)'"
            oninput="formatPhoneInput(this)" onkeydown="phoneKeydown(event)" onkeyup="if(event.key==='Enter')sendOtp()">
        </div>
        <p style="margin-top:6px; font-size:0.78rem; color:var(--text-muted);">Пример: +998 97 123 45 67</p>
        <p id="phoneError" style="margin-top:4px; font-size:0.85rem; color:var(--red); display:none;"></p>
        <button id="btnSendOtp" onclick="sendOtp()" style="margin-top:16px; width:100%; padding:14px; background:var(--navy); color:white; border:none; border-radius:12px; font-size:1rem; font-weight:600; cursor:pointer; transition:background 0.2s;" onmouseover="this.style.background='#0d2a5e'" onmouseout="this.style.background='var(--navy)'">
          Получить код
        </button>
        <p style="margin-top:14px; text-align:center; font-size:0.85rem; color:var(--text-muted);">
          Есть PIN?
          <a href="javascript:void(0)" onclick="showStep('loginPin')" style="color:var(--navy); font-weight:600; text-decoration:none;">Войти по PIN</a>
        </p>
      </div>

      <!-- Step: OTP -->
      <div id="stepOtp" style="display:none;">
        <p style="margin-bottom:16px; font-size:0.9rem; color:var(--text-secondary); text-align:center;">Код отправлен на <strong id="otpPhone"></strong></p>
        <div style="display:flex; gap:10px; justify-content:center;">
          <input class="otp-box" type="tel" inputmode="numeric" maxlength="1" oninput="otpInput(this,0)" onkeydown="otpKeydown(event,0)" style="width:60px; height:68px; text-align:center; font-size:1.6rem; font-weight:700; border:2px solid var(--border); border-radius:12px; outline:none; color:var(--navy); transition:border-color 0.2s;" onfocus="this.style.borderColor='var(--green)'" onblur="this.style.borderColor=this.value?'var(--green)':'var(--border)'">
          <input class="otp-box" type="tel" inputmode="numeric" maxlength="1" oninput="otpInput(this,1)" onkeydown="otpKeydown(event,1)" style="width:60px; height:68px; text-align:center; font-size:1.6rem; font-weight:700; border:2px solid var(--border); border-radius:12px; outline:none; color:var(--navy); transition:border-color 0.2s;" onfocus="this.style.borderColor='var(--green)'" onblur="this.style.borderColor=this.value?'var(--green)':'var(--border)'">
          <input class="otp-box" type="tel" inputmode="numeric" maxlength="1" oninput="otpInput(this,2)" onkeydown="otpKeydown(event,2)" style="width:60px; height:68px; text-align:center; font-size:1.6rem; font-weight:700; border:2px solid var(--border); border-radius:12px; outline:none; color:var(--navy); transition:border-color 0.2s;" onfocus="this.style.borderColor='var(--green)'" onblur="this.style.borderColor=this.value?'var(--green)':'var(--border)'">
          <input class="otp-box" type="tel" inputmode="numeric" maxlength="1" oninput="otpInput(this,3)" onkeydown="otpKeydown(event,3)" style="width:60px; height:68px; text-align:center; font-size:1.6rem; font-weight:700; border:2px solid var(--border); border-radius:12px; outline:none; color:var(--navy); transition:border-color 0.2s;" onfocus="this.style.borderColor='var(--green)'" onblur="this.style.borderColor=this.value?'var(--green)':'var(--border)'">
        </div>
        <p id="otpError" style="margin-top:8px; font-size:0.85rem; color:var(--red); text-align:center; display:none;"></p>
        <button id="btnVerifyOtp" onclick="verifyOtp()" disabled style="margin-top:16px; width:100%; padding:14px; background:var(--navy); color:white; border:none; border-radius:12px; font-size:1rem; font-weight:600; cursor:pointer; opacity:0.4; transition:all 0.2s;">
          Подтвердить
        </button>
        <div style="margin-top:14px; display:flex; justify-content:space-between; font-size:0.85rem;">
          <a href="javascript:void(0)" onclick="showStep('phone')" style="color:var(--text-muted); text-decoration:none;">Изменить номер</a>
          <span id="resendWrap"><span style="color:var(--text-muted);">Повторить через <span id="resendTimer">60</span>с</span></span>
        </div>
      </div>

      <!-- Step: Set PIN -->
      <div id="stepPin" style="display:none;">
        <p style="margin-bottom:16px; font-size:0.9rem; color:var(--text-secondary);">Запомните 4-значный PIN для быстрого входа</p>
        <input id="pinInput" type="tel" inputmode="numeric" maxlength="4" placeholder="----"
          style="width:100%; padding:14px; border:2px solid var(--border); border-radius:12px; text-align:center; font-size:2rem; font-weight:700; letter-spacing:0.5em; color:var(--navy); outline:none; transition:border-color 0.2s;"
          oninput="this.value=this.value.replace(/\D/g,'').slice(0,4)" onfocus="this.style.borderColor='var(--green)'" onblur="this.style.borderColor='var(--border)'">
        <button onclick="setPin()" style="margin-top:16px; width:100%; padding:14px; background:var(--green); color:white; border:none; border-radius:12px; font-size:1rem; font-weight:600; cursor:pointer;">
          Сохранить PIN
        </button>
        <button onclick="authFinish()" style="margin-top:8px; width:100%; padding:12px; background:none; border:none; font-size:0.9rem; color:var(--text-muted); cursor:pointer;">
          Пропустить
        </button>
      </div>

      <!-- Step: Login by PIN -->
      <div id="stepLoginPin" style="display:none;">
        <div style="margin-bottom:12px;">
          <div id="loginPhoneWrap" style="display:flex; border:2px solid var(--border); border-radius:12px; overflow:hidden; transition:border-color 0.2s;">
            <span style="padding:14px 16px; font-weight:600; color:var(--navy); background:var(--gray-50); border-right:1px solid var(--border); font-size:1rem;">+998</span>
            <input id="loginPhoneInput" type="tel" inputmode="numeric" placeholder="97 123 45 67" maxlength="12"
              style="flex:1; padding:14px 16px; border:none; outline:none; font-size:1rem; font-weight:500; color:var(--navy); letter-spacing:0.05em;"
              onfocus="document.getElementById('loginPhoneWrap').style.borderColor='var(--green)'"
              onblur="document.getElementById('loginPhoneWrap').style.borderColor='var(--border)'"
              oninput="formatPhoneInput(this)" onkeydown="phoneKeydown(event)">
          </div>
        </div>
        <input id="loginPinInput" type="tel" inputmode="numeric" maxlength="4" placeholder="----"
          style="width:100%; padding:14px; border:2px solid var(--border); border-radius:12px; text-align:center; font-size:2rem; font-weight:700; letter-spacing:0.5em; color:var(--navy); outline:none; transition:border-color 0.2s;"
          oninput="this.value=this.value.replace(/\D/g,'').slice(0,4)" onfocus="this.style.borderColor='var(--green)'" onblur="this.style.borderColor='var(--border)'"
          onkeyup="if(event.key==='Enter')loginWithPin()">
        <p id="loginPinError" style="margin-top:4px; font-size:0.85rem; color:var(--red); display:none;"></p>
        <button onclick="loginWithPin()" style="margin-top:16px; width:100%; padding:14px; background:var(--navy); color:white; border:none; border-radius:12px; font-size:1rem; font-weight:600; cursor:pointer;">
          Войти
        </button>
        <p style="margin-top:14px; text-align:center;">
          <a href="javascript:void(0)" onclick="showStep('phone')" style="font-size:0.85rem; color:var(--text-muted); text-decoration:none;">Войти по SMS</a>
        </p>
      </div>
    </div>
  </div>
</div>

<script>
// ===== AUTH MODAL LOGIC =====
const API_BASE = '/api/v1/public';
let authToken = null;
let authUser = null;
let resendInterval = null;

function openAuthModal() {
  // Если уже авторизован — перейти в ЛК
  if (localStorage.getItem('public_token')) {
    window.location.href = '/me/cases';
    return;
  }
  const m = document.getElementById('authModal');
  m.style.display = 'flex';
  showStep('phone');
  document.body.style.overflow = 'hidden';
}

function closeAuthModal() {
  document.getElementById('authModal').style.display = 'none';
  document.body.style.overflow = '';
  clearInterval(resendInterval);
}

function showStep(step) {
  ['stepPhone','stepOtp','stepPin','stepLoginPin'].forEach(id => document.getElementById(id).style.display = 'none');
  const titles = { phone: ['Вход в систему','Введите номер телефона'], otp: ['Введите код','SMS отправлено'], pin: ['Установите PIN','Для быстрого входа в будущем'], loginPin: ['Вход по PIN','Введите телефон и PIN'] };
  const t = titles[step] || titles.phone;
  document.getElementById('authTitle').textContent = t[0];
  document.getElementById('authSubtitle').textContent = t[1];
  const map = { phone:'stepPhone', otp:'stepOtp', pin:'stepPin', loginPin:'stepLoginPin' };
  document.getElementById(map[step]).style.display = 'block';
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

// OTP box logic
function otpInput(el, i) {
  el.value = el.value.replace(/\D/g, '').slice(-1);
  if (el.value) el.style.borderColor = 'var(--green)';
  const boxes = document.querySelectorAll('.otp-box');
  if (el.value && i < 3) boxes[i+1].focus();
  const code = Array.from(boxes).map(b => b.value).join('');
  document.getElementById('btnVerifyOtp').disabled = code.length < 4;
  document.getElementById('btnVerifyOtp').style.opacity = code.length < 4 ? '0.4' : '1';
  if (code.length === 4) verifyOtp();
}

function otpKeydown(e, i) {
  if (e.key === 'Backspace' && !e.target.value && i > 0) {
    const boxes = document.querySelectorAll('.otp-box');
    boxes[i-1].value = ''; boxes[i-1].style.borderColor = 'var(--border)';
    boxes[i-1].focus();
  }
}

function clearOtpBoxes() {
  document.querySelectorAll('.otp-box').forEach(b => { b.value = ''; b.style.borderColor = 'var(--border)'; });
  document.getElementById('btnVerifyOtp').disabled = true;
  document.getElementById('btnVerifyOtp').style.opacity = '0.4';
}

function startResendTimer() {
  let sec = 60;
  const wrap = document.getElementById('resendWrap');
  const timer = document.getElementById('resendTimer');
  timer.textContent = sec;
  wrap.innerHTML = '<span style="color:var(--text-muted);">Повторить через <span id="resendTimer">' + sec + '</span>с</span>';
  clearInterval(resendInterval);
  resendInterval = setInterval(() => {
    sec--;
    const t = document.getElementById('resendTimer');
    if (t) t.textContent = sec;
    if (sec <= 0) {
      clearInterval(resendInterval);
      wrap.innerHTML = '<a href="javascript:void(0)" onclick="sendOtp()" style="color:var(--navy); font-weight:600; text-decoration:none;">Отправить снова</a>';
    }
  }, 1000);
}

// API calls
async function sendOtp() {
  const digits = getPhoneDigits('phoneInput');
  if (digits.length < 9) { showError('phoneError','Введите полный номер'); return; }
  hideError('phoneError');
  const phone = '+998' + digits;
  const btn = document.getElementById('btnSendOtp');
  btn.textContent = 'Отправка...'; btn.disabled = true;
  try {
    const r = await fetch(API_BASE + '/auth/send-otp', {
      method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'},
      body: JSON.stringify({ phone })
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.message || 'Ошибка');
    document.getElementById('otpPhone').textContent = phone;
    showStep('otp');
    clearOtpBoxes();
    startResendTimer();
  } catch(e) {
    showError('phoneError', e.message || 'Ошибка отправки SMS');
  } finally {
    btn.textContent = 'Получить код'; btn.disabled = false;
  }
}

async function verifyOtp() {
  const boxes = document.querySelectorAll('.otp-box');
  const code = Array.from(boxes).map(b => b.value).join('');
  if (code.length < 4) return;
  hideError('otpError');
  const phone = '+998' + getPhoneDigits('phoneInput');
  const btn = document.getElementById('btnVerifyOtp');
  btn.textContent = 'Проверка...'; btn.disabled = true;
  try {
    const r = await fetch(API_BASE + '/auth/verify-otp', {
      method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'},
      body: JSON.stringify({ phone, code })
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.message || 'Неверный код');
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
    showError('otpError', e.message || 'Неверный код');
    clearOtpBoxes();
    setTimeout(() => document.querySelectorAll('.otp-box')[0].focus(), 100);
  } finally {
    btn.textContent = 'Подтвердить'; btn.disabled = false;
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
  if (digits.length < 9) { showError('loginPinError','Введите полный номер'); return; }
  if (pin.length < 4) { showError('loginPinError','Введите 4-значный PIN'); return; }
  hideError('loginPinError');
  const phone = '+998' + digits;
  try {
    const r = await fetch(API_BASE + '/auth/login', {
      method:'POST', headers:{'Content-Type':'application/json','Accept':'application/json'},
      body: JSON.stringify({ phone, pin })
    });
    const d = await r.json();
    if (!r.ok) throw new Error(d.message || 'Неверный телефон или PIN');
    localStorage.setItem('public_token', d.data.token);
    localStorage.setItem('public_user', JSON.stringify(d.data.user));
    authFinish();
  } catch(e) {
    showError('loginPinError', e.message || 'Ошибка входа');
  }
}

function authFinish() {
  closeAuthModal();
  window.location.href = '/me/cases';
}
</script>

</body>
</html>
