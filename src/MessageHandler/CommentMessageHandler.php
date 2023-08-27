<?php

namespace App\MessageHandler;

use App\Enum\CommentStatusEnum;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;

class CommentMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SpamChecker $spamChecker,
    ) {
    }

    public function __invoke(CommentMessage $commentMessage): void
    {
        $comment = $this->commentRepository->find($commentMessage->id);

        if (!$comment) {
            return;
        }

        $commentStatus = 0 < $this->spamChecker->getSpamScore($comment, $commentMessage->context)
            ? CommentStatusEnum::Spam->value
            : CommentStatusEnum::Published->value;

        $comment->setStatus($commentStatus);

        $this->entityManager->flush();
    }
}