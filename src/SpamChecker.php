<?php

namespace App;

use App\Entity\Comment;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpamChecker
{
    public function __construct(
        private readonly string $akismetKey,
        private readonly string $akismetEndpoint,
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function getSpamScore(Comment $comment, array $context): int
    {
        $response = $this->httpClient->request('POST', $this->akismetEndpoint, [
            'body' => array_merge($context, [
                'api_key' => $this->akismetKey,
                'blog' => 'https://guestbook.example.com',
                'comment_type' => 'comment',
                'comment_author' => $comment->getAuthor(),
                'comment_author_email' => $comment->getEmail(),
                'comment_content' => $comment->getText(),
                'comment_date_gmt' => $comment->getCreatedAt()?->format('c'),
                'blog_lang' => 'en',
                'blog_charset' => 'UTF-8',
                'is_test' => true,
            ]),
        ]);

        $headers = $response->getHeaders();

        if ('discard' === ($headers['x-akismet-pro-tip'][0] ?? '')) {
            return 2;
        }

        $content = $response->getContent();

        if (isset($headers['x-akismet-debug-help'][0])) {
            throw new RuntimeException(
                sprintf(
                    'Unable to check for spam: %s (%s).',
                    $content,
                    $headers['x-akismet-debug-help'][0]
                ),
            );
        }

        return 'true' === $content ? 1 : 0;
    }
}