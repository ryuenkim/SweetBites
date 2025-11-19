<?php
$hash = '$2y$10$YfL7mD3nDLEWmQRM6CGaD.5Bu9cSmNoK9MfBAt5p4oDLQFvMzuA9m';
$plain = 'admin123';

if (password_verify($plain, $hash)) {
    echo "✅ Password matches!";
} else {
    echo "❌ Password does NOT match.";
}
