<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Doctrine\Common\State\PersistProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Input\UserUpdateDto;
use App\Entity\User;
use Doctrine\DBAL\LockMode;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

readonly class UserPatchProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private PersistProcessor $persistProcessor
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof UserUpdateDto) {
            throw new BadRequestHttpException(sprintf('Expected data to be an instance of %s, got %s', UserUpdateDto::class, get_debug_type($data)));
        }

        $id = $uriVariables['id'] ?? null;
        if (!$id) {
            throw new \InvalidArgumentException('Missing user ID for update.');
        }

        $repo = $this->entityManager->getRepository(User::class);

        $conflictEmail = $repo->findOneBy(['email' => $data->email]);
        if ($conflictEmail && $conflictEmail->getId() !== $id) {
            throw new \DomainException(sprintf('Email "%s" is already used.', $data->email));
        }

        $conflictName = $repo->findOneBy(['name' => $data->name]);
        if ($conflictName && $conflictName->getId() !== $id) {
            throw new \DomainException(sprintf('Name "%s" is already used.', $data->name));
        }

        $this->entityManager->beginTransaction();

        try {
            /** @var User|null $user */
            $user = $this->entityManager->createQueryBuilder()
                ->select('u')
                ->from(User::class, 'u')
                ->where('u.id = :id')
                ->setParameter('id', $id)
                ->getQuery()
                ->setLockMode(LockMode::PESSIMISTIC_WRITE)
                ->getOneOrNullResult();

            if (!$user) {
                $this->entityManager->rollback();
                throw new NotFoundHttpException('User not found.');
            }

            if ($data->name !== null && $data->name !== $user->getName()) {
                $user->setName($data->name);
            }

            if ($data->email !== null && $data->email !== $user->getEmail()) {
                $user->setEmail($data->email);
            }
            
            if ($data->notes !== $user->getNotes()) {
                $user->setNotes($data->notes);
            }

            $result = $this->persistProcessor->process($user, $operation, $uriVariables, $context);

            $this->entityManager->commit();

            return $result;
        } catch (\Throwable $e) {
            if ($this->entityManager->getConnection()->isTransactionActive()) {
                $this->entityManager->rollback();
            }
            throw $e;
        }
    }
}