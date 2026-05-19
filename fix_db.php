<?php
$pdo = new PDO('sqlite:database/database.sqlite');
$pdo->exec("UPDATE time_sessions SET ended_at = datetime('now'), duration_seconds = 120 WHERE ended_at IS NULL");
echo 'Done';
