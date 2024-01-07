<?php

namespace App\MessageHandler;

use App\ImageOptimizer;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\SpamChecker;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Twig\Mime\NotificationEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\Handler\MessageHandlerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\WorkflowInterface;

class CommentMessageHandler implements MessageHandlerInterface
{
    public function __construct(
        private readonly string $adminEmail,
        private readonly MessageBusInterface $bus,
        private readonly CommentRepository $commentRepository,
        private readonly WorkflowInterface $commentStateMachine,
        private readonly EntityManagerInterface $entityManager,
        private readonly ImageOptimizer $imageOptimizer,
        private readonly LoggerInterface $logger,
        private readonly MailerInterface $mailer,
        private readonly string $photoDir,
        private readonly SpamChecker $spamChecker,
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
            $notificationEmail = (new NotificationEmail())
                ->subject('New comment posted')
                ->htmlTemplate('emails/comment_notification.html.twig')
                ->from($this->adminEmail)
                ->to($this->adminEmail)
                ->context(['comment' => $comment]);

            $this->mailer->send($notificationEmail);
        } elseif ($this->commentStateMachine->can($comment, 'optimize')) {
            if ($comment->getPhotoFilename()) {
                $this->imageOptimizer->resize($this->photoDir.'/'.$comment->getPhotoFilename());
            }
            $this->commentStateMachine->apply($comment, 'optimize');
            $this->entityManager->flush();
        }

        $this->logger->debug(
            'Dropping comment message',
            ['comment' => $comment->getId(), 'status' => $comment->getStatus()],
        );
    }
}