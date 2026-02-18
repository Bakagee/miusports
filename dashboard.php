<?php
session_start();
require_once 'config/config.php';
require_once 'includes/db.php';

// Simple auth guard
if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit;
}

// Redirect admin away from player dashboard
if ($_SESSION['is_admin']) {
    header('Location: admin_dashboard.php');
    exit;
}

$studentId   = (int)$_SESSION['student_id'];
$studentName = $_SESSION['student_name'];

// Determine gender (refresh from DB so it reflects latest change)
$stmt = $pdo->prepare('SELECT gender FROM users WHERE id = :id LIMIT 1');
$stmt->execute([':id' => $studentId]);
$studentGender = $stmt->fetchColumn() ?: null;
$_SESSION['gender'] = $studentGender;

// ── Handle AJAX registration ──────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'register') {
    header('Content-Type: application/json');

    $teamId = (int)($_POST['team_id'] ?? 0);
    if (!$teamId) {
        echo json_encode(['success' => false, 'message' => 'Invalid team selection.']);
        exit;
    }

    try {
        // Fetch team details (note: team_name column)
        $stmt = $pdo->prepare('SELECT id, team_name AS name, sport, max_players FROM teams WHERE id = :tid');
        $stmt->execute([':tid' => $teamId]);
        $team = $stmt->fetch();

        if (!$team) {
            echo json_encode(['success' => false, 'message' => 'Team not found.']);
            exit;
        }

        $max_number = (int)$team['max_players'];
        var_dump($max_number); exit;

        // Check player hasn't already registered for this sport
        $stmt = $pdo->prepare('SELECT id FROM registrations WHERE user_id = :uid AND sport = :sp LIMIT 1');
        $stmt->execute([':uid' => $studentId, ':sp' => $team['sport']]);
        $existing = $stmt->fetch();

        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'You are already registered for a ' . ucfirst($team['sport']) . ' team.']);
            exit;
        }

        // Gender gate: females cannot register for football
        if ($studentGender === 'female' && $team['sport'] === 'football') {
            echo json_encode(['success' => false, 'message' => 'Female players register for Volleyball only.']);
            exit;
        }

        // Use transaction to check capacity and insert atomically
        $pdo->beginTransaction();

        $stmt = $pdo->prepare('SELECT COUNT(*) AS cnt FROM registrations WHERE team_id = :tid FOR UPDATE');
        $stmt->execute([':tid' => $teamId]);
        $count = (int)$stmt->fetchColumn();

        if ($count >= (int)$team['max_players']) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => 'Sorry, this team is now full. Please choose another team.']);
            exit;
        }

        $stmt = $pdo->prepare('INSERT INTO registrations (user_id, team_id, sport) VALUES (:uid, :tid, :sp)');
        $stmt->execute([':uid' => $studentId, ':tid' => $teamId, ':sp' => $team['sport']]);

        $pdo->commit();

        // Updated slot count for this team
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM registrations WHERE team_id = :tid');
        $stmt->execute([':tid' => $teamId]);
        $registered = (int)$stmt->fetchColumn();
        $remaining  = (int)$team['max_players'] - $registered;

        echo json_encode([
            'success'    => true,
            'message'    => 'You have been registered for <strong>' . htmlspecialchars($team['name']) . '</strong>!',
            'team_id'    => $teamId,
            'sport'      => $team['sport'],
            'team_name'  => $team['name'],
            'remaining'  => $remaining,
            'registered' => $registered,
            'max'        => (int)$team['max_players'],
        ]);

    } catch (Throwable $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        echo json_encode(['success' => false, 'message' => 'A system error occurred. Please try again.']);
    }
    exit;
}

// ── Load page data ────────────────────────────────────────────────────────────
// Player's existing registrations
$stmt = $pdo->prepare('
    SELECT r.sport, r.team_id, t.team_name AS team_name
    FROM registrations r
    JOIN teams t ON t.id = r.team_id
    WHERE r.user_id = :uid
');
$stmt->execute([':uid' => $studentId]);
$rows = $stmt->fetchAll();

$myRegistrations = []; // keyed by sport
foreach ($rows as $row) {
    $myRegistrations[$row['sport']] = $row;
}

// All teams with current registration counts
$sql = '
    SELECT t.id, t.team_name AS name, t.sport, t.max_players,
           COUNT(r.id) AS registered,
           (t.max_players - COUNT(r.id)) AS remaining
    FROM teams t
    LEFT JOIN registrations r ON r.team_id = t.id
    GROUP BY t.id, t.team_name, t.sport, t.max_players
    ORDER BY t.sport, t.id
';
$teams = $pdo->query($sql)->fetchAll();

$footballTeams   = array_values(array_filter($teams, fn($r) => $r['sport'] === 'football'));
$volleyballTeams = array_values(array_filter($teams, fn($r) => $r['sport'] === 'volleyball'));

// Helpers
$hasFootball   = isset($myRegistrations['football']);
$hasVolleyball = isset($myRegistrations['volleyball']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Dashboard — <?php echo SITE_NAME; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&family=Crimson+Pro:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
    /* ══════════════════════════════════════════════
       DESIGN TOKENS
    ══════════════════════════════════════════════ */
    :root {
        --miu-red:       #A31D24;
        --miu-red-dark:  #7B181E;
        --miu-red-deep:  #550F13;
        --miu-gold:      #FFCC33;
        --miu-gold-dim:  #D4A800;
        --miu-white:     #FFFFFF;
        --miu-off:       #F8F5F0;
        --miu-charcoal:  #212121;
        --miu-gray:      #6B6B6B;
        --miu-gray-lt:   #E5E0D8;
        --bg-body:       #F4F1EC;
        --bg-card:       #FFFFFF;

        --font-display: 'Bebas Neue', sans-serif;
        --font-body:    'DM Sans', sans-serif;
        --font-serif:   'Crimson Pro', Georgia, serif;

        --radius:    10px;
        --radius-lg: 16px;
        --shadow-sm: 0 2px 12px rgba(0,0,0,0.07);
        --shadow-md: 0 6px 28px rgba(0,0,0,0.11);
        --shadow-lg: 0 16px 56px rgba(0,0,0,0.15);
    }

    *, *::before, *::after { box-sizing: border-box; }

    body {
        font-family: var(--font-body);
        background-color: var(--bg-body);
        color: var(--miu-charcoal);
        min-height: 100vh;
        overflow-x: hidden;
    }

    /* ══════════════════════════════════════════════
       TOPBAR / NAVBAR
    ══════════════════════════════════════════════ */
    .topbar {
        background: linear-gradient(135deg, var(--miu-red-deep) 0%, var(--miu-red) 100%);
        padding: 0 1.5rem;
        height: 66px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        position: sticky;
        top: 0;
        z-index: 100;
        box-shadow: 0 2px 20px rgba(85,15,19,0.4);
    }

    .topbar-left {
        display: flex;
        align-items: center;
        gap: 0.85rem;
    }

    .topbar-logo {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        border: 2px solid rgba(255,204,51,0.6);
        overflow: hidden;
        background: var(--miu-red-dark);
        display: flex;
        align-items: center;
        justify-content: center;
        flex-shrink: 0;
    }

    .topbar-logo img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .topbar-logo .logo-fallback {
        font-size: 1.1rem;
        color: var(--miu-gold);
    }

    .topbar-site-name {
        font-family: var(--font-display);
        font-size: 1.25rem;
        letter-spacing: 0.08em;
        color: var(--miu-white);
        line-height: 1;
    }

    .topbar-site-sub {
        font-size: 0.62rem;
        letter-spacing: 0.15em;
        text-transform: uppercase;
        color: rgba(255,204,51,0.8);
        margin-top: 1px;
    }

    .topbar-right {
        display: flex;
        align-items: center;
        gap: 0.75rem;
    }

    .topbar-player-info {
        text-align: right;
        display: none;
    }

    @media (min-width: 576px) { .topbar-player-info { display: block; } }

    .topbar-player-name {
        font-size: 0.82rem;
        font-weight: 600;
        color: var(--miu-white);
        line-height: 1.2;
    }

    .topbar-player-meta {
        font-size: 0.65rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: rgba(255,204,51,0.75);
    }

    .topbar-avatar {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: rgba(255,255,255,0.15);
        border: 2px solid rgba(255,204,51,0.4);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--miu-gold);
        font-size: 1rem;
        flex-shrink: 0;
    }

    .btn-logout {
        background: rgba(255,255,255,0.12);
        border: 1px solid rgba(255,255,255,0.25);
        color: var(--miu-white);
        font-size: 0.75rem;
        font-weight: 600;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        border-radius: 6px;
        padding: 0.42rem 0.85rem;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.4rem;
        transition: background 0.2s, border-color 0.2s;
        white-space: nowrap;
    }

    .btn-logout:hover {
        background: rgba(255,255,255,0.22);
        border-color: rgba(255,255,255,0.45);
        color: var(--miu-white);
    }

    /* ══════════════════════════════════════════════
       GOLD ACCENT BAR
    ══════════════════════════════════════════════ */
    .gold-bar {
        height: 4px;
        background: linear-gradient(90deg, var(--miu-red), var(--miu-gold) 50%, var(--miu-red));
    }

    /* ══════════════════════════════════════════════
       PAGE WRAPPER
    ══════════════════════════════════════════════ */
    .page-wrap {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem 1.25rem 4rem;
    }

    /* ══════════════════════════════════════════════
       WELCOME HEADER
    ══════════════════════════════════════════════ */
    .welcome-block {
        background: linear-gradient(135deg, var(--miu-red-deep) 0%, var(--miu-red-dark) 100%);
        border-radius: var(--radius-lg);
        padding: 2rem 2.2rem;
        margin-bottom: 1.8rem;
        position: relative;
        overflow: hidden;
        box-shadow: var(--shadow-md);
        animation: fadeUp 0.5s ease both;
    }

    .welcome-block::before {
        content: '';
        position: absolute;
        top: -60px; right: -60px;
        width: 220px; height: 220px;
        background: rgba(255,204,51,0.07);
        border-radius: 50%;
    }

    .welcome-block::after {
        content: '';
        position: absolute;
        bottom: -40px; left: 30%;
        width: 160px; height: 160px;
        background: rgba(255,255,255,0.03);
        border-radius: 50%;
    }

    .welcome-eyebrow {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.2em;
        text-transform: uppercase;
        color: rgba(255,204,51,0.8);
        margin-bottom: 0.4rem;
    }

    .welcome-name {
        font-family: var(--font-display);
        font-size: clamp(1.8rem, 5vw, 2.8rem);
        letter-spacing: 0.05em;
        color: var(--miu-white);
        line-height: 1;
        position: relative;
        z-index: 1;
    }

    .welcome-name span { color: var(--miu-gold); }

    .welcome-sub {
        font-family: var(--font-serif);
        font-size: 1rem;
        color: rgba(255,255,255,0.65);
        margin-top: 0.5rem;
        position: relative;
        z-index: 1;
    }

    
    /* ══════════════════════════════════════════════
       SECTION HEADERS
    ══════════════════════════════════════════════ */
    .section-header {
        display: flex;
        align-items: center;
        gap: 1rem;
        margin-bottom: 1.2rem;
        padding-bottom: 0.8rem;
        border-bottom: 2px solid var(--miu-gray-lt);
    }

    .section-sport-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.3rem;
        flex-shrink: 0;
    }

    .icon-football { background: rgba(163,29,36,0.1); }
    .icon-volleyball { background: rgba(212,168,0,0.12); }

    .section-title {
        font-family: var(--font-display);
        font-size: 1.6rem;
        letter-spacing: 0.06em;
        color: var(--miu-charcoal);
        line-height: 1;
    }

    .section-subtitle {
        font-size: 0.8rem;
        color: var(--miu-gray);
        margin-top: 1px;
    }

    .section-rule-badge {
        margin-left: auto;
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        padding: 0.3rem 0.85rem;
        border-radius: 100px;
        border: 1px solid rgba(163,29,36,0.3);
        color: var(--miu-red);
        background: rgba(163,29,36,0.06);
        white-space: nowrap;
    }

    .sport-section {
        margin-bottom: 3rem;
        animation: fadeUp 0.5s ease 0.15s both;
    }

    /* ══════════════════════════════════════════════
       TEAM GRID
    ══════════════════════════════════════════════ */
    .teams-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(260px, 1fr));
        gap: 1rem;
    }

    /* ══════════════════════════════════════════════
       TEAM CARD
    ══════════════════════════════════════════════ */
    .team-card {
        background: var(--bg-card);
        border-radius: var(--radius-lg);
        border: 2px solid var(--miu-gray-lt);
        padding: 0;
        overflow: hidden;
        box-shadow: var(--shadow-sm);
        transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.2s;
        position: relative;
        display: flex;
        flex-direction: column;
    }

    .team-card:hover:not(.is-full):not(.is-registered) {
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
        border-color: rgba(163,29,36,0.3);
    }

    /* Top colour strip per sport */
    .team-card-strip {
        height: 5px;
        background: linear-gradient(90deg, var(--miu-red), var(--miu-red-dark));
    }

    .team-card-strip.volleyball-strip {
        background: linear-gradient(90deg, var(--miu-gold-dim), var(--miu-gold));
    }

    /* Full team overlay tint */
    .team-card.is-full {
        opacity: 0.72;
    }

    .team-card.is-full .team-card-strip {
        background: #AAAAAA;
    }

    /* Registered state */
    .team-card.is-registered {
        border-color: rgba(163,29,36,0.4);
        background: linear-gradient(160deg, #fff 50%, #fff5f5 100%);
    }

    .team-card.is-registered .team-card-strip {
        background: linear-gradient(90deg, var(--miu-red), var(--miu-gold));
    }

    .team-card-body {
        padding: 1.3rem 1.4rem 1rem;
        flex: 1;
        display: flex;
        flex-direction: column;
    }

    /* Team number badge */
    .team-number {
        font-family: var(--font-display);
        font-size: 0.75rem;
        letter-spacing: 0.14em;
        color: var(--miu-gray);
        margin-bottom: 0.35rem;
    }

    .team-name {
        font-weight: 700;
        font-size: 0.97rem;
        color: var(--miu-charcoal);
        line-height: 1.35;
        margin-bottom: 1rem;
        flex: 1;
    }

    /* Capacity bar */
    .capacity-section {
        margin-bottom: 0.85rem;
    }

    .capacity-meta {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 0.4rem;
    }

    .capacity-label {
        font-size: 0.68rem;
        font-weight: 600;
        letter-spacing: 0.1em;
        text-transform: uppercase;
        color: var(--miu-gray);
    }

    .capacity-count {
        font-size: 0.8rem;
        font-weight: 700;
    }

    .capacity-count.has-space { color: var(--miu-red); }
    .capacity-count.is-full-text { color: #888; }

    .progress-bar-track {
        height: 6px;
        background: var(--miu-gray-lt);
        border-radius: 100px;
        overflow: hidden;
    }

    .progress-bar-fill {
        height: 100%;
        border-radius: 100px;
        background: linear-gradient(90deg, var(--miu-red-dark), var(--miu-red));
        transition: width 0.5s ease;
    }

    .progress-bar-fill.volleyball-fill {
        background: linear-gradient(90deg, var(--miu-gold-dim), var(--miu-gold));
    }

    .progress-bar-fill.full-fill {
        background: #BBBBBB;
    }

    /* Slots remaining pill */
    .slots-pill {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.06em;
        padding: 0.22rem 0.6rem;
        border-radius: 100px;
        margin-top: 0.45rem;
    }

    .slots-pill.has-space {
        background: rgba(163,29,36,0.08);
        color: var(--miu-red);
        border: 1px solid rgba(163,29,36,0.2);
    }

    .slots-pill.slots-low {
        background: rgba(212,100,0,0.1);
        color: #B34700;
        border: 1px solid rgba(212,100,0,0.25);
    }

    .slots-pill.slots-full {
        background: rgba(100,100,100,0.1);
        color: #777;
        border: 1px solid rgba(100,100,100,0.2);
    }

    /* Register button */
    .btn-team-register {
        width: 100%;
        padding: 0.72rem 1rem;
        border: none;
        border-radius: 8px;
        font-family: var(--font-body);
        font-weight: 700;
        font-size: 0.82rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        cursor: pointer;
        transition: background 0.2s, transform 0.15s, box-shadow 0.2s, opacity 0.2s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        margin-top: auto;
    }

    .btn-register-active {
        background: var(--miu-red);
        color: var(--miu-white);
        box-shadow: 0 3px 14px rgba(163,29,36,0.28);
    }

    .btn-register-active:hover {
        background: var(--miu-red-dark);
        transform: translateY(-1px);
        box-shadow: 0 5px 20px rgba(163,29,36,0.4);
    }

    .btn-register-active:active { transform: translateY(0); }

    .btn-register-full {
        background: #EEEEEE;
        color: #999999;
        cursor: not-allowed;
    }

    .btn-register-done {
        background: linear-gradient(135deg, var(--miu-red-deep), var(--miu-red-dark));
        color: var(--miu-gold);
        cursor: default;
    }

    .btn-register-sport-taken {
        background: #F0F0F0;
        color: #AAAAAA;
        cursor: not-allowed;
    }

    .btn-register-loading {
        opacity: 0.7;
        cursor: wait;
    }

    /* Registered checkmark badge on card */
    .registered-badge {
        position: absolute;
        top: 14px;
        right: 14px;
        width: 28px;
        height: 28px;
        background: var(--miu-gold);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--miu-charcoal);
        font-size: 0.85rem;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
    }

    /* ══════════════════════════════════════════════
       ALREADY-REGISTERED NOTICE BANNER
    ══════════════════════════════════════════════ */
    .sport-done-banner {
        background: linear-gradient(135deg, var(--miu-red-deep), var(--miu-red-dark));
        border-radius: var(--radius);
        padding: 1rem 1.4rem;
        display: flex;
        align-items: center;
        gap: 0.85rem;
        margin-bottom: 1.2rem;
        color: var(--miu-white);
    }

    .sport-done-banner i {
        font-size: 1.3rem;
        color: var(--miu-gold);
        flex-shrink: 0;
    }

    .sport-done-banner strong {
        font-size: 0.9rem;
        display: block;
        color: var(--miu-white);
    }

    .sport-done-banner span {
        font-size: 0.8rem;
        color: rgba(255,255,255,0.65);
    }

    /* ══════════════════════════════════════════════
       FEMALE-ONLY NOTICE
    ══════════════════════════════════════════════ */
    .female-notice {
        background: linear-gradient(135deg, #FFF8E1, #FFF3CD);
        border: 1px solid rgba(212,168,0,0.3);
        border-left: 4px solid var(--miu-gold-dim);
        border-radius: var(--radius);
        padding: 1rem 1.4rem;
        display: flex;
        align-items: flex-start;
        gap: 0.85rem;
        margin-bottom: 2.5rem;
        color: var(--miu-charcoal);
    }

    .female-notice i {
        font-size: 1.2rem;
        color: var(--miu-gold-dim);
        flex-shrink: 0;
        margin-top: 1px;
    }

    .female-notice strong {
        font-size: 0.88rem;
        display: block;
        color: var(--miu-charcoal);
        margin-bottom: 0.15rem;
    }

    .female-notice span {
        font-size: 0.8rem;
        color: var(--miu-gray);
    }

    /* ══════════════════════════════════════════════
       TOAST NOTIFICATIONS
    ══════════════════════════════════════════════ */
    .toast-stack {
        position: fixed;
        bottom: 1.5rem;
        right: 1.5rem;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 0.6rem;
        pointer-events: none;
    }

    .miu-toast {
        background: var(--miu-charcoal);
        color: var(--miu-white);
        border-radius: 10px;
        padding: 1rem 1.3rem;
        min-width: 280px;
        max-width: 360px;
        box-shadow: 0 8px 32px rgba(0,0,0,0.3);
        display: flex;
        align-items: flex-start;
        gap: 0.75rem;
        pointer-events: all;
        animation: toastIn 0.35s cubic-bezier(0.34,1.56,0.64,1) both;
        border-left: 4px solid var(--miu-gold);
    }

    .miu-toast.toast-error {
        border-left-color: var(--miu-red);
    }

    .miu-toast.toast-out {
        animation: toastOut 0.3s ease forwards;
    }

    @keyframes toastIn {
        from { opacity: 0; transform: translateY(20px) scale(0.95); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    @keyframes toastOut {
        to { opacity: 0; transform: translateY(10px) scale(0.95); }
    }

    .toast-icon {
        font-size: 1.2rem;
        flex-shrink: 0;
        margin-top: 1px;
    }

    .toast-success .toast-icon { color: var(--miu-gold); }
    .toast-error .toast-icon   { color: var(--miu-red); }

    .toast-title {
        font-weight: 700;
        font-size: 0.88rem;
        margin-bottom: 0.15rem;
    }

    .toast-body {
        font-size: 0.8rem;
        color: rgba(255,255,255,0.7);
        line-height: 1.5;
    }

    /* ══════════════════════════════════════════════
       CONFIRM MODAL
    ══════════════════════════════════════════════ */
    .confirm-modal .modal-content {
        border: none;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 24px 80px rgba(0,0,0,0.35);
    }

    .confirm-header {
        background: linear-gradient(135deg, var(--miu-red-deep), var(--miu-red));
        padding: 1.8rem 2rem 1.4rem;
        position: relative;
    }

    .confirm-header::after {
        content: '';
        position: absolute;
        bottom: 0; left: 0; right: 0;
        height: 3px;
        background: linear-gradient(90deg, transparent, var(--miu-gold), transparent);
    }

    .confirm-sport-emoji {
        font-size: 2rem;
        margin-bottom: 0.5rem;
        display: block;
    }

    .confirm-title {
        font-family: var(--font-display);
        font-size: 1.6rem;
        letter-spacing: 0.06em;
        color: var(--miu-white);
    }

    .confirm-subtitle {
        font-size: 0.82rem;
        color: rgba(255,255,255,0.6);
        margin-top: 0.3rem;
    }

    .confirm-body {
        padding: 1.8rem 2rem;
        background: var(--miu-white);
    }

    .confirm-team-box {
        background: var(--miu-off);
        border-radius: var(--radius);
        border: 1px solid var(--miu-gray-lt);
        padding: 1.1rem 1.3rem;
        margin-bottom: 1.2rem;
        display: flex;
        align-items: center;
        gap: 0.85rem;
    }

    .confirm-team-icon {
        font-size: 1.8rem;
        flex-shrink: 0;
    }

    .confirm-team-label {
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        color: var(--miu-gray);
        margin-bottom: 0.15rem;
    }

    .confirm-team-name {
        font-weight: 700;
        font-size: 1rem;
        color: var(--miu-charcoal);
    }

    .confirm-rule-note {
        font-size: 0.82rem;
        color: var(--miu-gray);
        background: #FFF8E1;
        border: 1px solid rgba(212,168,0,0.25);
        border-radius: 8px;
        padding: 0.75rem 1rem;
        display: flex;
        gap: 0.6rem;
        align-items: flex-start;
        line-height: 1.5;
    }

    .confirm-rule-note i {
        color: var(--miu-gold-dim);
        flex-shrink: 0;
        margin-top: 2px;
    }

    .confirm-footer {
        padding: 0 2rem 1.8rem;
        background: var(--miu-white);
        display: flex;
        gap: 0.8rem;
    }

    .btn-confirm-cancel {
        flex: 1;
        padding: 0.8rem;
        border: 2px solid var(--miu-gray-lt);
        background: transparent;
        border-radius: 8px;
        font-weight: 600;
        font-size: 0.85rem;
        color: var(--miu-gray);
        cursor: pointer;
        transition: border-color 0.2s, color 0.2s;
    }

    .btn-confirm-cancel:hover {
        border-color: var(--miu-charcoal);
        color: var(--miu-charcoal);
    }

    .btn-confirm-go {
        flex: 2;
        padding: 0.8rem;
        border: none;
        background: var(--miu-red);
        border-radius: 8px;
        font-weight: 700;
        font-size: 0.88rem;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: var(--miu-white);
        cursor: pointer;
        transition: background 0.2s, transform 0.15s;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 0.5rem;
        box-shadow: 0 3px 16px rgba(163,29,36,0.3);
    }

    .btn-confirm-go:hover {
        background: var(--miu-red-dark);
        transform: translateY(-1px);
    }

    .btn-confirm-go .spinner-sm {
        display: none;
        width: 14px;
        height: 14px;
        border: 2px solid rgba(255,255,255,0.4);
        border-top-color: white;
        border-radius: 50%;
        animation: spin 0.6s linear infinite;
    }

    @keyframes spin { to { transform: rotate(360deg); } }

    /* ══════════════════════════════════════════════
       FOOTER
    ══════════════════════════════════════════════ */
    .page-footer {
        background: var(--miu-charcoal);
        padding: 1.8rem 1.5rem;
        border-top: 3px solid var(--miu-red-deep);
        margin-top: 2rem;
    }

    .page-footer p {
        margin: 0;
        font-size: 0.75rem;
        color: rgba(255,255,255,0.3);
        text-align: center;
    }

    /* ══════════════════════════════════════════════
       ANIMATIONS
    ══════════════════════════════════════════════ */
    @keyframes fadeUp {
        from { opacity: 0; transform: translateY(16px); }
        to   { opacity: 1; transform: translateY(0); }
    }

    
    /* ══════════════════════════════════════════════
       RESPONSIVE
    ══════════════════════════════════════════════ */
    @media (max-width: 576px) {
        .topbar {
            padding: 0 0.9rem;
        }
        .topbar-site-name {
            font-size: 1rem;
        }
        .page-wrap {
            padding: 1.4rem 1rem 3rem;
        }
        .welcome-block {
            padding: 1.5rem 1.2rem;
        }
        .section-header {
            flex-wrap: wrap;
        }
        .section-rule-badge {
            margin-left: 0;
        }
    }
    @media (max-width: 480px) {
        .page-wrap { padding: 1.2rem 0.85rem 3rem; }
        .welcome-block { padding: 1.5rem 1.1rem; }
        .teams-grid { grid-template-columns: 1fr; }
        .confirm-footer { flex-direction: column; }
        .btn-confirm-cancel { order: 2; }
        .btn-confirm-go { order: 1; }
        .toast-stack { left: 1rem; right: 1rem; bottom: 1rem; }
        .miu-toast { min-width: unset; }
    }
    </style>
</head>
<body>
    

<!-- ══════════════════════════════════════════════════════════
     TOPBAR
══════════════════════════════════════════════════════════ -->
<header class="topbar">
    <div class="topbar-left">
        <div class="topbar-logo">
            <img src="miulogo.jpeg" alt="MIU"
                 onerror="this.style.display='none';this.parentNode.innerHTML='<i class=\'bi bi-building logo-fallback\'></i>'">
        </div>
        <div>
            <div class="topbar-site-name"><?php echo SITE_NAME; ?></div>
            <div class="topbar-site-sub">Player Registration Portal</div>
        </div>
    </div>

    <div class="topbar-right">
        <div class="topbar-player-info">
            <div class="topbar-player-name"><?php echo htmlspecialchars($studentName); ?></div>
            <div class="topbar-player-meta">
                ⚽ 🏐 Football &amp; Volleyball
            </div>
        </div>
        <div class="topbar-avatar">
            <i class="bi bi-person-fill"></i>
        </div>
        <a href="logout.php" class="btn-logout">
            <i class="bi bi-box-arrow-right"></i>
            <span>Logout</span>
        </a>
    </div>
</header>
<div class="gold-bar"></div>

<!-- ══════════════════════════════════════════════════════════
     PAGE BODY
══════════════════════════════════════════════════════════ -->
<main class="page-wrap">

    <!-- Welcome banner -->
    <div class="welcome-block">
        <div class="welcome-eyebrow">
            <i class="bi bi-person-badge-fill me-1"></i>
            Player Dashboard
        </div>
        <h1 class="welcome-name">
            Welcome, <span><?php echo htmlspecialchars(explode(' ', $studentName)[0]); ?></span>!
        </h1>
        <p class="welcome-sub">
            <?php if ($studentGender === 'female'): ?>
                You can register for <strong>one Volleyball team</strong>. Football is reserved for male players.
            <?php else: ?>
                Register for up to <strong>one Football team</strong> and <strong>one Volleyball team</strong> below. First come, first served.
            <?php endif; ?>
        </p>
    </div>

    
    <!-- Gender notice removed -->


    <!-- ════════════════════════════════
         FOOTBALL SECTION (male players only)
    ════════════════════════════════ -->
    <section class="sport-section" id="football-section">

        <div class="section-header">
            <div class="section-sport-icon icon-football">⚽</div>
            <div>
                <div class="section-title">FOOTBALL TEAMS</div>
                <div class="section-subtitle">
                    
                <?php include 'players_count.php'; ?>
                
                8 teams · 
                    Max 
                    <?php echo $max_football; ?> 
                    players each · One team per player</div>
            </div>
            <span class="section-rule-badge" style="color:var(--miu-gold-dim);border-color:rgba(212,168,0,0.35);background:rgba(212,168,0,0.07);">
                <i class="bi bi-gender-male me-1"></i>Male players only
            </span>
        </div>

        <?php if ($hasFootball): ?>
        <div class="sport-done-banner mb-3">
            <i class="bi bi-check-circle-fill"></i>
            <div>
                <strong>You're registered for Football!</strong>
                <span>Team: <?php echo htmlspecialchars($myRegistrations['football']['team_name']); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="teams-grid" id="football-grid">
            <?php foreach ($footballTeams as $i => $team):
                $isFull       = $team['remaining'] <= 0;
                $isMyTeam     = $hasFootball && $myRegistrations['football']['team_id'] == $team['id'];
                $sportTaken   = $hasFootball && !$isMyTeam;
                $fillPct      = round(($team['registered'] / $team['max_players']) * 100);
                $slotsLeft    = $team['remaining'];
                $slotClass    = $isFull ? 'slots-full' : ($slotsLeft <= 3 ? 'slots-low' : 'has-space');
                $cardClass    = $isFull ? 'is-full' : ($isMyTeam ? 'is-registered' : '');
            ?>
            <div class="team-card <?php echo $cardClass; ?>"
                 id="team-card-<?php echo $team['id']; ?>"
                 data-team-id="<?php echo $team['id']; ?>"
                 data-sport="football"
                 data-max="<?php echo $team['max_players']; ?>">

                <div class="team-card-strip"></div>

                <?php if ($isMyTeam): ?>
                <div class="registered-badge" title="Your team">
                    <i class="bi bi-check2"></i>
                </div>
                <?php endif; ?>

                <div class="team-card-body">
                    <div class="team-number">TEAM <?php echo str_pad($i + 1, 2, '0', STR_PAD_LEFT); ?></div>
                    <div class="team-name"><?php echo htmlspecialchars($team['name']); ?></div>

                    <div class="capacity-section">
                        <div class="capacity-meta">
                            <span class="capacity-label">Capacity</span>
                            <span class="capacity-count <?php echo $isFull ? 'is-full-text' : 'has-space'; ?>">
                                <?php echo $team['registered']; ?> / <?php echo $team['max_players']; ?>
                            </span>
                        </div>
                        <div class="progress-bar-track">
                            <div class="progress-bar-fill <?php echo $isFull ? 'full-fill' : ''; ?>"
                                 style="width:<?php echo $fillPct; ?>%"></div>
                        </div>
                        <span class="slots-pill <?php echo $slotClass; ?>">
                            <?php if ($isFull): ?>
                                <i class="bi bi-x-circle-fill"></i> Full
                            <?php elseif ($slotsLeft <= 3): ?>
                                <i class="bi bi-exclamation-circle-fill"></i> <?php echo $slotsLeft; ?> slot<?php echo $slotsLeft > 1 ? 's' : ''; ?> left!
                            <?php else: ?>
                                <i class="bi bi-people-fill"></i> <?php echo $slotsLeft; ?> slots remaining
                            <?php endif; ?>
                        </span>
                    </div>

                    <?php if ($studentGender === 'female'): ?>
                        <button class="btn-team-register btn-register-sport-taken" disabled>
                            <i class="bi bi-lock-fill"></i> Volleyball only
                        </button>
                    <?php elseif ($isMyTeam): ?>
                        <button class="btn-team-register btn-register-done" disabled>
                            <i class="bi bi-patch-check-fill"></i> Your Team
                        </button>
                    <?php elseif ($isFull): ?>
                        <button class="btn-team-register btn-register-full" disabled>
                            <i class="bi bi-slash-circle"></i> Team Full
                        </button>
                    <?php elseif ($sportTaken): ?>
                        <button class="btn-team-register btn-register-sport-taken" disabled>
                            <i class="bi bi-lock-fill"></i> Already Registered
                        </button>
                    <?php else: ?>
                        <button class="btn-team-register btn-register-active"
                                onclick="openConfirm(<?php echo $team['id']; ?>, '<?php echo htmlspecialchars($team['name'], ENT_QUOTES); ?>', 'football', '⚽')">
                            <i class="bi bi-person-plus-fill"></i> Register
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>


    <!-- ════════════════════════════════
         VOLLEYBALL SECTION (all genders)
    ════════════════════════════════ -->
    <section class="sport-section" id="volleyball-section">

        <div class="section-header">
            <div class="section-sport-icon icon-volleyball">🏐</div>
            <div>
                <div class="section-title">VOLLEYBALL TEAMS</div>
                <div class="section-subtitle">4 teams · Max 8 players each · One team per player</div>
            </div>
            <span class="section-rule-badge" style="color:var(--miu-gold-dim);border-color:rgba(212,168,0,0.35);background:rgba(212,168,0,0.07);">
                <i class="bi bi-people-fill me-1"></i>All Genders
            </span>
        </div>

        <?php if ($hasVolleyball): ?>
        <div class="sport-done-banner mb-3" style="background:linear-gradient(135deg,#5c4000,#8a6000);">
            <i class="bi bi-check-circle-fill" style="color:var(--miu-gold);"></i>
            <div>
                <strong>You're registered for Volleyball!</strong>
                <span>Team: <?php echo htmlspecialchars($myRegistrations['volleyball']['team_name']); ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="teams-grid" id="volleyball-grid">
            <?php foreach ($volleyballTeams as $i => $team):
                $isFull     = $team['remaining'] <= 0;
                $isMyTeam   = $hasVolleyball && $myRegistrations['volleyball']['team_id'] == $team['id'];
                $sportTaken = $hasVolleyball && !$isMyTeam;
                $fillPct    = round(($team['registered'] / $team['max_players']) * 100);
                $slotsLeft  = $team['remaining'];
                $slotClass  = $isFull ? 'slots-full' : ($slotsLeft <= 2 ? 'slots-low' : 'has-space');
                $cardClass  = $isFull ? 'is-full' : ($isMyTeam ? 'is-registered' : '');
            ?>
            <div class="team-card <?php echo $cardClass; ?>"
                 id="team-card-<?php echo $team['id']; ?>"
                 data-team-id="<?php echo $team['id']; ?>"
                 data-sport="volleyball"
                 data-max="<?php echo $team['max_players']; ?>">

                <div class="team-card-strip volleyball-strip"></div>

                <?php if ($isMyTeam): ?>
                <div class="registered-badge" title="Your team" style="background:var(--miu-gold);">
                    <i class="bi bi-check2"></i>
                </div>
                <?php endif; ?>

                <div class="team-card-body">
                    <div class="team-number">TEAM <?php echo str_pad($i + 1, 2, '0', STR_PAD_LEFT); ?></div>
                    <div class="team-name"><?php echo htmlspecialchars($team['name']); ?></div>

                    <div class="capacity-section">
                        <div class="capacity-meta">
                            <span class="capacity-label">Capacity</span>
                            <span class="capacity-count <?php echo $isFull ? 'is-full-text' : 'has-space'; ?>">
                                <?php echo $team['registered']; ?> / <?php echo $team['max_players']; ?>
                            </span>
                        </div>
                        <div class="progress-bar-track">
                            <div class="progress-bar-fill volleyball-fill <?php echo $isFull ? 'full-fill' : ''; ?>"
                                 style="width:<?php echo $fillPct; ?>%"></div>
                        </div>
                        <span class="slots-pill <?php echo $slotClass; ?>">
                            <?php if ($isFull): ?>
                                <i class="bi bi-x-circle-fill"></i> Full
                            <?php elseif ($slotsLeft <= 2): ?>
                                <i class="bi bi-exclamation-circle-fill"></i> <?php echo $slotsLeft; ?> slot<?php echo $slotsLeft > 1 ? 's' : ''; ?> left!
                            <?php else: ?>
                                <i class="bi bi-people-fill"></i> <?php echo $slotsLeft; ?> slots remaining
                            <?php endif; ?>
                        </span>
                    </div>

                    <?php if ($isMyTeam): ?>
                        <button class="btn-team-register btn-register-done" disabled>
                            <i class="bi bi-patch-check-fill"></i> Your Team
                        </button>
                    <?php elseif ($isFull): ?>
                        <button class="btn-team-register btn-register-full" disabled>
                            <i class="bi bi-slash-circle"></i> Team Full
                        </button>
                    <?php elseif ($sportTaken): ?>
                        <button class="btn-team-register btn-register-sport-taken" disabled>
                            <i class="bi bi-lock-fill"></i> Already Registered
                        </button>
                    <?php else: ?>
                        <button class="btn-team-register btn-register-active"
                                onclick="openConfirm(<?php echo $team['id']; ?>, '<?php echo htmlspecialchars($team['name'], ENT_QUOTES); ?>', 'volleyball', '🏐')">
                            <i class="bi bi-person-plus-fill"></i> Register
                        </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

</main>


<!-- ══════════════════════════════════════════════════════════
     FOOTER
══════════════════════════════════════════════════════════ -->
<footer class="page-footer">
    <p>
        <i class="bi bi-shield-lock-fill me-1" style="color:var(--miu-gold);opacity:.5;"></i>
        &copy; <?php echo date('Y'); ?> Mewar International University Sports Directorate &mdash; Secure Registration Portal
    </p>
</footer>


<!-- ══════════════════════════════════════════════════════════
     CONFIRMATION MODAL
══════════════════════════════════════════════════════════ -->
<div class="modal fade confirm-modal" id="confirmModal" tabindex="-1" aria-modal="true" role="dialog">
    <div class="modal-dialog modal-dialog-centered" style="max-width:420px;">
        <div class="modal-content">

            <div class="confirm-header">
                <span class="confirm-sport-emoji" id="confirmEmoji">⚽</span>
                <div class="confirm-title">CONFIRM REGISTRATION</div>
                <div class="confirm-subtitle">This action cannot be undone</div>
            </div>

            <div class="confirm-body">
                <div class="confirm-team-box">
                    <div class="confirm-team-icon" id="confirmTeamEmoji">⚽</div>
                    <div>
                        <div class="confirm-team-label">Selected Team</div>
                        <div class="confirm-team-name" id="confirmTeamName">—</div>
                    </div>
                </div>

                <div class="confirm-rule-note">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <span>
                        Once registered, you <strong>cannot switch teams</strong>.
                        You are allowed <strong>one team per sport</strong> only.
                        Make sure this is your final choice.
                    </span>
                </div>
            </div>

            <div class="confirm-footer">
                <button class="btn-confirm-cancel" data-bs-dismiss="modal">
                    Cancel
                </button>
                <button class="btn-confirm-go" id="confirmGoBtn" onclick="submitRegistration()">
                    <span class="spinner-sm" id="confirmSpinner"></span>
                    <i class="bi bi-person-plus-fill" id="confirmBtnIcon"></i>
                    <span id="confirmBtnText">Yes, Register Me</span>
                </button>
            </div>

        </div>
    </div>
</div>


<!-- ══════════════════════════════════════════════════════════
     TOAST CONTAINER
══════════════════════════════════════════════════════════ -->
<div class="toast-stack" id="toastStack"></div>


<!-- ══════════════════════════════════════════════════════════
     SCRIPTS
══════════════════════════════════════════════════════════ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
(function () {
    'use strict';

    // ── State ──────────────────────────────────────────────
    let pendingTeamId   = null;
    let pendingTeamName = null;
    let pendingSport    = null;
    let confirmModal    = null;

    // Bootstrap modal instance
    document.addEventListener('DOMContentLoaded', function () {
        confirmModal = new bootstrap.Modal(document.getElementById('confirmModal'));
    });

    // ── Open confirmation modal ────────────────────────────
    window.openConfirm = function (teamId, teamName, sport, emoji) {
        pendingTeamId   = teamId;
        pendingTeamName = teamName;
        pendingSport    = sport;

        document.getElementById('confirmEmoji').textContent     = emoji;
        document.getElementById('confirmTeamEmoji').textContent = emoji;
        document.getElementById('confirmTeamName').textContent  = teamName;

        // Reset button state
        setConfirmLoading(false);
        confirmModal.show();
    };

    // ── Submit registration ────────────────────────────────
    window.submitRegistration = function () {
        if (!pendingTeamId) return;

        setConfirmLoading(true);

        const body = new URLSearchParams();
        body.append('action', 'register');
        body.append('team_id', pendingTeamId);

        fetch('dashboard.php', {
            method:  'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:    body.toString()
        })
        .then(function (res) {
            if (!res.ok) throw new Error('Network error');
            return res.json();
        })
        .then(function (data) {
            confirmModal.hide();
            setConfirmLoading(false);

            if (data.success) {
                // Update the specific team card in the DOM
                updateTeamCard(data);
                                // Show done banner
                showDoneBanner(data.sport, data.team_name);
                // Disable all other cards for this sport
                lockOtherCards(data.sport, data.team_id);
                // Toast
                showToast('success', 'Registered!', data.message);
            } else {
                showToast('error', 'Registration Failed', data.message);
                // If team became full during confirm, refresh that card's button
                if (data.message && data.message.includes('full')) {
                    location.reload();
                }
            }
        })
        .catch(function () {
            confirmModal.hide();
            setConfirmLoading(false);
            showToast('error', 'Connection Error', 'Could not reach the server. Please try again.');
        });
    };

    // ── Update the registered team card ───────────────────
    function updateTeamCard(data) {
        const card = document.getElementById('team-card-' + data.team_id);
        if (!card) return;

        // Add registered class
        card.classList.add('is-registered');
        card.classList.remove('is-full');

        // Update strip
        const strip = card.querySelector('.team-card-strip');
        if (strip && data.sport === 'football') {
            strip.style.background = 'linear-gradient(90deg, var(--miu-red), var(--miu-gold))';
        }

        // Update progress bar
        const fill = card.querySelector('.progress-bar-fill');
        if (fill) {
            const pct = Math.round((data.registered / data.max) * 100);
            fill.style.width = pct + '%';
        }

        // Update count text
        const countEl = card.querySelector('.capacity-count');
        if (countEl) {
            countEl.textContent = data.registered + ' / ' + data.max;
        }

        // Update slots pill
        const pill = card.querySelector('.slots-pill');
        if (pill) {
            pill.className = 'slots-pill has-space';
            pill.innerHTML = '<i class="bi bi-people-fill"></i> ' + data.remaining + ' slots remaining';
        }

        // Add gold checkmark badge
        if (!card.querySelector('.registered-badge')) {
            const badge = document.createElement('div');
            badge.className = 'registered-badge';
            badge.title = 'Your team';
            badge.innerHTML = '<i class="bi bi-check2"></i>';
            card.appendChild(badge);
        }

        // Swap button
        const btn = card.querySelector('.btn-team-register');
        if (btn) {
            btn.className = 'btn-team-register btn-register-done';
            btn.disabled = true;
            btn.removeAttribute('onclick');
            btn.innerHTML = '<i class="bi bi-patch-check-fill"></i> Your Team';
        }
    }

    // ── Lock other cards for this sport ───────────────────
    function lockOtherCards(sport, registeredTeamId) {
        const cards = document.querySelectorAll('[data-sport="' + sport + '"]');
        cards.forEach(function (card) {
            const cardTeamId = parseInt(card.dataset.teamId);
            if (cardTeamId === registeredTeamId) return;
            if (card.classList.contains('is-full')) return;

            const btn = card.querySelector('.btn-team-register');
            if (btn && !btn.disabled) {
                btn.className = 'btn-team-register btn-register-sport-taken';
                btn.disabled = true;
                btn.removeAttribute('onclick');
                btn.innerHTML = '<i class="bi bi-lock-fill"></i> Already Registered';
            }
        });
    }

    
    // ── Show done banner above the team grid ─────────────
    function showDoneBanner(sport, teamName) {
        const sectionId = sport + '-section';
        const section   = document.getElementById(sectionId);
        if (!section) return;

        // Remove existing banner if any
        const existing = section.querySelector('.sport-done-banner');
        if (existing) existing.remove();

        const banner = document.createElement('div');
        const isVb   = sport === 'volleyball';
        banner.className = 'sport-done-banner mb-3';
        if (isVb) banner.style.background = 'linear-gradient(135deg,#5c4000,#8a6000)';
        banner.innerHTML =
            '<i class="bi bi-check-circle-fill" style="color:' + (isVb ? 'var(--miu-gold)' : 'var(--miu-gold)') + '"></i>' +
            '<div><strong>You\'re registered for ' + (sport === 'football' ? 'Football' : 'Volleyball') + '!</strong>' +
            '<span>Team: ' + escHtml(teamName) + '</span></div>';

        const grid = section.querySelector('.teams-grid');
        if (grid) section.insertBefore(banner, grid);
    }

    // ── Confirm loading state ─────────────────────────────
    function setConfirmLoading(on) {
        const btn     = document.getElementById('confirmGoBtn');
        const spinner = document.getElementById('confirmSpinner');
        const icon    = document.getElementById('confirmBtnIcon');
        const text    = document.getElementById('confirmBtnText');

        btn.disabled        = on;
        spinner.style.display = on ? 'block' : 'none';
        icon.style.display    = on ? 'none'  : 'inline';
        text.textContent      = on ? 'Registering...' : 'Yes, Register Me';
    }

    // ── Toast ─────────────────────────────────────────────
    function showToast(type, title, message) {
        const stack = document.getElementById('toastStack');
        const toast = document.createElement('div');
        toast.className = 'miu-toast toast-' + type;

        const icon = type === 'success'
            ? '<i class="bi bi-check-circle-fill toast-icon"></i>'
            : '<i class="bi bi-exclamation-circle-fill toast-icon"></i>';

        toast.innerHTML =
            icon +
            '<div>' +
                '<div class="toast-title">' + escHtml(title) + '</div>' +
                '<div class="toast-body">' + message + '</div>' +
            '</div>';

        stack.appendChild(toast);

        setTimeout(function () {
            toast.classList.add('toast-out');
            setTimeout(function () { toast.remove(); }, 350);
        }, 4500);
    }

    // ── Escape HTML ───────────────────────────────────────
    function escHtml(s) {
        return String(s)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

})();
</script>

</body>
</html>