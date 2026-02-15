<?php
/**
 * ============================================
 * InfinityFree — Home Page (Enterprise v2)
 * Polished Hero · Skeleton Loaders · Toast Ready
 * ============================================
 */

require_once __DIR__ . '/includes/init.php';

$pageTitle = __('home');

// Fetch live stats (with graceful fallback)
$pdo = getDatabaseConnection();
try {
    $statsRow = $pdo->query("
        SELECT
          (SELECT COUNT(*) FROM users    WHERE is_active = 1)  AS total_users,
          (SELECT COUNT(*) FROM lessons  WHERE is_active = 1)  AS total_lessons,
          (SELECT COUNT(*) FROM subjects WHERE is_active = 1)  AS total_subjects,
          (SELECT SUM(time_spent_seconds) FROM user_progress)  AS total_seconds
    ")->fetch();
} catch (PDOException $e) {
    $statsRow = ['total_users' => 0, 'total_lessons' => 0, 'total_subjects' => 0, 'total_seconds' => 0];
}

$totalHours = $statsRow['total_seconds'] ? round($statsRow['total_seconds'] / 3600) : 0;

// Student progress (if logged in)
$currentUser  = loadCurrentUser();
$isLoggedIn   = $currentUser !== null;
$userStats    = null;

if ($isLoggedIn) {
    try {
        $stmt = $pdo->prepare("
            SELECT
                COUNT(DISTINCT lesson_id)                                     AS lessons_started,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END)        AS lessons_completed,
                SUM(time_spent_seconds)                                       AS total_time,
                COALESCE((SELECT xp_points FROM users WHERE id = :uid), 0)   AS xp_points
            FROM user_progress WHERE user_id = :uid
        ");
        $stmt->execute(['uid' => $currentUser['id']]);
        $userStats = $stmt->fetch();
    } catch (PDOException $e) {
        $userStats = ['lessons_started' => 0, 'lessons_completed' => 0, 'total_time' => 0, 'xp_points' => 0];
    }
}

// Recent public levels for the "Explore" section
$levels = $pdo->query("
    SELECT l.id, l.name_" . getCurrentLanguage() . " as name,
           l.description_" . getCurrentLanguage() . " as description,
           COUNT(s.id) as subject_count
    FROM levels l
    LEFT JOIN subjects s ON s.level_id = l.id AND s.is_active = 1
    WHERE l.is_active = 1
    GROUP BY l.id
    ORDER BY l.order_position ASC
    LIMIT 3
")->fetchAll();

include_once __DIR__ . '/includes/_header.php';
?>

<?php /* ====================================================
   ADDITIONAL CSS
   ==================================================== */ ?>
<style>
/* ---- Google Font ---- */
@import url('https://fonts.googleapis.com/css2?family=Syne:wght@700;800&family=DM+Sans:wght@400;500;600&display=swap');

:root {
  --ink:      #0f172a;
  --blue:     #2563eb;
  --blue-mid: #1d4ed8;
  --blue-lt:  #3b82f6;
  --sky:      #eff6ff;
  --gold:     #f59e0b;
  --green:    #10b981;
  --radius:   12px;
  --shadow-xl: 0 20px 60px rgba(37,99,235,.14);
}

/* Syne for hero display text */
.font-display { font-family: 'Syne', sans-serif !important; }
body          { font-family: 'DM Sans', sans-serif; }

/* ============================================
   HERO
   ============================================ */
.hero-enterprise {
  position: relative;
  overflow: hidden;
  background: var(--ink);
  padding: 0;
}

/* Animated mesh background */
.hero-mesh {
  position: absolute; inset: 0;
  background:
    radial-gradient(ellipse 80% 60% at 20% 30%,  rgba(37,99,235,.55)  0%, transparent 65%),
    radial-gradient(ellipse 60% 50% at 80% 70%,  rgba(29,78,216,.45)  0%, transparent 65%),
    radial-gradient(ellipse 50% 40% at 60% 20%,  rgba(59,130,246,.30) 0%, transparent 60%);
  animation: meshFloat 12s ease-in-out infinite alternate;
}
@keyframes meshFloat {
  0%   { transform: scale(1)   rotate(0deg); }
  100% { transform: scale(1.06) rotate(1.5deg); }
}

/* Grid lines overlay */
.hero-grid {
  position: absolute; inset: 0;
  background-image:
    linear-gradient(rgba(255,255,255,.04) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.04) 1px, transparent 1px);
  background-size: 48px 48px;
}

.hero-inner {
  position: relative; z-index: 2;
  max-width: 1200px;
  margin: 0 auto;
  padding: 7rem 2rem 6rem;
  display: grid;
  grid-template-columns: 1fr 420px;
  gap: 4rem;
  align-items: center;
}

/* Eyebrow tag */
.hero-eyebrow {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  background: rgba(255,255,255,.1);
  border: 1px solid rgba(255,255,255,.2);
  border-radius: 999px;
  padding: .35rem 1rem;
  font-size: .8rem;
  font-weight: 600;
  letter-spacing: .05em;
  text-transform: uppercase;
  color: #93c5fd;
  backdrop-filter: blur(8px);
  margin-bottom: 1.5rem;
}
.hero-eyebrow-dot {
  width: 7px; height: 7px;
  border-radius: 50%;
  background: #34d399;
  animation: blink 2s ease-in-out infinite;
}
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:.3} }

.hero-headline {
  font-family: 'Syne', sans-serif;
  font-size: clamp(2.4rem, 5vw, 4rem);
  font-weight: 800;
  line-height: 1.08;
  color: #fff;
  margin: 0 0 1.25rem;
  letter-spacing: -.02em;
}
.hero-headline .accent {
  background: linear-gradient(135deg, #60a5fa 0%, #a78bfa 100%);
  -webkit-background-clip: text;
  -webkit-text-fill-color: transparent;
  background-clip: text;
}

.hero-sub {
  font-size: 1.125rem;
  color: rgba(255,255,255,.72);
  line-height: 1.7;
  max-width: 480px;
  margin: 0 0 2.5rem;
}

.hero-cta-group {
  display: flex;
  gap: 1rem;
  flex-wrap: wrap;
}
.btn-hero-primary {
  display: inline-flex; align-items: center; gap: .6rem;
  background: #fff;
  color: var(--blue);
  font-weight: 700;
  font-size: 1rem;
  padding: .875rem 2rem;
  border-radius: 10px;
  border: none;
  cursor: pointer;
  text-decoration: none;
  transition: transform .2s ease, box-shadow .2s ease;
  box-shadow: 0 4px 20px rgba(255,255,255,.25);
}
.btn-hero-primary:hover {
  transform: translateY(-2px);
  box-shadow: 0 8px 30px rgba(255,255,255,.35);
  color: var(--blue);
}
.btn-hero-secondary {
  display: inline-flex; align-items: center; gap: .6rem;
  background: rgba(255,255,255,.1);
  color: #fff;
  font-weight: 600;
  font-size: 1rem;
  padding: .875rem 2rem;
  border-radius: 10px;
  border: 1px solid rgba(255,255,255,.25);
  cursor: pointer;
  text-decoration: none;
  backdrop-filter: blur(6px);
  transition: background .2s ease, transform .2s ease;
}
.btn-hero-secondary:hover {
  background: rgba(255,255,255,.18);
  transform: translateY(-2px);
  color: #fff;
}

/* Trust strip */
.hero-trust {
  display: flex;
  align-items: center;
  gap: 2rem;
  margin-top: 2.5rem;
  flex-wrap: wrap;
}
.trust-item {
  display: flex;
  align-items: center;
  gap: .5rem;
  color: rgba(255,255,255,.6);
  font-size: .875rem;
}
.trust-item svg { color: #34d399; flex-shrink: 0; }

/* Hero card (right side) */
.hero-card {
  background: rgba(255,255,255,.07);
  border: 1px solid rgba(255,255,255,.15);
  border-radius: 20px;
  padding: 2rem;
  backdrop-filter: blur(16px);
  box-shadow: 0 24px 60px rgba(0,0,0,.4);
}
.hero-card-title {
  font-family: 'Syne', sans-serif;
  font-size: 1rem;
  font-weight: 700;
  color: rgba(255,255,255,.5);
  text-transform: uppercase;
  letter-spacing: .1em;
  margin-bottom: 1.25rem;
}
.hero-stat-row {
  display: flex;
  align-items: center;
  gap: 1rem;
  padding: .875rem 0;
  border-bottom: 1px solid rgba(255,255,255,.08);
}
.hero-stat-row:last-child { border-bottom: none; }
.hero-stat-icon {
  width: 42px; height: 42px; border-radius: 10px;
  display: flex; align-items: center; justify-content: center;
  font-size: 1.25rem;
  flex-shrink: 0;
}
.hero-stat-value {
  font-family: 'Syne', sans-serif;
  font-size: 1.5rem;
  font-weight: 800;
  color: #fff;
  line-height: 1;
}
.hero-stat-label {
  font-size: .8rem;
  color: rgba(255,255,255,.5);
  margin-top: .15rem;
}

/* ============================================
   STATS TICKER
   ============================================ */
.stats-ticker {
  background: var(--sky);
  border-top: 1px solid #dbeafe;
  border-bottom: 1px solid #dbeafe;
  padding: 1.75rem 0;
}
.stats-ticker-inner {
  max-width: 1200px;
  margin: 0 auto;
  padding: 0 2rem;
  display: grid;
  grid-template-columns: repeat(4, 1fr);
  gap: 1rem;
}
.ticker-item { text-align: center; }
.ticker-value {
  font-family: 'Syne', sans-serif;
  font-size: 2rem;
  font-weight: 800;
  color: var(--blue);
  display: block;
}
.ticker-label {
  font-size: .875rem;
  color: #64748b;
  margin-top: .2rem;
}

/* ============================================
   FEATURES GRID
   ============================================ */
.features-section {
  padding: 6rem 0;
  background: #fff;
}
.section-tag {
  display: inline-block;
  background: var(--sky);
  color: var(--blue);
  font-size: .8rem;
  font-weight: 700;
  letter-spacing: .08em;
  text-transform: uppercase;
  padding: .35rem .9rem;
  border-radius: 999px;
  margin-bottom: 1rem;
}
.section-title {
  font-family: 'Syne', sans-serif;
  font-size: clamp(1.75rem, 3vw, 2.5rem);
  font-weight: 800;
  color: var(--ink);
  margin: 0 0 1rem;
  letter-spacing: -.02em;
}
.section-sub {
  font-size: 1.0625rem;
  color: #64748b;
  max-width: 560px;
  line-height: 1.7;
  margin: 0 auto 3.5rem;
}
.features-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
  gap: 1.5rem;
}
.feat-card {
  background: var(--sky);
  border: 1px solid #dbeafe;
  border-radius: 16px;
  padding: 2rem;
  transition: transform .25s ease, box-shadow .25s ease;
}
.feat-card:hover {
  transform: translateY(-5px);
  box-shadow: var(--shadow-xl);
}
.feat-icon {
  width: 52px; height: 52px;
  border-radius: 14px;
  background: linear-gradient(135deg, var(--blue) 0%, var(--blue-lt) 100%);
  display: flex; align-items: center; justify-content: center;
  font-size: 1.5rem;
  margin-bottom: 1.25rem;
  box-shadow: 0 4px 14px rgba(37,99,235,.3);
}
.feat-card h4 {
  font-family: 'Syne', sans-serif;
  font-size: 1.125rem;
  font-weight: 700;
  color: var(--ink);
  margin: 0 0 .5rem;
}
.feat-card p {
  font-size: .9rem;
  color: #64748b;
  line-height: 1.65;
  margin: 0;
}

/* ============================================
   LEVELS SECTION
   ============================================ */
.levels-section {
  padding: 6rem 0;
  background: var(--ink);
  position: relative;
  overflow: hidden;
}
.levels-section::before {
  content: '';
  position: absolute; inset: 0;
  background:
    radial-gradient(ellipse 60% 80% at 90% 50%, rgba(37,99,235,.25) 0%, transparent 65%);
}
.levels-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
  gap: 1.5rem;
  margin-top: 3rem;
  position: relative; z-index: 1;
}
.level-card {
  background: rgba(255,255,255,.06);
  border: 1px solid rgba(255,255,255,.12);
  border-radius: 16px;
  padding: 2rem;
  backdrop-filter: blur(8px);
  transition: background .25s ease, transform .25s ease;
  text-decoration: none;
  display: block;
}
.level-card:hover {
  background: rgba(255,255,255,.12);
  transform: translateY(-4px);
  color: inherit;
}
.level-number {
  font-family: 'Syne', sans-serif;
  font-size: 3rem;
  font-weight: 800;
  color: rgba(255,255,255,.08);
  line-height: 1;
  margin-bottom: .5rem;
}
.level-name {
  font-family: 'Syne', sans-serif;
  font-size: 1.25rem;
  font-weight: 700;
  color: #fff;
  margin: 0 0 .5rem;
}
.level-desc {
  font-size: .875rem;
  color: rgba(255,255,255,.55);
  line-height: 1.6;
  margin: 0 0 1rem;
}
.level-chip {
  display: inline-flex;
  align-items: center;
  gap: .35rem;
  background: rgba(59,130,246,.2);
  border: 1px solid rgba(59,130,246,.35);
  color: #93c5fd;
  font-size: .8rem;
  font-weight: 600;
  padding: .3rem .75rem;
  border-radius: 999px;
}

/* Empty state for levels */
.empty-levels {
  text-align: center;
  padding: 4rem 0;
  position: relative; z-index: 1;
  color: rgba(255,255,255,.5);
}
.empty-levels h3 { color: rgba(255,255,255,.7); margin-bottom: .5rem; }

/* ============================================
   DASHBOARD STRIP (logged-in users)
   ============================================ */
.dash-strip {
  background: linear-gradient(135deg, #0f172a 0%, #1e293b 100%);
  padding: 3rem 0;
  border-bottom: 1px solid #1e293b;
}
.dash-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
  gap: 1rem;
}
.dash-stat {
  background: rgba(255,255,255,.05);
  border: 1px solid rgba(255,255,255,.1);
  border-radius: 12px;
  padding: 1.25rem 1.5rem;
}
.dash-stat-val {
  font-family: 'Syne', sans-serif;
  font-size: 1.75rem;
  font-weight: 800;
  color: #fff;
  margin-bottom: .2rem;
}
.dash-stat-lbl { font-size: .8rem; color: rgba(255,255,255,.45); }
.dash-action {
  display: flex;
  align-items: center;
  justify-content: flex-end;
}

/* ============================================
   TESTIMONIALS / CTA BANNER
   ============================================ */
.cta-banner {
  background: linear-gradient(135deg, var(--blue) 0%, #1d4ed8 100%);
  padding: 5rem 0;
  text-align: center;
  position: relative;
  overflow: hidden;
}
.cta-banner::before {
  content: '';
  position: absolute; inset: 0;
  background-image:
    radial-gradient(circle at 20% 80%, rgba(255,255,255,.08) 0%, transparent 40%),
    radial-gradient(circle at 80% 20%, rgba(255,255,255,.08) 0%, transparent 40%);
}
.cta-banner-inner { position: relative; z-index: 1; }
.cta-banner h2 {
  font-family: 'Syne', sans-serif;
  font-size: clamp(1.75rem, 3.5vw, 3rem);
  font-weight: 800;
  color: #fff;
  margin: 0 0 1rem;
  letter-spacing: -.02em;
}
.cta-banner p {
  font-size: 1.0625rem;
  color: rgba(255,255,255,.8);
  max-width: 520px;
  margin: 0 auto 2.5rem;
  line-height: 1.7;
}

/* ============================================
   LANGUAGE BADGES
   ============================================ */
.lang-badges {
  display: flex;
  gap: .75rem;
  justify-content: center;
  flex-wrap: wrap;
  margin-bottom: 2rem;
}
.lang-badge {
  display: inline-flex;
  align-items: center;
  gap: .5rem;
  background: rgba(255,255,255,.12);
  border: 1px solid rgba(255,255,255,.2);
  border-radius: 999px;
  padding: .45rem 1.1rem;
  font-size: .875rem;
  font-weight: 600;
  color: rgba(255,255,255,.9);
  backdrop-filter: blur(4px);
}

/* ============================================
   RESPONSIVE
   ============================================ */
@media (max-width: 900px) {
  .hero-inner {
    grid-template-columns: 1fr;
    padding: 5rem 1.5rem 4rem;
    text-align: center;
  }
  .hero-sub { margin-left: auto; margin-right: auto; }
  .hero-cta-group { justify-content: center; }
  .hero-trust { justify-content: center; }
  .hero-card { display: none; }
  .stats-ticker-inner { grid-template-columns: repeat(2, 1fr); gap: 1.5rem; }
}
@media (max-width: 600px) {
  .stats-ticker-inner { grid-template-columns: 1fr 1fr; }
  .hero-cta-group { flex-direction: column; align-items: center; }
  .btn-hero-primary, .btn-hero-secondary { width: 100%; justify-content: center; }
}
</style>

<?php /* ====================================================
   DASHBOARD STRIP — only for logged-in non-admin users
   ==================================================== */ ?>
<?php if ($isLoggedIn && $userStats): ?>
<div class="dash-strip">
  <div class="container">
    <div style="display:flex;align-items:center;justify-content:space-between;gap:1rem;flex-wrap:wrap;margin-bottom:1.5rem;">
      <div>
        <p style="font-size:.8rem;text-transform:uppercase;letter-spacing:.1em;color:rgba(255,255,255,.4);margin:0 0 .25rem;">Bienvenue</p>
        <h2 style="font-family:'Syne',sans-serif;font-size:1.5rem;color:#fff;margin:0;">
          <?= escape($currentUser['first_name']) ?> ✦
        </h2>
      </div>
      <a href="/dashboard" class="btn-hero-primary" style="color:var(--blue);">
        Tableau de bord →
      </a>
    </div>
    <div class="dash-grid">
      <div class="dash-stat">
        <div class="dash-stat-val" data-countup="<?= (int)$userStats['lessons_started'] ?>">0</div>
        <div class="dash-stat-lbl"><?= __('total_lessons') ?></div>
      </div>
      <div class="dash-stat">
        <div class="dash-stat-val" data-countup="<?= (int)$userStats['lessons_completed'] ?>">0</div>
        <div class="dash-stat-lbl"><?= __('lessons_completed') ?></div>
      </div>
      <div class="dash-stat">
        <div class="dash-stat-val" data-countup="<?= (int)$userStats['xp_points'] ?>" data-suffix=" XP">0</div>
        <div class="dash-stat-lbl">XP Gagnés</div>
      </div>
      <div class="dash-stat">
        <div class="dash-stat-val"><?= formatDuration((int)$userStats['total_time']) ?></div>
        <div class="dash-stat-lbl"><?= __('total_time_spent') ?></div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php /* ====================================================
   HERO
   ==================================================== */ ?>
<section class="hero-enterprise" aria-label="Hero">
  <div class="hero-mesh" aria-hidden="true"></div>
  <div class="hero-grid"  aria-hidden="true"></div>
  <div class="hero-inner">
    <div>
      <div class="hero-eyebrow">
        <span class="hero-eyebrow-dot"></span>
        <?= __('lang_fr') ?> · <?= __('lang_en') ?> · <?= __('lang_ar') ?>
      </div>
      <h1 class="hero-headline">
        <?= __('hero_title_part1', null) ?: 'Apprenez' ?>
        <span class="accent"><?= __('hero_title_part2', null) ?: ' sans limites' ?></span><br>
        avec InfinityFree
      </h1>
      <p class="hero-sub"><?= escape(__('hero_subtitle')) ?></p>

      <div class="hero-cta-group">
        <?php if (!$isLoggedIn): ?>
          <a href="/register" class="btn-hero-primary">
            <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" d="M13 9l3 3-3 3M6 12h10"/><circle cx="12" cy="12" r="10"/></svg>
            <?= __('get_started') ?>
          </a>
          <a href="/levels" class="btn-hero-secondary">
            <?= __('explore_courses') ?> →
          </a>
        <?php else: ?>
          <a href="/dashboard" class="btn-hero-primary">
            Tableau de bord →
          </a>
          <a href="/levels" class="btn-hero-secondary">
            Continuer l'apprentissage
          </a>
        <?php endif; ?>
      </div>

      <div class="hero-trust">
        <div class="trust-item">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
          100% Gratuit
        </div>
        <div class="trust-item">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
          3 langues
        </div>
        <div class="trust-item">
          <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" d="M5 13l4 4L19 7"/></svg>
          Certificats
        </div>
      </div>
    </div>

    <!-- Right card — live platform stats -->
    <div class="hero-card" aria-hidden="true">
      <div class="hero-card-title">Statistiques en direct</div>
      <div class="hero-stat-row">
        <div class="hero-stat-icon" style="background:rgba(16,185,129,.2);">👥</div>
        <div>
          <div class="hero-stat-value" data-countup="<?= (int)$statsRow['total_users'] ?>">—</div>
          <div class="hero-stat-label">Apprenants inscrits</div>
        </div>
      </div>
      <div class="hero-stat-row">
        <div class="hero-stat-icon" style="background:rgba(245,158,11,.2);">📚</div>
        <div>
          <div class="hero-stat-value" data-countup="<?= (int)$statsRow['total_lessons'] ?>">—</div>
          <div class="hero-stat-label">Leçons disponibles</div>
        </div>
      </div>
      <div class="hero-stat-row">
        <div class="hero-stat-icon" style="background:rgba(59,130,246,.2);">🌐</div>
        <div>
          <div class="hero-stat-value">3</div>
          <div class="hero-stat-label">Langues d'enseignement</div>
        </div>
      </div>
      <div class="hero-stat-row">
        <div class="hero-stat-icon" style="background:rgba(139,92,246,.2);">⏱️</div>
        <div>
          <div class="hero-stat-value" data-countup="<?= $totalHours ?>" data-suffix="h">—</div>
          <div class="hero-stat-label">Heures d'apprentissage</div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php /* ====================================================
   STATS TICKER
   ==================================================== */ ?>
<div class="stats-ticker">
  <div class="stats-ticker-inner">
    <div class="ticker-item">
      <span class="ticker-value" data-countup="<?= (int)$statsRow['total_users'] ?>" data-suffix="+">0+</span>
      <span class="ticker-label">Utilisateurs actifs</span>
    </div>
    <div class="ticker-item">
      <span class="ticker-value" data-countup="<?= (int)$statsRow['total_lessons'] ?>" data-suffix="+">0+</span>
      <span class="ticker-label">Leçons publiées</span>
    </div>
    <div class="ticker-item">
      <span class="ticker-value" data-countup="<?= (int)$statsRow['total_subjects'] ?>" data-suffix="+">0+</span>
      <span class="ticker-label">Matières couvertes</span>
    </div>
    <div class="ticker-item">
      <span class="ticker-value" data-countup="<?= $totalHours ?>" data-suffix="h">0h</span>
      <span class="ticker-label">Heures d'apprentissage</span>
    </div>
  </div>
</div>

<?php /* ====================================================
   FEATURES
   ==================================================== */ ?>
<section class="features-section">
  <div class="container" style="text-align:center;">
    <span class="section-tag"><?= __('why_choose_us') ?></span>
    <h2 class="section-title"><?= __('why_choose_us') ?></h2>
    <p class="section-sub"><?= escape(__('hero_subtitle')) ?></p>

    <div class="features-grid">
      <?php
      $features = [
        ['📚', 'feature_multilingual',  'feature_multilingual_desc',  '#2563eb'],
        ['🎯', 'feature_tracking',      'feature_tracking_desc',      '#10b981'],
        ['✅', 'feature_quizzes',       'feature_quizzes_desc',       '#f59e0b'],
        ['🔒', 'feature_security',      'feature_security_desc',      '#8b5cf6'],
      ];
      foreach ($features as [$icon, $titleKey, $descKey, $color]):
      ?>
        <div class="feat-card">
          <div class="feat-icon" style="background:linear-gradient(135deg,<?= $color ?> 0%,<?= $color ?>cc 100%);"><?= $icon ?></div>
          <h4><?= escape(__($titleKey)) ?></h4>
          <p><?= escape(__($descKey)) ?></p>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<?php /* ====================================================
   LEVELS / EXPLORE
   ==================================================== */ ?>
<section class="levels-section">
  <div class="container">
    <div style="position:relative;z-index:1;">
      <span class="section-tag" style="background:rgba(255,255,255,.1);color:#93c5fd;"><?= __('levels') ?></span>
      <h2 class="section-title font-display" style="color:#fff;">
        Explorez nos niveaux
      </h2>
      <p style="color:rgba(255,255,255,.55);font-size:1rem;max-width:500px;">
        Du primaire au lycée — un parcours structuré pour chaque apprenant.
      </p>
    </div>

    <?php if (!empty($levels)): ?>
      <div class="levels-grid">
        <?php foreach ($levels as $i => $level): ?>
          <a href="/level/<?= $level['id'] ?>" class="level-card">
            <div class="level-number">0<?= $i + 1 ?></div>
            <div class="level-name"><?= escape($level['name']) ?></div>
            <div class="level-desc"><?= escape(substr($level['description'] ?? 'Explorez ce niveau.', 0, 90)) ?>…</div>
            <span class="level-chip">
              📖 <?= (int)$level['subject_count'] ?> matières
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-levels">
        <div style="font-size:3rem;margin-bottom:1rem;">🎓</div>
        <h3>Niveaux bientôt disponibles</h3>
        <p>Les niveaux seront publiés par les administrateurs très prochainement.</p>
        <?php if (!$isLoggedIn): ?>
          <a href="/register" class="btn-hero-primary" style="display:inline-flex;margin-top:1.25rem;">
            S'inscrire maintenant
          </a>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <div style="text-align:center;margin-top:3rem;position:relative;z-index:1;">
      <a href="/levels" class="btn-hero-secondary">
        Voir tous les niveaux →
      </a>
    </div>
  </div>
</section>

<?php /* ====================================================
   CTA BANNER
   ==================================================== */ ?>
<section class="cta-banner">
  <div class="container">
    <div class="cta-banner-inner">
      <div class="lang-badges" aria-label="Supported languages">
        <span class="lang-badge">🇫🇷 Français</span>
        <span class="lang-badge">🇬🇧 English</span>
        <span class="lang-badge" dir="rtl">🇸🇦 العربية</span>
      </div>
      <h2>Prêt à commencer votre voyage d'apprentissage ?</h2>
      <p>Rejoignez des milliers d'apprenants qui font confiance à InfinityFree pour leur éducation.</p>
      <?php if (!$isLoggedIn): ?>
        <a href="/register" class="btn-hero-primary">
          Créer un compte gratuit →
        </a>
      <?php else: ?>
        <a href="/dashboard" class="btn-hero-primary">
          Voir mon tableau de bord →
        </a>
      <?php endif; ?>
    </div>
  </div>
</section>

<script>
// Flash any session messages as Toasts
document.addEventListener('DOMContentLoaded', function () {
  <?php if (isset($_SESSION['success'])): ?>
    InfinityFree.Toast.success(<?= json_encode($_SESSION['success']) ?>);
    <?php unset($_SESSION['success']); ?>
  <?php endif; ?>

  <?php if (isset($_SESSION['error'])): ?>
    InfinityFree.Toast.error(<?= json_encode($_SESSION['error']) ?>);
    <?php unset($_SESSION['error']); ?>
  <?php endif; ?>
});
</script>

<?php include_once __DIR__ . '/includes/_footer.php'; ?>
