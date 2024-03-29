<?php

namespace App\Controller;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Form\CommentFormType;
use App\Message\CommentMessage;
use App\Repository\CommentRepository;
use App\Repository\ConferenceRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\NotifierInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use function random_bytes;

class ConferenceController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly Environment $twig,
        private readonly MessageBusInterface $messageBus,
    ) {
    }

    #[Route(path: '/{_locale<%app.supported_locales%>}/conference-header', name: 'conference_header')]
    public function conferenceHeader(ConferenceRepository $conferenceRepository): Response
    {
        $response = new Response(
            $this->twig->render('conference/header.html.twig', ['conferences' => $conferenceRepository->findAll()]),
        );
        // $response->setSharedMaxAge(3600);

        return $response;
    }

    #[Route(path: '/', name: 'homepage_no_locale')]
    public function indexNoLocale(): Response
    {
        return $this->redirectToRoute('homepage', ['_locale' => 'en']);
    }

    #[Route(path: '/{_locale<%app.supported_locales%>}', name: 'homepage')]
    public function index(ConferenceRepository $conferenceRepository): Response
    {
        $response = new Response(
            $this->twig->render('conference/index.html.twig', [
                'conferences' => $conferenceRepository->findAll(),
            ])
        );
        // $response->setSharedMaxAge(3600);

        return $response;
    }

    #[Route(path: '/{_locale<%app.supported_locales%>}/conference/{slug}', name: 'conference')]
    public function show(
        Request $request,
        Conference $conference,
        CommentRepository $commentRepository,
        string $photoDir,
        NotifierInterface $notifier,
        UrlGeneratorInterface $urlGenerator,
    ): Response {
        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $comment->setConference($conference);

            if ($photo = $form['photo']->getData()) {
                $filename = bin2hex(random_bytes(6).'.'.$photo->guessExtension());

                try {
                    $photo->move($photoDir, $filename);
                } catch (FileException $exception) {
                }

                $comment->setPhotoFilename($filename);
            }

            $this->entityManager->persist($comment);
            $this->entityManager->flush();

            $context = [
                'user_ip'    => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referrer'   => $request->headers->get('referrer'),
                'permalink'  => $request->getUri(),
            ];

            $reviewUrl = $urlGenerator->generate(
                'review_comment',
                ['id' => $comment->getId()],
                UrlGeneratorInterface::ABSOLUTE_URL,
            );
            $this->messageBus->dispatch(new CommentMessage($comment->getId(), $reviewUrl, $context));

            $notifier->send(
                new Notification(
                    'Thank you for your feedback! Your comment will be posted after moderation.',
                    ['browser'],
                ),
            );

            return $this->redirectToRoute('conference', ['slug' => $conference->getSlug()]);
        }

        if ($form->isSubmitted()) {
            $notifier->send(
                new Notification('Can you check your submission? There are some problems with it.', ['browser']),
            );
        }

        $offset = max(0, $request->query->getInt('offset', 0));
        $paginator = $commentRepository->getCommentPaginator($conference, $offset);

        return new Response(
            $this->twig->render('conference/show.html.twig', [
                'conference'   => $conference,
                'comments'     => $paginator,
                'next'         => min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
                'previous'     => $offset - CommentRepository::PAGINATOR_PER_PAGE,
                'comment_form' => $form->createView(),
            ])
        );
    }
}
