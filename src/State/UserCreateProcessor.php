<?php

declare(strict_types=1);

namespace App\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Dto\Input\UserCreateDto;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class UserCreateProcessor implements ProcessorInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): User
    {
        if (!$data instanceof UserCreateDto) {
            throw new BadRequestHttpException(sprintf('Expected data to be an instance of %s, got %s', UserCreateDto::class, get_debug_type($data)));
        }

        $user = new User(
            name: $data->name,
            email: $data->email,
            notes: $data->notes
        );

        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }
} 