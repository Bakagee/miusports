<?php
session_start();
require_once 'config/config.php';
require_once 'includes/db.php';

// Simple auth guard
if (!isset($_SESSION['student_id'])) {
    header('Location: index.php');
    exit;
}

// Only directors can access roster
if (empty($_SESSION['is_admin'])) {
    header('Location: dashboard.php');
    exit;
}

$sport = $_GET['sport'] ?? 'all';
if (!in_array($sport, ['all', 'football', 'volleyball'], true)) {
    $sport = 'all';
}

$autoPrint = (($_GET['autoprint'] ?? '') === '1');

function h(string $v): string { return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }
function sportLabel(string $s): string { return $s === 'football' ? 'Football' : 'Volleyball'; }

$where = '';
$params = [];
if ($sport !== 'all') {
    $where = 'WHERE t.sport = :sport';
    $params[':sport'] = $sport;
}

$stmt = $pdo->prepare("
    SELECT
        t.id            AS team_id,
        t.team_name     AS team_name,
        t.sport         AS sport,
        t.max_players   AS max_players,
        u.name          AS player_name,
        u.matric_number AS matric_number,
        r.registration_time AS registration_time
    FROM teams t
    LEFT JOIN registrations r ON r.team_id = t.id
    LEFT JOIN users u ON u.id = r.user_id
    $where
    ORDER BY
        FIELD(t.sport,'football','volleyball'),
        t.team_name ASC,
        u.name ASC
");
$stmt->execute($params);
$rows = $stmt->fetchAll();

$teamsBySport = [
    'football' => [],
    'volleyball' => [],
];
foreach ($rows as $row) {
    $sp = $row['sport'];
    $tid = (int)$row['team_id'];
    if (!isset($teamsBySport[$sp][$tid])) {
        $teamsBySport[$sp][$tid] = [
            'team_name' => $row['team_name'],
            'max_players' => (int)$row['max_players'],
            'players' => [],
        ];
    }
    if (!empty($row['player_name'])) {
        $teamsBySport[$sp][$tid]['players'][] = [
            'player_name' => $row['player_name'],
            'matric_number' => $row['matric_number'],
            'registration_time' => $row['registration_time'],
        ];
    }
}

$generatedAt = date('Y-m-d H:i');
$title = 'Registered Players Roster';
if ($sport !== 'all') {
    $title .= ' — ' . sportLabel($sport);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo h($title); ?> — <?php echo h(SITE_NAME); ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Crimson+Pro:wght@500;600;700&display=swap" rel="stylesheet">

    <style>
        :root{
            --miu-red:#A31D24;
            --miu-red-deep:#550F13;
            --miu-gold:#FFCC33;
            --ink:#161616;
            --muted:#5b5b5b;
            --line:rgba(0,0,0,0.12);
            --paper:#ffffff;
        }
        *{ box-sizing:border-box; }
        body{
            margin:0;
            font-family:'DM Sans', system-ui, -apple-system, Segoe UI, Arial, sans-serif;
            color:var(--ink);
            background: #f3f1ed;
        }
        .wrap{
            max-width: 980px;
            margin: 22px auto;
            padding: 0 14px;
        }
        .sheet{
            background: var(--paper);
            border: 1px solid rgba(0,0,0,0.10);
            border-radius: 16px;
            box-shadow: 0 18px 65px rgba(0,0,0,0.15);
            overflow: hidden;
        }
        .sheet-header{
            padding: 18px 20px 14px 20px;
            background: linear-gradient(135deg, var(--miu-red-deep), var(--miu-red));
            color:#fff;
            position: relative;
        }
        .sheet-header::after{
            content:"";
            position:absolute;
            left:0; right:0; bottom:0;
            height: 5px;
            background: linear-gradient(90deg, var(--miu-red), var(--miu-gold), var(--miu-red));
            opacity: 0.95;
        }
        .brand{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap: 12px;
        }
        .brand-left{
            display:flex; align-items:center; gap: 12px;
            min-width: 0;
        }
        .seal{
            width: 44px; height: 44px;
            border-radius: 50%;
            background: rgba(255,255,255,0.14);
            border: 2px solid rgba(255,204,51,0.55);
            display:flex; align-items:center; justify-content:center;
            flex: 0 0 auto;
        }
        .seal svg{ display:block; }
        h1{
            margin:0;
            font-size: 1.18rem;
            letter-spacing: 0.2px;
        }
        .sub{
            margin-top: 4px;
            font-size: 0.92rem;
            opacity: 0.92;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        .meta{
            font-size: 0.92rem;
            text-align:right;
            opacity: 0.95;
            white-space: nowrap;
        }
        .toolbar{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap: 10px;
            padding: 12px 16px;
            border-bottom: 1px solid var(--line);
            background: linear-gradient(180deg, rgba(255,255,255,0.92), rgba(255,255,255,0.76));
        }
        .toolbar .hint{
            color: var(--muted);
            font-weight: 600;
            font-size: 0.92rem;
        }
        .btn{
            appearance:none;
            border: 1px solid rgba(0,0,0,0.14);
            background: #fff;
            border-radius: 10px;
            padding: 9px 12px;
            font-weight: 800;
            cursor: pointer;
        }
        .btn.primary{
            border-color: rgba(163,29,36,0.28);
            background: linear-gradient(135deg, rgba(163,29,36,0.10), rgba(255,204,51,0.10));
        }
        .content{
            padding: 16px 16px 18px 16px;
        }
        .sport-title{
            font-family: 'Crimson Pro', Georgia, serif;
            font-size: 1.22rem;
            margin: 14px 0 10px 0;
            display:flex;
            align-items:baseline;
            justify-content:space-between;
            gap: 10px;
            border-top: 1px solid var(--line);
            padding-top: 12px;
        }
        .sport-title small{ color: var(--muted); font-weight: 700; }
        .team{
            border: 1px solid rgba(0,0,0,0.10);
            border-radius: 14px;
            overflow:hidden;
            margin: 10px 0 14px 0;
            page-break-inside: avoid;
        }
        .team-head{
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap: 12px;
            padding: 10px 12px;
            background: rgba(85,15,19,0.04);
            border-bottom: 1px solid rgba(0,0,0,0.08);
        }
        .team-name{
            font-weight: 900;
        }
        .cap{
            color: var(--muted);
            font-weight: 800;
            font-size: 0.92rem;
            white-space: nowrap;
        }
        table{
            width: 100%;
            border-collapse: collapse;
        }
        thead th{
            text-align:left;
            font-size: 0.78rem;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: var(--muted);
            padding: 8px 12px;
            border-bottom: 1px solid rgba(0,0,0,0.10);
        }
        tbody td{
            padding: 8px 12px;
            border-bottom: 1px solid rgba(0,0,0,0.06);
            vertical-align: top;
        }
        tbody tr:last-child td{ border-bottom: none; }
        .badge{
            display:inline-block;
            padding: .12rem .48rem;
            border-radius: 999px;
            border: 1px solid rgba(0,0,0,0.14);
            background: rgba(255,255,255,0.75);
            font-weight: 900;
        }
        .empty{
            padding: 10px 12px;
            color: var(--muted);
            font-weight: 700;
        }
        .footer{
            padding: 12px 16px 14px 16px;
            border-top: 1px solid var(--line);
            color: var(--muted);
            display:flex;
            justify-content:space-between;
            gap: 10px;
            font-size: 0.88rem;
        }

        /* Print */
        @page { size: A4; margin: 12mm; }
        @media print{
            body{ background: #fff; }
            .wrap{ max-width: none; margin: 0; padding: 0; }
            .sheet{ border: none; border-radius: 0; box-shadow: none; }
            .toolbar{ display:none !important; }
            a[href]:after { content: ""; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="sheet">
            <div class="sheet-header">
                <div class="brand">
                    <div class="brand-left">
                        <div class="seal" aria-hidden="true">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none">
                                <path d="M12 2l2.2 6.7H21l-5.4 3.9 2.1 6.7L12 15.5 6.3 19.3l2.1-6.7L3 8.7h6.8L12 2z" fill="rgba(255,204,51,0.95)"/>
                            </svg>
                        </div>
                        <div style="min-width:0">
                            <h1><?php echo h($title); ?></h1>
                            <div class="sub"><?php echo h(SITE_NAME); ?> · Sports Directorate</div>
                        </div>
                    </div>
                    <div class="meta">
                        Generated: <?php echo h($generatedAt); ?>
                    </div>
                </div>
            </div>

            <div class="toolbar">
                <div class="hint">Use <strong>Print</strong> and select <strong>Save as PDF</strong> in the print dialog.</div>
                <div style="display:flex; gap:8px; flex-wrap:wrap;">
                    <button class="btn primary" type="button" onclick="window.print()">Print / Save as PDF</button>
                    <button class="btn" type="button" onclick="window.close()">Close</button>
                </div>
            </div>

            <div class="content">
                <?php
                    $sportsToShow = ($sport === 'all') ? ['football','volleyball'] : [$sport];
                    foreach ($sportsToShow as $sp):
                        $teams = $teamsBySport[$sp] ?? [];
                        $teamTotal = count($teams);
                        $playerTotal = 0;
                        foreach ($teams as $t) { $playerTotal += count($t['players']); }
                ?>
                    <div class="sport-title">
                        <div><?php echo h(sportLabel($sp)); ?></div>
                        <small><?php echo (int)$teamTotal; ?> teams · <?php echo (int)$playerTotal; ?> players</small>
                    </div>

                    <?php if (!$teams): ?>
                        <div class="team">
                            <div class="empty">No teams found.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($teams as $t): ?>
                            <?php
                                $players = $t['players'];
                                $registered = count($players);
                                $max = (int)$t['max_players'];
                            ?>
                            <div class="team">
                                <div class="team-head">
                                    <div class="team-name"><?php echo h($t['team_name']); ?></div>
                                    <div class="cap"><?php echo $registered; ?> / <?php echo $max; ?></div>
                                </div>

                                <?php if (!$players): ?>
                                    <div class="empty">No registrations yet.</div>
                                <?php else: ?>
                                    <table>
                                        <thead>
                                            <tr>
                                                <th style="width:44px;">#</th>
                                                <th>Player</th>
                                                <th style="width: 170px;">Matric</th>
                                                <th style="width: 190px;">Registered</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $n=0; foreach ($players as $p): $n++; ?>
                                                <tr>
                                                    <td><?php echo $n; ?></td>
                                                    <td><strong><?php echo h($p['player_name']); ?></strong></td>
                                                    <td><span class="badge"><?php echo h($p['matric_number']); ?></span></td>
                                                    <td><?php echo h((string)$p['registration_time']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>

            <div class="footer">
                <div>MIU Sports Directorate — Official roster</div>
                <div>Source: registrations database</div>
            </div>
        </div>
    </div>

    <script>
        (function () {
            const auto = <?php echo $autoPrint ? 'true' : 'false'; ?>;
            if (!auto) return;
            setTimeout(() => window.print(), 350);
        })();
    </script>
</body>
</html>

