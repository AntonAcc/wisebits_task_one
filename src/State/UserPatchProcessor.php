<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class UserPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $em,
        private PersistProcessor $persistProcessor
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $id = $uriVariables['id'] ?? null;

        if (!$id) {
            throw new \InvalidArgumentException('Missing user ID for update.');
        }

        $this->em->beginTransaction();

        try {
            // SELECT FOR UPDATE
            $user = $this->em->createQueryBuilder()
                ->select('u')
                ->from(User::class, 'u')
                ->where('u.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult();

            if (!$user) {
                throw new NotFoundHttpException('User not found.');
            }

            $result = $this->persistProcessor->process($user, $operation, $uriVariables, $context);

            $this->em->commit();

            return $result;
        } catch (\Throwable $e) {
            $this->em->rollback();
            throw $e;
        }
    }
}