<?php

namespace App\MessageHandler;

use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class CommentMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly SpamChecker $spamChecker,
        private readonly MessageBusInterface $bus,
        private readonly WorkflowInterface $commentStateMachine,
        private readonly LoggerInterface $logger,
    ) {
    }

    public function __invoke(CommentMessage $commentMessage): void
    {
        $comment = $this->commentRepository->find($commentMessage->id);

        if (!$comment) {
            return;
        }

        if ($this->commentStateMachine->can($comment, 'accept')) {
            $score = $this->spamChecker->getSpamScore($comment, $commentMessage->context);
            $transition = 'accept';

            if (2 === $score) {
                $transition = 'reject_spam';
            } elseif (1 === $score) {
                $transition = 'might_be_spam';
            }

            $this->commentStateMachine->apply($comment, $transition);
            $this->entityManager->flush();

            $this->bus->dispatch($commentMessage);

            return;
        }

        if ($this->commentStateMachine->can($comment, 'publish')
            || $this->commentStateMachine->can($comment, 'publish_ham')) {
            $transition = $this->commentStateMachine->can($comment, 'publish') ? 'publish' : 'publish_ham';

            $this->commentStateMachine->apply($comment, $transition);
            $this->entityManager->flush();
        }

        $this->logger->debug(
            'Dropping comment message',
            ['comment' => $comment->getId(), 'status' => $comment->getStatus()],
        );
    }
}