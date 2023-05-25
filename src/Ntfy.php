<?php

declare(strict_types=1);

namespace App;

final readonly class Ntfy
{
    public function __construct(
        private string $topic,
    ) {
    }

    public function sendSuccessMessage(string $message): void
    {
        $this->send($message, 'Success', 'partying_face');
    }

    public function sendErrorMessage(string $message): void
    {
        $this->send($message, 'Error', 'warning');
    }

    private function send(string $message, string $title, string $tag): void
    {
        @file_get_contents(sprintf('https://ntfy.sh/%s', $this->topic), false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: text/plain\r\n" . "Title: $title\r\n" . "Priority: urgent\r\n" . "Tags: $tag",
                'content' => $message,
            ],
        ]));
    }
}
