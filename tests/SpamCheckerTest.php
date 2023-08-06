<?php

namespace App\Tests;

use App\Entity\Comment;
use App\SpamChecker;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;
use Symfony\Contracts\HttpClient\ResponseInterface;

class SpamCheckerTest extends TestCase
{
    public function testSpamScoreWithInvalidMessage(): void
    {
        $comment = new Comment();
        $comment->setCreatedAtValue();
        $context = [];

        $mockClient = new MockHttpClient([
            new MockResponse('invalid', ['response_headers' => ['x-akismet-debug-help: Invalid key']]),
        ]);
        $checker = new SpamChecker('fake-key', 'fake-endpoint', $mockClient);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Unable to check for spam: invalid (Invalid key).');
        $checker->getSpamScore($comment, $context);
    }

    /**
     * @dataProvider commentsProvider
     */
    public function testSpamScore(
        int               $expectedScore,
        ResponseInterface $response,
        Comment           $comment,
        array             $context,
    ): void
    {
        $mockClient = new MockHttpClient([$response]);
        $spamChecker = new SpamChecker('fake-key', 'fake-endpoint', $mockClient);

        $score = $spamChecker->getSpamScore($comment, $context);
        $this->assertEquals($expectedScore, $score);
    }

    public function commentsProvider(): iterable
    {
        $comment = new Comment();
        $comment->setCreatedAtValue();
        $context = [];

        $response = new MockResponse('', ['response_headers' => ['x-akismet-pro-tip: discard']]);
        yield 'blatant_spam' => [2, $response, $comment, $context];

        $response = new MockResponse('true');
        yield 'spam' => [1, $response, $comment, $context];

        $response = new MockResponse('false');
        yield 'not_spam' => [0, $response, $comment, $context];
    }
}
