<?php

if (!function_exists('sendToGoogleSheet')) {

    function sendToGoogleSheet($type, $name, $details, $status)
    {

        $url = "https://script.google.com/macros/s/AKfycbzNGZmbsJXWi6Syk35pVQm8O8SOf23MpV70P6WtRgSGzcElIozxBvQAYRGVmu3Aidif9A/exec";

        $data = array(
            "type" => $type,
            "name" => $name,
            "details" => $details,
            "status" => $status
        );

        $options = array(
            "http" => array(
                "header"  => "Content-Type: application/json\r\n",
                "method"  => "POST",
                "content" => json_encode($data)
            )
        );

        $context = stream_context_create($options);

        $result = file_get_contents($url, false, $context);

        return $result;
    }
}
?>