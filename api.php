<?php
header('Content-Type: application/json');

// Database connection settings – update these with your own credentials.
$host = 'localhost';
$db   = 'pubgolf';
$user = 'your_db_username';
$pass = 'your_db_password';
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];
try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'load_data') {
    $scoreboards = [];
    $groups = ['group1', 'group2', 'group3'];
    foreach ($groups as $group) {
        $stmt = $pdo->prepare("SELECT player_index, player_name, hole1, hole2, hole3, hole4, hole5, hole6, hole7, hole8, hole9 FROM scoreboards WHERE group_id = ?");
        $stmt->execute([$group]);
        $scoreboards[$group] = $stmt->fetchAll();
    }
    $stmt = $pdo->query("SELECT challenge_name, completed_by FROM challenges");
    $challenges = $stmt->fetchAll();
    echo json_encode(['scoreboards' => $scoreboards, 'challenges' => $challenges]);
} elseif ($action === 'save_scoreboard') {
    $group = $_POST['group'] ?? '';
    $scoreboard = json_decode($_POST['scoreboard'] ?? '[]', true);
    if (!$group || !is_array($scoreboard)) {
        echo json_encode(['error' => 'Invalid parameters']);
        exit;
    }
    // Delete existing score entries for the group
    $stmt = $pdo->prepare("DELETE FROM scoreboards WHERE group_id = ?");
    $stmt->execute([$group]);
    // Insert new scoreboard entries
    foreach ($scoreboard as $row) {
        $stmt = $pdo->prepare("INSERT INTO scoreboards (group_id, player_index, player_name, hole1, hole2, hole3, hole4, hole5, hole6, hole7, hole8, hole9)
                               VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $scores = $row['scores'];
        $stmt->execute([
            $group,
            $row['player_index'],
            $row['name'],
            $scores[0],
            $scores[1],
            $scores[2],
            $scores[3],
            $scores[4],
            $scores[5],
            $scores[6],
            $scores[7],
            $scores[8]
        ]);
    }
    echo json_encode(['success' => true]);
} elseif ($action === 'save_challenges') {
    $challengesData = json_decode($_POST['challenges'] ?? '[]', true);
    if (!is_array($challengesData)) {
        echo json_encode(['error' => 'Invalid challenges data']);
        exit
