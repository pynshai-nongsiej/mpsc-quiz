<?php
session_start();
require_once __DIR__ . '/includes/functions.php';

$mock_mode = isset($_SESSION['mock_mode']) && $_SESSION['mock_mode'];

if (!isset($_SESSION['quiz_file']) || !isset($_SESSION['answers'])) {
    header('Location: index.php');
    exit;
}

if ($mock_mode) {
    $version = $_SESSION['mock_version'];
    $base_dir = __DIR__ . '/quizzes/' . $version;
    $quiz_files = [];
    foreach (glob($base_dir . '/*.txt') as $file) {
        if (basename($file) === 'metadata.txt') continue;
        $quiz_files[] = $file;
    }
    $questions = [];
    foreach ($quiz_files as $qf) {
        $questions = array_merge($questions, parse_quiz($qf));
    }
    $quiz_title = ucfirst(str_replace('_',' ',$version)) . ' Mock Test';
    $quiz_id = $version . '_mock';
} else {
    $quiz_file = $_SESSION['quiz_file'];
    $quiz_path = __DIR__ . '/quizzes/' . $quiz_file;
    if (!file_exists($quiz_path)) {
        die('Quiz not found.');
    }
    $questions = parse_quiz($quiz_path);
    $quiz_title = quiz_title_from_filename($quiz_file);
    $quiz_id = $quiz_file;
}

$user_answers = $_SESSION['answers'];
$score = 0;
$total = count($questions);
$results = [];
foreach ($questions as $i => $q) {
    $correct = $q['answer'];
    $user = $user_answers[$i] ?? '';
    $is_correct = ($user === $correct);
    if ($is_correct) $score++;
    $results[] = [
        'question' => $q['question'],
        'user' => $user,
        'correct' => $correct,
        'options' => $q['options'],
        'is_correct' => $is_correct
    ];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz Results</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="glass-container">
        <h1>Results: <?= htmlspecialchars($quiz_title) ?></h1>
        <h2>Score: <?= $score ?> / <?= $total ?></h2>
        <ol class="result-list">
            <?php foreach ($results as $i => $r): ?>
                <li class="result-item <?= $r['is_correct'] ? 'correct' : 'wrong' ?>">
                    <div class="question">Q<?= $i+1 ?>. <?= htmlspecialchars($r['question']) ?></div>
                    <div class="options">
                        <?php foreach ($r['options'] as $opt): 
                            $opt_letter = strtolower($opt[0]);
                            $classes = [];
                            if ($opt_letter === $r['correct']) $classes[] = 'correct-answer';
                            if ($opt_letter === $r['user'] && !$r['is_correct']) $classes[] = 'user-wrong';
                        ?>
                            <span class="option <?= implode(' ', $classes) ?>">
                                <?= htmlspecialchars($opt) ?>
                                <?php if ($opt_letter === $r['user']): ?>
                                    <b> (Your answer)</b>
                                <?php endif; ?>
                                <?php if ($opt_letter === $r['correct']): ?>
                                    <b> (Correct)</b>
                                <?php endif; ?>
                            </span><br>
                        <?php endforeach; ?>
                    </div>
                </li>
            <?php endforeach; ?>
        </ol>
        <?php if ($mock_mode): ?>
            <a href="quiz.php?mock=1&version=<?= urlencode($version) ?>" class="retry-btn">Retry Mock Test</a>
        <?php else: ?>
            <a href="quiz.php?quiz=<?= urlencode($quiz_id) ?>" class="retry-btn">Retry Quiz</a>
        <?php endif; ?>
        <a href="index.php" class="home-btn">Back to Menu</a>
    </div>
</body>
</html> 