<?php

namespace App\Lib\Development;


class SlackHelper
{
    /**
     * @param string $label
     * @param string $content
     * @return array
     */
    public function getHorizontalField(string $label, string $content): array
    {
        return [
            "type"   => "section",
            "fields" => [
                [
                    "type" => "mrkdwn",
                    "text" => "*$label*",
                ],
                [
                    "type" => "mrkdwn",
                    "text" => $content,
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    public function getDivider(): array
    {
        return [
            "type" => "divider",
        ];
    }

    /**
     * @param array $blocks
     * @return array
     */
    public function getPayload(array $blocks): array
    {
        return [
            "blocks" => $blocks
        ];
    }

    /**
     * @param string $title
     * @param string|null $imageUrl
     * @return array
     */
    public function getTitleSection(string $title, ?string $imageUrl = null): array
    {
        $imageSection = [];
        if ($imageUrl) {
            $imageSection = [
                "accessory" => [
                    "type"      => "image",
                    "image_url" => $imageUrl,
                    "alt_text"  => "image"
                ]
            ];
        }
        $textSection = [
            "type" => "section",
            "text" => [
                "type" => "mrkdwn",
                "text" => $title
            ]
        ];

        return array_merge($textSection, $imageSection);
    }
}

