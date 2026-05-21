<?php

function sendStaffNotification($message)
{
    $botToken = "8847105729:AAGakY2QB__dp3kPSAe-j-Ib23ha7B-xfJQ";
    $chatID = "7451424193";

    $url = "https://api.telegram.org/bot$botToken/sendMessage";

    $data = [
        'chat_id' => $chatID,
        'text' => $message
    ];

    $options = [
        'http' => [
            'header'  => "Content-Type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            'content' => http_build_query($data),
        ],
    ];

    $context = stream_context_create($options);

    file_get_contents($url, false, $context);
}

?>

//  $botToken = "8847105729:AAGakY2QB__dp3kPSAe-j-Ib23ha7B-xfJQ";
// $chatId = "7451424193";