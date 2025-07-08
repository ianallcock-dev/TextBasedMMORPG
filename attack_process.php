<?php
// ─── DEBUG LOGGING ───────────────────────────────────────────
// Every time this script loads, append a marker to dbg_attack.log
file_put_contents(__DIR__ . "/dbg_attack.log",
    date('c') . " >> attack_process.php loaded\n",
    FILE_APPEND
);

// ─── ERROR REPORTING ─────────────────────────────────────────
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// ─── BOILERPLATE ─────────────────────────────────────────────
require 'config.php';
session_start();

// ─── AUTH CHECK ──────────────────────────────────────────────
if (!isset($_SESSION['user_id'])) {
    die("Please log in to attack.");
}

// ─── CURRENT ROUND ───────────────────────────────────────────
$stmt = $pdo->query("SELECT id FROM rounds ORDER BY id DESC LIMIT 1");
$currentRoundId = $stmt->fetchColumn();
file_put_contents(__DIR__ . "/dbg_attack.log",
    date('c') . " RoundID={$currentRoundId}\n",
    FILE_APPEND
);
if (!$currentRoundId) {
    die("No active round found.");
}

// ─── LOAD ATTACKER ───────────────────────────────────────────
$attacker_id = $_SESSION['user_id'];
$stmt = $pdo->prepare("
    SELECT id, username, cash, thugs, turns, items
    FROM users
    WHERE id = ?
");
$stmt->execute([$attacker_id]);
$attacker = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$attacker) {
    die("Attacker not found.");
}

// ─── LOAD DEFENDER ───────────────────────────────────────────
$defender_id = $_POST['target_id'] ?? null;
if (!$defender_id) {
    die("No target selected.");
}
if ($defender_id == $attacker_id) {
    die("You cannot target yourself.");
}
$stmt = $pdo->prepare("
    SELECT id, username, cash, thugs, entertainers, operations
    FROM users
    WHERE id = ?
");
$stmt->execute([$defender_id]);
$defender = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$defender) {
    die("Defender not found.");
}

// ─── CHECK TURNS ─────────────────────────────────────────────
if ($attacker['turns'] < 1) {
    die("You have no turns left!");
}

$action = $_POST['action'] ?? 'attack';

// ─── HELPER: LOG EVENT WITH DEBUG ────────────────────────────
function debugLogEvent($pdo, $roundId, $actor, $target, $type, $amt, $details = null) {
    $now = date('c');
    file_put_contents(__DIR__ . "/dbg_attack.log",
        "$now >> Attempting to log event: $type of $amt (details: $details)\n",
        FILE_APPEND
    );
    try {
        $ins = $pdo->prepare("
            INSERT INTO round_events
              (round_id, actor_id, target_id, action_type, amount, details)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $ok = $ins->execute([$roundId, $actor, $target, $type, $amt, $details]);
        if ($ok) {
            file_put_contents(__DIR__ . "/dbg_attack.log",
                date('c') . " >> INSERT succeeded\n",
                FILE_APPEND
            );
        } else {
            $err = $ins->errorInfo();
            file_put_contents(__DIR__ . "/dbg_attack.log",
                date('c') . " >> INSERT failed: {$err[2]}\n",
                FILE_APPEND
            );
        }
    } catch (Exception $e) {
        file_put_contents(__DIR__ . "/dbg_attack.log",
            date('c') . " >> INSERT exception: " . $e->getMessage() . "\n",
            FILE_APPEND
        );
    }
}

// ─── BRANCH: ATTACK THUGS ────────────────────────────────────
if ($action === 'attack') {
    if ($defender['thugs'] > 0 && $attacker['thugs'] > 0) {
        $att_thugs = $attacker['thugs'];
        $def_thugs = $defender['thugs'];

        // Compute how many are killed
        if ($def_thugs >= 30) {
            $atk_multiplier = mt_rand(20,25)/100;
            $thugs_killed   = ceil($def_thugs * $atk_multiplier);
        } else {
            $thugs_killed   = mt_rand(8,12);
            $atk_multiplier = $thugs_killed / $def_thugs;
        }
        $thugs_killed = min($thugs_killed, $def_thugs);

        // Log per-user metric
        $pdo->prepare("
            UPDATE users
            SET thugs_killed_this_round = thugs_killed_this_round + ?
            WHERE id = ?
        ")->execute([$thugs_killed, $attacker_id]);

        // Defender retaliation
        if ($def_thugs > 0 && $thugs_killed > 0) {
            $ret_factor = mt_rand(50,70)/100;
            $ret_base   = $thugs_killed * $ret_factor;
            $adv_ratio  = $att_thugs / max($def_thugs,1);
            if ($adv_ratio < 1) $adv_ratio = 1/$adv_ratio;
            $attacker_thugs_lost = ceil($ret_base/$adv_ratio);
            $attacker_thugs_lost = min($attacker_thugs_lost, $att_thugs);
        } else {
            $attacker_thugs_lost = 0;
        }

        $new_def_thugs = max(0, $def_thugs - $thugs_killed);
        $new_att_thugs = max(0, $att_thugs - $attacker_thugs_lost);

        // Apply DB updates
        $pdo->prepare("UPDATE users SET thugs = ? WHERE id = ?")
            ->execute([$new_def_thugs, $defender_id]);
        $pdo->prepare("UPDATE users SET thugs = ? WHERE id = ?")
            ->execute([$new_att_thugs, $attacker_id]);
        $pdo->prepare("UPDATE users SET turns = turns - 1 WHERE id = ?")
            ->execute([$attacker_id]);

        // Log event
        debugLogEvent(
            $pdo,
            $currentRoundId,
            $attacker_id,
            $defender_id,
            'attack',
            $thugs_killed,
            round($atk_multiplier*100) . '%'
        );

        echo "You killed $thugs_killed of {$defender['username']}'s thugs. ";
        echo "You lost $attacker_thugs_lost of your own thugs. ";
        echo "{$defender['username']} now has $new_def_thugs thugs left. ";
        echo "You now have $new_att_thugs thugs left.";
        exit;
    }
    // ─── Cash steal when defender has no thugs ───────────────
    elseif ($defender['thugs'] <= 0) {
        $pct           = rand(40,60);
        $avail         = $defender['cash'];
        $amount_stolen = min( ceil($avail*$pct/100), $avail );

        if ($amount_stolen > 0) {
            $pdo->prepare("UPDATE users SET cash = cash - ? WHERE id = ?")
                ->execute([$amount_stolen, $defender_id]);
            $pdo->prepare("UPDATE users SET cash = cash + ? WHERE id = ?")
                ->execute([$amount_stolen, $attacker_id]);
            $pdo->prepare("UPDATE users SET turns = turns - 1 WHERE id = ?")
                ->execute([$attacker_id]);

            // Log event
            debugLogEvent(
                $pdo,
                $currentRoundId,
                $attacker_id,
                $defender_id,
                'steal_cash',
                $amount_stolen
            );

            echo "You stole $$amount_stolen from {$defender['username']}! ($pct%).";
        } else {
            echo "{$defender['username']} has no cash left to steal!";
        }
        exit;
    } else {
        echo "You have no thugs left to attack with!";
        exit;
    }
}

// ─── BRANCH: LURE ENTERTAINERS ──────────────────────────────
elseif ($action === 'lure') {
    $luxury = max(0, intval($_POST['luxury_goods_used'] ?? 0));
    if ($luxury < 1) {
        die("Invalid number of lure items.");
    }
    if ($luxury > $attacker['items']) {
        die("You only have {$attacker['items']} lure items.");
    }

    // Atomic spend
    $stmt = $pdo->prepare("
      UPDATE users
      SET items = items - :n, turns = turns - 1
      WHERE id = :id AND items >= :n AND turns >= 1
    ");
    $stmt->execute([':n'=>$luxury,':id'=>$attacker_id]);
    if ($stmt->rowCount()===0) {
        die("Cannot use $luxury lure item(s).");
    }

    $mult = mt_rand(80,120)/100;
    $lured = min( floor($luxury*$mult), $defender['entertainers'] );
    if ($lured > 0) {
        $pdo->prepare("UPDATE users SET entertainers = entertainers - ? WHERE id = ?")
            ->execute([$lured, $defender_id]);
        $pdo->prepare("UPDATE users SET entertainers = entertainers + ? WHERE id = ?")
            ->execute([$lured, $attacker_id]);
        // metric
        $pdo->prepare("
          UPDATE users
          SET entertainers_lured_this_round = entertainers_lured_this_round + ?
          WHERE id = ?
        ")->execute([$lured, $attacker_id]);
    }

    $pct = round($mult*100);
    // Log event
    debugLogEvent(
        $pdo,
        $currentRoundId,
        $attacker_id,
        $defender_id,
        'lure',
        $lured,
        "{$pct}%"
    );

    echo "You used $luxury lure item(s) (×{$pct}%) and lured away $lured entertainers from {$defender['username']}.";
    exit;
}

// ─── BRANCH: SABOTAGE OPERATIONS ────────────────────────────
elseif ($action === 'sabotage') {
    $s_qty = max(1, intval($_POST['sabotage_qty'] ?? 0));
    if ($defender['thugs'] > 0) {
        die("Cannot sabotage until target has 0 thugs.");
    }
    if ($s_qty > $defender['operations']) {
        die("Target has only {$defender['operations']} operations.");
    }

    $cost = 0;
    for ($i=0; $i<$s_qty; $i++) {
        $cost += rand(100,200);
    }
    if ($attacker['thugs'] < $cost) {
        die("Need $cost thugs to sabotage $s_qty ops but only have {$attacker['thugs']}.");
    }

    // Apply
    $pdo->prepare("
      UPDATE users
      SET thugs = thugs - ?, turns = turns - ?
      WHERE id = ?
    ")->execute([$cost, $s_qty, $attacker_id]);
    $pdo->prepare("
      UPDATE users
      SET operations = GREATEST(operations - ?,0)
      WHERE id = ?
    ")->execute([$s_qty, $defender_id]);

    // Log event
    debugLogEvent(
        $pdo,
        $currentRoundId,
        $attacker_id,
        $defender_id,
        'sabotage',
        $s_qty,
        "$cost thug cost"
    );

    echo "You sabotaged $s_qty operation(s) from {$defender['username']} at a cost of $cost thugs.";
    exit;
}

else {
    die("Invalid action.");
}
