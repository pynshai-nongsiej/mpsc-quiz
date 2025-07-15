# MPSC Quiz Site

A minimalistic PHP-based quiz website for MPSC and other quizzes. No database required. Just drop your .txt quiz files in the `quizzes/` folder!

## Features
- Loads quizzes from `.txt` files
- Parses questions, options, and answers
- Minimal black & white glassmorphism UI
- Shows all questions at once
- Calculates and displays score

## Project Structure
```
mpsc_quiz_site/
├── index.php                  # Homepage / quiz menu
├── quiz.php                   # Runs a selected quiz
├── result.php                 # Shows final score
├── quizzes/                   # Folder with .txt files
│   ├── synonyms.txt
│   ├── meghalaya_current_affairs.txt
│   └── ...
├── includes/
│   └── functions.php          # Text file parser, helpers
├── style.css                  # Minimal glassmorphism CSS
└── README.md
```

## How to Use
1. Place your quiz `.txt` files in the `quizzes/` folder.
2. Each file should follow this format:

```
1. What is the capital of Meghalaya?
   a) Guwahati
   b) Shillong
   c) Kohima
   d) Imphal
   Answer: b

2. MHIS scheme relates to?
   a) Housing
   b) Education
   c) Insurance
   d) Employment
   Answer: c
```

3. Open `index.php` in your browser (via localhost or your PHP server).
4. Select a quiz, answer questions, and see your score!

## Requirements
- PHP 7.0+
- No database needed

## Customization
- Edit `style.css` for theme tweaks.
- Add more quizzes by dropping `.txt` files in `quizzes/`.

---
MIT License 