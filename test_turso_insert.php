<?php
$url2 = 'https://supptracker-biileprince.aws-ap-northeast-1.turso.io';
$token = 'eyJhbGciOiJFZERTQSIsInR5cCI6IkpXVCJ9.eyJhIjoicnciLCJpYXQiOjE3NzczMjQ4MjMsImlkIjoiMDE5ZGQwYWMtZjMwMS03MjQ2LThmYzMtN2JkMDQyYWM5ZTYwIiwicmlkIjoiYWQxOGRhZTYtODI5NS00ZGMyLWIyNmQtMzE3ZWRlNjBhNWNmIn0.wCEot4LnI24cOJKtVr3SoFEacTUZrHVLEJVAIHFmydErCj5ADa6r3iu9cX8xMvj8Xq53MXyvjUrS8LyEk_bMAw';

$data2 = json_encode([
    'statements' => [
        [
            'q' => 'INSERT INTO turso_test_table (name) VALUES (?)',
            'params' => ['test_name_3']
        ],
        [
            'q' => 'SELECT last_insert_rowid() AS last_id'
        ]
    ]
]);
$ch2 = curl_init($url2);
curl_setopt($ch2, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch2, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch2, CURLOPT_HTTPHEADER, [
    "Authorization: Bearer $token",
    "Content-Type: application/json"
]);
curl_setopt($ch2, CURLOPT_POST, true);
curl_setopt($ch2, CURLOPT_POSTFIELDS, $data2);

$response2 = curl_exec($ch2);
$status2 = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
curl_close($ch2);

echo "Status: $status2\n";
echo "Response: $response2\n";
