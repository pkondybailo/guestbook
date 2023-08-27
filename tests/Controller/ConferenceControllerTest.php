<?php

namespace App\Tests\Controller;

use App\Entity\Comment;
use App\Enum\CommentStatusEnum;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Panther\PantherTestCase;

class ConferenceControllerTest extends PantherTestCase
{
    public function testIndex(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h2', 'Give your feedback!');
    }

    public function testCommentSubmission(): void
    {
        $client = static::createClient();
        $client->request('GET', '/conference/amsterdam-2020');
        $email = 'comment@mail.com';
        $client->submitForm('Submit', [
            'comment_form[author]' => 'Test Submission',
            'comment_form[text]' => 'Test Comment',
            'comment_form[email]' => $email,
            'comment_form[photo]' => dirname(__DIR__, 2).'/public/images/under-construction.gif',
        ]);

        /** @var Comment $comment */
        $comment = self::getContainer()->get(CommentRepository::class)->findOneByEmail($email);
        $comment->setStatus(CommentStatusEnum::Published->value);
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        self::assertResponseRedirects();
        $client->followRedirect();
        self::assertSelectorExists('div:contains("There are 2 comments")');
    }

    public function testConferencePage(): void
    {
        $client = static::createClient();
        $crawler = $client->request('GET', '/');

        self::assertCount(2, $crawler->filter('h4'));

        $client->clickLink('View');

        self::assertResponseIsSuccessful();
        self::assertPageTitleContains('Amsterdam');
        self::assertSelectorTextContains('h2', 'Amsterdam 2020');
        self::assertSelectorExists('div:contains("There are 1 comments")');
    }
}
