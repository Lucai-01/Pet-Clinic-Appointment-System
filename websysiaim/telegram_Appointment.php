
<?php

function sendAppointmentNotification($message)
{
    $botToken = "8965970794:AAEpYYft4_xbp-B5eusXm4vyf7jHY6l3cgg";
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

//    $botToken = "8965970794:AAEpYYft4_xbp-B5eusXm4vyf7jHY6l3cgg";
//    $chatId = "7451424193";