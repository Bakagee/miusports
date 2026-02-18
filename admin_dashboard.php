<?php
session_start();
require_once 'config/config.php';
require_once 'includes/db.php';

// Simple auth guard
if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit;
}

// Only directors can access admin dashboard
if (empty($_SESSION['is_admin'])) {
    header('Location: dashboard.php');
    exit;
}

$adminName = $_SESSION['student_name'] ?? 'Admin';

// Load teams and registrations grouped
$sql = "
    SELECT
        t.id            AS team_id,
        t.team_name     AS team_name,
        t.sport         AS sport,
        t.max_players   AS max_players,
        u.id            AS user_id,
        u.name          AS player_name,
        u.matric_number AS matric_number,
        r.registration_time AS registration_time
    FROM teams t
    LEFT JOIN registrations r ON r.team_id = t.id
    LEFT JOIN users u ON u.id = r.user_id
    ORDER BY
        FIELD(t.sport,'football','volleyball'),
        t.team_name ASC,
        u.name ASC
";

$rows = $pdo->query($sql)->fetchAll();

$teamsBySport = [
    'football' => [],
    'volleyball' => [],
];

foreach ($rows as $row) {
    $sport = $row['sport'];
    if (!isset($teamsBySport[$sport])) {
        $teamsBySport[$sport] = [];
    }

    $tid = (int)$row['team_id'];
    if (!isset($teamsBySport[$sport][$tid])) {
        $teamsBySport[$sport][$tid] = [
            'team_id' => $tid,
            'team_name' => $row['team_name'],
            'sport' => $sport,
            'max_players' => (int)$row['max_players'],
            'players' => [],
        ];
    }

    if (!empty($row['user_id'])) {
        $teamsBySport[$sport][$tid]['players'][] = [
            'user_id' => (int)$row['user_id'],
            'player_name' => $row['player_name'],
            'matric_number' => $row['matric_number'],
            'registration_time' => $row['registration_time'],
        ];
    }
}

// Stats
$teamCount = 0;
$playerCount = 0;
foreach ($teamsBySport as $sport => $teams) {
    $teamCount += count($teams);
    foreach ($teams as $t) {
        $playerCount += count($t['players']);
    }
}

$activeSport = $_GET['sport'] ?? 'all';
if (!in_array($activeSport, ['all', 'football', 'volleyball'], true)) {
    $activeSport = 'all';
}

function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function sportLabel(string $sport): string { return $sport === 'football' ? 'Football' : 'Volleyball'; }

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard — <?php echo h(SITE_NAME); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bebas+Neue&family=DM+Sans:wght@300;400;500;600;700&family=Crimson+Pro:wght@400;600;700&display=swap" rel="stylesheet">

    <style>
        :root{
            --miu-red:#A31D24;
            --miu-red-dark:#7B181E;
            --miu-red-deep:#550F13;
            --miu-gold:#FFCC33;
            --miu-off:#F8F5F0;
            --miu-charcoal:#212121;
            --bg-body:#F4F1EC;
            --bg-card:#FFFFFF;
            --radius:14px;
            --shadow-sm:0 2px 12px rgba(0,0,0,0.08);
            --shadow-md:0 8px 28px rgba(0,0,0,0.12);
            --font-display:'Bebas Neue', sans-serif;
            --font-body:'DM Sans', sans-serif;
        }
        body{
            font-family:var(--font-body);
            background: radial-gradient(1100px 380px at 15% 0%, rgba(163,29,36,0.20), transparent 60%),
                        radial-gradient(900px 300px at 85% 10%, rgba(255,204,51,0.18), transparent 60%),
                        var(--bg-body);
            color:var(--miu-charcoal);
            min-height:100vh;
        }
        .topbar{
            position: sticky;
            top: 0;
            z-index: 50;
            background: linear-gradient(135deg, var(--miu-red-deep), var(--miu-red));
            border-bottom: 1px solid rgba(255,255,255,0.12);
            box-shadow: 0 10px 30px rgba(85,15,19,0.28);
        }
        .brand-mark{
            width: 42px; height: 42px;
            border-radius: 50%;
            display:flex; align-items:center; justify-content:center;
            border: 2px solid rgba(255,204,51,0.55);
            background: rgba(255,255,255,0.10);
            flex: 0 0 auto;
        }
        .brand-title{
            font-family: var(--font-display);
            letter-spacing: 0.8px;
            font-size: 1.55rem;
            line-height: 1;
        }
        .brand-sub{
            font-size: 0.92rem;
            opacity: 0.92;
        }
        .miu-card{
            background: var(--bg-card);
            border: 1px solid rgba(0,0,0,0.06);
            border-radius: var(--radius);
            box-shadow: var(--shadow-sm);
        }
        .kpi{
            border-radius: 16px;
            background: linear-gradient(180deg, rgba(255,255,255,0.92), rgba(255,255,255,0.78));
            border: 1px solid rgba(0,0,0,0.06);
            box-shadow: var(--shadow-sm);
        }
        .kpi .num{
            font-family: var(--font-display);
            font-size: 2.1rem;
            letter-spacing: 0.8px;
        }
        .pill{
            display:inline-flex;
            align-items:center;
            gap: .45rem;
            padding: .35rem .65rem;
            border-radius: 999px;
            font-weight: 600;
            font-size: .86rem;
            border: 1px solid rgba(0,0,0,0.08);
            background: rgba(255,255,255,0.70);
        }
        .pill.football{ border-color: rgba(163,29,36,0.28); color: var(--miu-red-deep); }
        .pill.volleyball{ border-color: rgba(255,204,51,0.55); color: #6a4a00; }
        .team-header{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap: 1rem;
        }
        .team-name{
            font-weight: 800;
            font-size: 1.08rem;
        }
        .small-muted{ color: rgba(33,33,33,0.70); }
        .table thead th{
            font-size: .82rem;
            text-transform: uppercase;
            letter-spacing: .6px;
            color: rgba(33,33,33,0.70);
            border-bottom: 1px solid rgba(0,0,0,0.08);
        }
        .btn-miu{
            --bs-btn-bg: var(--miu-red);
            --bs-btn-border-color: var(--miu-red);
            --bs-btn-hover-bg: var(--miu-red-dark);
            --bs-btn-hover-border-color: var(--miu-red-dark);
            --bs-btn-active-bg: var(--miu-red-deep);
            --bs-btn-active-border-color: var(--miu-red-deep);
            --bs-btn-color: #fff;
            box-shadow: 0 8px 22px rgba(163,29,36,0.25);
        }
        .btn-soft{
            background: rgba(255,255,255,0.16);
            border: 1px solid rgba(255,255,255,0.22);
            color: #fff;
        }
        .btn-soft:hover{
            background: rgba(255,255,255,0.22);
            border-color: rgba(255,255,255,0.30);
            color:#fff;
        }
        .tabs a{
            text-decoration:none;
        }
        .tabs .tab{
            display:inline-flex;
            align-items:center;
            gap:.5rem;
            padding:.55rem .85rem;
            border-radius: 999px;
            border: 1px solid rgba(0,0,0,0.08);
            background: rgba(255,255,255,0.75);
            color: var(--miu-charcoal);
            font-weight: 700;
        }
        .tabs .tab.active{
            background: linear-gradient(135deg, rgba(163,29,36,0.14), rgba(255,204,51,0.12));
            border-color: rgba(163,29,36,0.20);
        }
        .empty{
            padding: 1.1rem;
            border-radius: 14px;
            border: 1px dashed rgba(0,0,0,0.18);
            background: rgba(255,255,255,0.7);
        }
        @media (max-width: 576px){
            .topbar .container{
                flex-direction: column;
                align-items: flex-start;
            }
            .brand-title{
                font-size: 1.3rem;
            }
            .tabs{
                width: 100%;
            }
            .tabs .tab{
                justify-content: center;
                flex: 1 1 calc(33.33% - 0.5rem);
            }
            .team-header{
                flex-direction: column;
                align-items: flex-start;
            }
        }
    </style>
</head>
<body>
    <div class="topbar text-white">
        <div class="container py-3 d-flex align-items-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-3">
                <div class="brand-mark">
                    <img src="miulogo.jpeg" alt="MIU"
                         style="width:100%;height:100%;object-fit:cover;border-radius:50%;"
                         onerror="this.style.display='none';this.parentNode.innerHTML='<i class=\'bi bi-trophy-fill\' style=\'color: var(--miu-gold); font-size: 1.35rem;\'></i>'">
                </div>
                <div>
                    <div class="brand-title">ADMIN DASHBOARD</div>
                    <div class="brand-sub"><?php echo h(SITE_NAME); ?></div>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <a class="btn btn-outline-light btn-sm" href="logout.php">
                    <i class="bi bi-box-arrow-right"></i> Logout
                </a>
            </div>
        </div>
    </div>

    <div class="container my-4">
        <div class="d-flex align-items-center justify-content-between mb-3 flex-wrap gap-2">
            <div>
                <div class="h4 mb-1 fw-bold">Teams & Registered Players</div>
                <div class="small-muted">Grouped by team, sorted alphabetically within each sport.</div>
            </div>
            <div class="d-flex align-items-center gap-2 flex-wrap tabs">
                <a href="admin_dashboard.php?sport=all"><span class="tab <?php echo $activeSport==='all'?'active':''; ?>"><i class="bi bi-grid-3x3-gap"></i> All</span></a>
                <a href="admin_dashboard.php?sport=football"><span class="tab <?php echo $activeSport==='football'?'active':''; ?>"><i class="bi bi-dribbble"></i> Football</span></a>
                <a href="admin_dashboard.php?sport=volleyball"><span class="tab <?php echo $activeSport==='volleyball'?'active':''; ?>"><i class="bi bi-activity"></i> Volleyball</span></a>
            </div>
        </div>

        <?php
            $sportsToShow = [];
            if ($activeSport === 'all') {
                $sportsToShow = ['football', 'volleyball'];
            } else {
                $sportsToShow = [$activeSport];
            }
        ?>

        <div class="row g-3">
            <?php foreach ($sportsToShow as $sport): ?>
                <?php $teams = $teamsBySport[$sport] ?? []; ?>
                <div class="col-12">
                    <div class="miu-card p-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <div class="d-flex align-items-center gap-2">
                                <span class="pill <?php echo h($sport); ?>">
                                    <i class="bi <?php echo $sport === 'football' ? 'bi-dribbble' : 'bi-activity'; ?>"></i>
                                    <?php echo h(sportLabel($sport)); ?>
                                </span>
                                <span class="small-muted fw-semibold">
                                    <?php echo count($teams); ?> teams
                                </span>
                            </div>
                            <a class="btn btn-outline-secondary btn-sm" href="admin_roster_print.php?sport=<?php echo h($sport); ?>&autoprint=1" target="_blank" rel="noopener">
                                <i class="bi bi-printer"></i> Print this sport
                            </a>
                        </div>

                        <?php if (!$teams): ?>
                            <div class="empty mt-3">
                                <div class="fw-bold">No teams found for <?php echo h(sportLabel($sport)); ?>.</div>
                            </div>
                        <?php else: ?>
                            <div class="accordion mt-3" id="acc-<?php echo h($sport); ?>">
                                <?php
                                    $i = 0;
                                    foreach ($teams as $team):
                                        $i++;
                                        $players = $team['players'];
                                        $registered = count($players);
                                        $max = (int)$team['max_players'];
                                        $pct = $max > 0 ? (int)round(($registered / $max) * 100) : 0;
                                        $accId = $sport . '-' . $team['team_id'];
                                ?>
                                    <div class="accordion-item" style="border-radius: 14px; overflow: hidden; border: 1px solid rgba(0,0,0,0.06);">
                                        <h2 class="accordion-header" id="heading-<?php echo h($accId); ?>">
                                            <button class="accordion-button <?php echo $i===1 ? '' : 'collapsed'; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-<?php echo h($accId); ?>" aria-expanded="<?php echo $i===1 ? 'true' : 'false'; ?>" aria-controls="collapse-<?php echo h($accId); ?>">
                                                <div class="w-100 team-header">
                                                    <div>
                                                        <div class="team-name"><?php echo h($team['team_name']); ?></div>
                                                        <div class="small-muted">Capacity <?php echo $registered; ?> / <?php echo $max; ?> (<?php echo $pct; ?>%)</div>
                                                    </div>
                                                    <div style="min-width: 180px;">
                                                        <div class="progress" style="height: 10px; border-radius: 999px; background: rgba(0,0,0,0.06);">
                                                            <div class="progress-bar" role="progressbar"
                                                                 style="width: <?php echo $pct; ?>%; background: linear-gradient(90deg, var(--miu-red), var(--miu-gold));"
                                                                 aria-valuenow="<?php echo $pct; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </button>
                                        </h2>
                                        <div id="collapse-<?php echo h($accId); ?>" class="accordion-collapse collapse <?php echo $i===1 ? 'show' : ''; ?>" aria-labelledby="heading-<?php echo h($accId); ?>" data-bs-parent="#acc-<?php echo h($sport); ?>">
                                            <div class="accordion-body">
                                                <?php if (!$players): ?>
                                                    <div class="empty">
                                                        <div class="fw-bold">No registrations yet.</div>
                                                        <div class="small-muted">This team will appear in the printable roster once players register.</div>
                                                    </div>
                                                <?php else: ?>
                                                    <div class="table-responsive">
                                                        <table class="table align-middle mb-0">
                                                            <thead>
                                                            <tr>
                                                                <th style="width: 60px;">#</th>
                                                                <th>Player</th>
                                                                <th style="width: 180px;">Matric</th>
                                                                <th style="width: 210px;">Registered</th>
                                                            </tr>
                                                            </thead>
                                                            <tbody>
                                                            <?php $n=0; foreach ($players as $p): $n++; ?>
                                                                <tr>
                                                                    <td class="fw-semibold"><?php echo $n; ?></td>
                                                                    <td class="fw-semibold"><?php echo h($p['player_name']); ?></td>
                                                                    <td><span class="badge text-bg-light border" style="font-weight:700;"><?php echo h($p['matric_number']); ?></span></td>
                                                                    <td class="small-muted"><?php echo h((string)$p['registration_time']); ?></td>
                                                                </tr>
                                                            <?php endforeach; ?>
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>