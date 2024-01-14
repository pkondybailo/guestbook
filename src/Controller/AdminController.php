<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Message\CommentMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Workflow\Registry;

#[Route(path: '/admin')]
class AdminController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $bus,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    #[Route(path: '/http-cache/{uri<.*>}', methods: ['POST'])]
    public function purgeHttpCache(
        KernelInterface $kernel,
        Request $request,
        string $uri,
        StoreInterface $store
    ): Response {
        if ('prod' === $kernel->getEnvironment()) {
            return new Response('KO', 400);
        }

        $store->purge($request->getSchemeAndHttpHost().'/'.$uri);

        return new Response('DONE');
    }

    #[Route(path: '/comment/review/{id}', name: 'review_comment', methods: ['GET'])]
    public function reviewComment(
        Request $request,
        Comment $comment,
        Registry $registry,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $accepted = !$request->query->get('reject');
        $machine = $registry->get($comment);

        if ($machine->can($comment, 'publish')) {
            $transition = $accepted ? 'publish' : 'reject';
        } elseif ($machine->can($comment, 'publish_ham')) {
            $transition = $accepted ? 'publish_ham' : 'reject_ham';
        } else {
            return new Response('Comment already reviewed or not in the right status.');
        }

        $machine->apply($comment, $transition);
        $this->entityManager->flush();

        if ($accepted) {
            $reviewUrl = $urlGenerator->generate(
                'review_comment',
                ['id' => $comment->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );
            $this->bus->dispatch(new CommentMessage($comment->getId(), $reviewUrl));
        }

        return $this->render('admin/review.html.twig', [
            'transition' => $transition,
            'comment'    => $comment,
        ]);
    }
}