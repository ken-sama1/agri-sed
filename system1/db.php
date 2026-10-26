<?php
session_start();
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

function requestContainsScriptTag($value): bool
{
    if (is_array($value)) {
        foreach ($value as $item) {
            if (requestContainsScriptTag($item)) {
                return true;
            }
        }
        return false;
    }

    return is_string($value) && preg_match('/<\s*\/?\s*script\b/i', $value) === 1;
}

foreach ([ $_GET, $_POST ] as $requestData) {
    if (requestContainsScriptTag($requestData)) {
        http_response_code(400);
        exit("Input contains unsupported script markup.");
    }
}

$conn = new mysqli("localhost", "user", "W@llyb0ld", "agrised");
if ($conn->connect_error) { 
    die("Connection failed: " . $conn->connect_error); 
}
?>
