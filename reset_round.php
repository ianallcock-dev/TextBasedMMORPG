<?php
// reset_round.php

// — Load DB connection from config.php —
require 'config.php';  // defines $pdo

// change this to whatever user ID you use as “system”
$systemSenderId = 1;  

try {
    // 1) Find the most recently ended round
    $stmt = $pdo->prepare("
        SELECT *
          FROM rounds
         WHERE round_end <= NOW()
      ORDER BY round_end DESC
         LIMIT 1
    ");
    $stmt->execute();
    $round = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2) Bail if no round has ended yet
    if (!$round) {
        echo "<p>No round has ended yet.</p>";
        exit;
    }
    $endedAt = $round['round_end'];

    // 3) Bail if next round already exists
    $check = $pdo->prepare("
        SELECT COUNT(*) 
          FROM rounds 
         WHERE round_start > ?
    ");
    $check->execute([$endedAt]);
    if ($check->fetchColumn() > 0) {
        echo "<p>Next round already created.</p>";
        exit;
    }

    //
    // 4) SNAPSHOT: only the columns that exist in leaderboard_snapshots
    //
    $snap = $pdo->prepare("
        INSERT INTO leaderboard_snapshots
          (user_id, prestige, cash, thugs, entertainers, round_end_date)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    $users = $pdo->query("
        SELECT id, prestige, cash, thugs, entertainers
          FROM users
    ");
    while ($u = $users->fetch(PDO::FETCH_ASSOC)) {
        $snap->execute([
            $u['id'],
            $u['prestige'],
            $u['cash'],
            $u['thugs'],
            $u['entertainers'],
            $endedAt
        ]);
    }

    //
    // 5) AWARD from snapshots: cash, thug‐count, entertainer‐count
    //
    function awardSnapshotCategory($pdo, $endedAt, $column, $prizes, $label, $systemSenderId) {
        $rank = 1;
        $stm = $pdo->prepare("
            SELECT user_id
              FROM leaderboard_snapshots
             WHERE round_end_date = ?
          ORDER BY {$column} DESC
             LIMIT 3
        ");
        $stm->execute([$endedAt]);

        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $turns = $prizes[$rank] ?? 0;
            // give reserved turns
            $pdo->prepare("
                UPDATE users 
                   SET reserved_turns = reserved_turns + ? 
                 WHERE id = ?
            ")->execute([$turns, $row['user_id']]);

            // notify user
            $message = "{$label} #{$rank}, you earned {$turns} reserved turns!";
            $pdo->prepare("
                INSERT INTO messages
                  (sender_id, receiver_id, subject, body)
                VALUES (?, ?, ?, ?)
            ")->execute([
                $systemSenderId,
                $row['user_id'],
                'Round Awards',
                $message
            ]);

            $rank++;
        }
    }

    awardSnapshotCategory($pdo, $endedAt, 'cash',        [1=>5000,2=>2500,3=>1000], 'Cash champion',       $systemSenderId);
    awardSnapshotCategory($pdo, $endedAt, 'thugs',       [1=>300, 2=>200, 3=>100 ], 'Thug‐count champion', $systemSenderId);
    awardSnapshotCategory($pdo, $endedAt, 'entertainers',[1=>300, 2=>200, 3=>100 ], 'Entertainer‐count champion', $systemSenderId);

    //
    // 6) AWARD from users table: thugs_killed_this_round & entertainers_lured_this_round
    //
    function awardUserStat($pdo, $column, $prizes, $label, $systemSenderId) {
        $rank = 1;
        $stm = $pdo->prepare("
            SELECT id AS user_id
              FROM users
          ORDER BY {$column} DESC
             LIMIT 3
        ");
        $stm->execute();

        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $turns = $prizes[$rank] ?? 0;
            // give reserved turns
            $pdo->prepare("
                UPDATE users 
                   SET reserved_turns = reserved_turns + ? 
                 WHERE id = ?
            ")->execute([$turns, $row['user_id']]);

            // notify user
            $message = "{$label} #{$rank}, you earned {$turns} reserved turns!";
            $pdo->prepare("
                INSERT INTO messages
                  (sender_id, receiver_id, subject, body)
                VALUES (?, ?, ?, ?)
            ")->execute([
                $systemSenderId,
                $row['user_id'],
                'Round Awards',
                $message
            ]);

            $rank++;
        }
    }

    awardUserStat($pdo, 'thugs_killed_this_round',      [1=>5000,2=>2500,3=>1000], 'Thug‐kill leader', $systemSenderId);
    awardUserStat($pdo, 'entertainers_lured_this_round',[1=>3000,2=>1250,3=>500 ], 'Master of lures',  $systemSenderId);

    //
    // 7) RESET per‐round stats on users
    //
    $pdo->exec("
        UPDATE users
           SET cash                          = 0,
               thugs                         = 0,
               entertainers                  = 0,
               items                         = 0,
               operations                    = 0,
               prestige                      = 0,
               thugs_killed_this_round       = 0,
               entertainers_lured_this_round = 0
    ");

    //
    // 8) INSERT the new 7-day round
    //
    $newStart = date('Y-m-d H:i:s');
    $newEnd   = date('Y-m-d H:i:s', strtotime('+7 days'));
    $pdo->prepare("
        INSERT INTO rounds (round_start, round_end)
        VALUES (?, ?)
    ")->execute([$newStart, $newEnd]);

    echo "<p style='color:green;'>Round reset complete. New round ends at {$newEnd}.</p>";

} catch (Exception $e) {
    echo "<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>";
    exit;
}
?>