<?php

declare(strict_types=1);

namespace App\Extension;

use ApiPlatform\Doctrine\Orm\Extension\QueryCollectionExtensionInterface;
use ApiPlatform\Doctrine\Orm\Extension\QueryItemExtensionInterface;
use ApiPlatform\Doctrine\Orm\Util\QueryNameGeneratorInterface;
use ApiPlatform\Metadata\Operation;
use App\Entity\Comment;
use App\Enum\CommentStatusEnum;
use Doctrine\ORM\QueryBuilder;

final class FilterPublishedCommentsQueryExtension implements
    QueryCollectionExtensionInterface,
    QueryItemExtensionInterface
{
    public function applyToCollection(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        Operation $operation = null,
        array $context = []
    ): void {
        $this->applyFilter($resourceClass, $queryBuilder);
    }

    public function applyToItem(
        QueryBuilder $queryBuilder,
        QueryNameGeneratorInterface $queryNameGenerator,
        string $resourceClass,
        array $identifiers,
        Operation $operation = null,
        array $context = []
    ): void {
        $this->applyFilter($resourceClass, $queryBuilder);
    }

    private function applyFilter(string $resourceClass, QueryBuilder $queryBuilder): void
    {
        if (Comment::class === $resourceClass) {
            $rootAlias = $queryBuilder->getRootAliases()[0];
            $queryBuilder
                ->andWhere($rootAlias.'.status = :status')
                ->setParameter('status', CommentStatusEnum::Published->value);
        }
    }
}
