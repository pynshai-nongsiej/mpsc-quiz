<?php
require_once __DIR__ . '/includes/functions.php';
$quiz_files = get_quiz_files(__DIR__ . '/quizzes');

// Group quizzes by version
$grouped = [];
foreach ($quiz_files as $qf) {
    [$ver, $file] = explode('/', $qf, 2);
    $grouped[$ver][] = $qf;
}
$versions = array_keys($grouped);

// For random mock test
$random_version = $versions[array_rand($versions)];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MPSC Quiz Site</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="glass-container">
        <h1>MPSC Quiz Site</h1>
        <h2>Select a Quiz</h2>
        <a href="quiz.php?mock=1&version=<?= urlencode($random_version) ?>" class="submit-btn" style="display:block;text-align:center;margin-bottom:2rem;">🎲 Random Mock Test (<?= htmlspecialchars(ucfirst(str_replace('_',' ',$random_version))) ?>)</a>
        <?php foreach ($grouped as $ver => $quizzes): ?>
            <h3><?= htmlspecialchars(ucfirst(str_replace('_',' ',$ver))) ?></h3>
            <ul class="quiz-list">
                <?php foreach ($quizzes as $quiz): ?>
                    <li><a href="quiz.php?quiz=<?= urlencode($quiz) ?>"> <?= htmlspecialchars(quiz_title_from_filename($quiz)) ?> </a></li>
                <?php endforeach; ?>
            </ul>
        <?php endforeach; ?>
    </div>
</body>
</html> 