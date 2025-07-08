<?php
// ——————————— RESET ROUND SCRIPT ———————————
require 'config.php';

// 1) Identify the round that just ended
$stmt = $pdo->query("SELECT * FROM rounds ORDER BY id DESC LIMIT 1");
$round   = $stmt->fetch(PDO::FETCH_ASSOC);
$endedAt = $round['round_end'];

// 2) Snapshot every user’s stats
$stmt = $pdo->query("SELECT * FROM users");
while ($u = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $ins = $pdo->prepare("
        INSERT INTO leaderboard_snapshots
          (user_id, prestige, cash, thugs, entertainers,
           thugs_killed, entertainers_lured, round_end_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $ins->execute([
        $u['id'],
        $u['prestige'],
        $u['cash'],
        $u['thugs'],
        $u['entertainers'],
        $u['thugs_killed_this_round'],
        $u['entertainers_lured_this_round'],
        $endedAt
    ]);
}

// Helper to award a category
function awardCategory($pdo, $endedAt, $column, $prizes, $text) {
    $rank = 1;
    $stmt = $pdo->prepare("
        SELECT user_id
        FROM leaderboard_snapshots
        WHERE round_end_date = ?
        ORDER BY {$column} DESC
        LIMIT 3
    ");
    $stmt->execute([$endedAt]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $turns = $prizes[$rank] ?? 0;
        $pdo->prepare("UPDATE users SET reserved_turns = reserved_turns + ? WHERE id = ?")
            ->execute([$turns, $row['user_id']]);
        $pdo->prepare("INSERT INTO messages (user_id, message) VALUES (?, ?)")
            ->execute([
                $row['user_id'],
                "{$text} #{$rank}, you earned {$turns} reserved turns!"
            ]);
        $rank++;
    }
}

// 3) Award top 3 by Cash
awardCategory(
    $pdo, $endedAt,
    'cash',
    [1=>5000,2=>2500,3=>1000],
    'Cash champion'
);

// 4) Award top 3 by Thugs Killed
awardCategory(
    $pdo, $endedAt,
    'thugs_killed',
    [1=>5000,2=>2500,3=>1000],
    'Thug‐kill leader'
);

// 5) Award top 3 by Entertainer Lures
awardCategory(
    $pdo, $endedAt,
    'entertainers_lured',
    [1=>3000,2=>1250,3=>500],
    'Master of lures'
);

// 6) Zero out per-round user stats
$pdo->exec("
    UPDATE users
    SET
      cash                       = 0,
      thugs                      = 0,
      entertainers                = 0,
      items                      = 0,
      operations                  = 0,
      prestige                   = 0,
      thugs_killed_this_round    = 0,
      entertainers_lured_this_round = 0
");

// 7) Start the new 7-day round
$newStart = date('Y-m-d H:i:s');
$newEnd   = date('Y-m-d H:i:s', strtotime('+7 days'));
$pdo->prepare("
    INSERT INTO rounds (round_start, round_end)
    VALUES (?, ?)
")->execute([$newStart, $newEnd]);

echo "<p style='color:green;'>Round reset complete. New round ends at {$newEnd}.</p>";
