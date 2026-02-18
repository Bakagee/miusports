<?php

    // Fetch team details (note: football column)
    $stmt = $pdo->prepare('SELECT max_players FROM teams WHERE sport = :tid');
    $stmt->execute([':tid' => "football"]);
    $team_one = $stmt->fetch();

    if (!$team_one) {
        echo json_encode(['success' => false, 'message' => 'Team not found.']);
        exit;
    }

    $max_football = (int)$team_one['max_players'];


    // Fetch team details (note: volley column)
    $stmt = $pdo->prepare('SELECT max_players FROM teams WHERE sport = :tid');
    $stmt->execute([':tid' => "volleyball"]);
    $team_two = $stmt->fetch();

    if (!$team_two) {
        echo json_encode(['success' => false, 'message' => 'Team not found.']);
        exit;
    }

    $max_volley = (int)$team_two['max_players'];


?>    
