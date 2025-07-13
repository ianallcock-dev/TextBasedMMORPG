<?php
// reset_round.php

require 'config.php';  // defines $pdo

// ID of your “system” account for outgoing messages
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
        exit("<p>No round has ended yet.</p>");
    }
    $endedAt = $round['round_end'];

    // 3) Bail if the *next* round already exists
    $chk = $pdo->prepare("
        SELECT COUNT(*) 
          FROM rounds 
         WHERE round_start > ?
    ");
    $chk->execute([$endedAt]);
    if ($chk->fetchColumn() > 0) {
        exit("<p>Next round already created.</p>");
    }

    // 3½) Bail if we’ve already snapshot this round
    $chkSnap = $pdo->prepare("
        SELECT COUNT(*) 
          FROM leaderboard_snapshots 
         WHERE round_end_date = ?
    ");
    $chkSnap->execute([$endedAt]);
    if ($chkSnap->fetchColumn() > 0) {
        exit("<p>Snapshot for this round already created.</p>");
    }

    //
    // 4) SNAPSHOT current stats
    //
    $snap = $pdo->prepare("
        INSERT INTO leaderboard_snapshots
          (user_id, prestige, cash, thugs, entertainers, round_end_date)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    foreach ($pdo->query("SELECT id, prestige, cash, thugs, entertainers FROM users") as $u) {
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
    // 5) CLEAR ALL PREVIOUS MESSAGES
    //
    // Must run *before* sending out this round’s award messages
    $pdo->exec("DELETE FROM messages");

    //
    // 6) AWARD snapshot-based categories (cash, thug-count, entertainer-count)
    //
    function awardSnapshotCategory($pdo, $endedAt, $column, $prizes, $label, $systemSenderId) {
        $rank = 1;
        $stm = $pdo->prepare("
            SELECT user_id, MAX({$column}) AS value
              FROM leaderboard_snapshots
             WHERE round_end_date = ?
          GROUP BY user_id
            HAVING value > 0
          ORDER BY value DESC
             LIMIT 3
        ");
        $stm->execute([$endedAt]);

        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $turns = $prizes[$rank] ?? 0;
            // award turns
            $pdo->prepare("
                UPDATE users
                   SET reserved_turns = reserved_turns + ?
                 WHERE id = ?
            ")->execute([$turns, $row['user_id']]);
            // send message
            $pdo->prepare("
                INSERT INTO messages
                  (sender_id, receiver_id, subject, body)
                VALUES (?, ?, ?, ?)
            ")->execute([
                $systemSenderId,
                $row['user_id'],
                'Round Awards',
                "{$label} #{$rank}, you earned {$turns} reserved turns!"
            ]);
            $rank++;
        }
    }

    awardSnapshotCategory($pdo, $endedAt, 'cash',        [1=>5000,2=>2500,3=>1000], 'Cash champion',       $systemSenderId);
    awardSnapshotCategory($pdo, $endedAt, 'thugs',       [1=>300, 2=>200, 3=>100 ], 'Thug-count champion', $systemSenderId);
    awardSnapshotCategory($pdo, $endedAt, 'entertainers',[1=>300, 2=>200, 3=>100 ], 'Entertainer-count champion', $systemSenderId);

    //
    // 7) AWARD kill/lure leaders (only if > 0)
    //
    function awardUserStat($pdo, $column, $prizes, $label, $systemSenderId) {
        $rank = 1;
        $stm = $pdo->prepare("
            SELECT id AS user_id, {$column} AS value
              FROM users
             WHERE {$column} > 0
          ORDER BY {$column} DESC
             LIMIT 3
        ");
        $stm->execute();

        while ($row = $stm->fetch(PDO::FETCH_ASSOC)) {
            $turns = $prizes[$rank] ?? 0;
            $pdo->prepare("
                UPDATE users
                   SET reserved_turns = reserved_turns + ?
                 WHERE id = ?
            ")->execute([$turns, $row['user_id']]);
            $pdo->prepare("
                INSERT INTO messages
                  (sender_id, receiver_id, subject, body)
                VALUES (?, ?, ?, ?)
            ")->execute([
                $systemSenderId,
                $row['user_id'],
                'Round Awards',
                "{$label} #{$rank}, you earned {$turns} reserved turns!"
            ]);
            $rank++;
        }
    }

    awardUserStat($pdo, 'thugs_killed_this_round',      [1=>5000,2=>2500,3=>1000], 'Thug-kill leader', $systemSenderId);
    awardUserStat($pdo, 'entertainers_lured_this_round',[1=>3000,2=>1250,3=>500 ],  'Master of lures',  $systemSenderId);

    //
    // 8) RESET per-round stats on users
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
    // 9) WIPE ALL GANGS & MEMBERS
    //
    $pdo->exec("DELETE FROM gang_members");
    $pdo->exec("DELETE FROM gangs");

    //
    // 10) INSERT the new 7-day round
    //
    $newStart = date('Y-m-d H:i:s');
    $newEnd   = date('Y-m-d H:i:s', strtotime('+7 days'));
    $pdo->prepare("
        INSERT INTO rounds (round_start, round_end)
        VALUES (?, ?)
    ")->execute([$newStart, $newEnd]);

    echo "<p style='color:green;'>Round reset complete. New round ends at {$newEnd}.</p>";

} catch (Exception $e) {
    exit("<p style='color:red;'>Error: " . htmlspecialchars($e->getMessage()) . "</p>");
} 
?>