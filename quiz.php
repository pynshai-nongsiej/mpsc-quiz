<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

$mock_mode = isset($_GET['mock']) && $_GET['mock'] == 1 && isset($_GET['version']);

if ($mock_mode) {
    $version = basename($_GET['version']);
    $base_dir = __DIR__ . '/quizzes/' . $version;
    $quiz_files = [];
    foreach (glob($base_dir . '/*.txt') as $file) {
        if (basename($file) === 'metadata.txt') continue;
        $quiz_files[] = $file;
    }
    if (empty($quiz_files)) die('No quizzes found in this version.');
    $questions = [];
    foreach ($quiz_files as $qf) {
        $questions = array_merge($questions, parse_quiz($qf));
    }
    shuffle($questions);
    $quiz_title = ucfirst(str_replace('_',' ',$version)) . ' Mock Test';
    $quiz_id = $version . '_mock';
} else {
    if (!isset($_GET['quiz'])) {
        header('Location: index.php');
        exit;
    }
    $quiz_file = $_GET['quiz'];
    $quiz_path = __DIR__ . '/quizzes/' . $quiz_file;
    if (!file_exists($quiz_path)) {
        die('Quiz not found.');
    }
    $questions = parse_quiz($quiz_path);
    $quiz_title = quiz_title_from_filename($quiz_file);
    $quiz_id = $quiz_file;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $_SESSION['answers'] = $_POST['answers'] ?? [];
    $_SESSION['quiz_file'] = $quiz_id;
    $_SESSION['mock_mode'] = $mock_mode;
    if ($mock_mode) {
        $_SESSION['mock_version'] = $version;
    }
    header('Location: result.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz: <?= htmlspecialchars($quiz_title) ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="glass-container">
        <h1><?= htmlspecialchars($quiz_title) ?></h1>
        <form method="post">
            <?php foreach ($questions as $i => $q): ?>
                <div class="question-block">
                    <p class="question"><b><?= ($i+1) . '. ' . htmlspecialchars($q['question']) ?></b></p>
                    <div class="options">
                        <?php foreach ($q['options'] as $opt): 
                            $opt_letter = strtolower($opt[0]); ?>
                            <label class="option-label">
                                <input type="radio" name="answers[<?= $i ?>]" value="<?= $opt_letter ?>" required>
                                <?= htmlspecialchars($opt) ?>
                            </label><br>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="submit-btn">Submit</button>
        </form>
    </div>
</body>
</html> 