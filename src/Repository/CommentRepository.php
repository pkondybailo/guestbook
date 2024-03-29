<?php

namespace App\Repository;

use App\Entity\Comment;
use App\Entity\Conference;
use App\Enum\CommentStatusEnum;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Comment>
 *
 * @method Comment|null find($id, $lockMode = null, $lockVersion = null)
 * @method Comment|null findOneBy(array $criteria, array $orderBy = null)
 * @method Comment[]    findAll()
 * @method Comment[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class CommentRepository extends ServiceEntityRepository
{
    public const PAGINATOR_PER_PAGE = 2;
    private const DAYS_BEFORE_REJECTED_REMOVAL = 7;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Comment::class);
    }

    public function add(Comment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function countOldRejected(): int
    {
        return $this
            ->getOldRejectedQueryBuilder()
            ->select('COUNT(c.id)')
            ->getQuery()
            ->getSingleScalarResult();
    }

    public function deleteOldRejected(): int
    {
        return $this
            ->getOldRejectedQueryBuilder()
            ->delete()
            ->getQuery()
            ->execute();
    }

    public function getCommentPaginator(Conference $conference, int $offset): Paginator
    {
        $query = $this->createQueryBuilder('comment')
            ->andWhere('comment.conference = :conference')
            ->andWhere('comment.status = :status')
            ->setParameter('conference', $conference)
            ->setParameter('status', CommentStatusEnum::Published->value)
            ->orderBy('comment.createdAt', 'DESC')
            ->setMaxResults(self::PAGINATOR_PER_PAGE)
            ->setFirstResult($offset)
            ->getQuery();

        return new Paginator($query);
    }

    public function remove(Comment $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    private function getOldRejectedQueryBuilder(): QueryBuilder
    {
        return $this
            ->createQueryBuilder('c')
            ->andWhere('c.status = :status_rejected or c.status = :status_spam')
            ->andWhere('c.createdAt < :date')
            ->setParameters([
                'status_rejected' => CommentStatusEnum::Rejected->value,
                'status_spam'     => CommentStatusEnum::Spam->value,
                'date'            => new DateTimeImmutable(-self::DAYS_BEFORE_REJECTED_REMOVAL.' days'),
            ]);
    }
}
