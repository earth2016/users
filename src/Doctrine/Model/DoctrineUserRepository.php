<?php

namespace Infra\Doctrine\Model;

use Doctrine\DBAL\Connection;
use Domain\Exception\UserNotFoundException;
use Domain\Model\User;
use Domain\Model\UserRepository;
use Domain\Model\UserUID;

/**
 * Class DoctrineUserRepository
 */
class DoctrineUserRepository implements UserRepository
{
    /**
     * @var Connection
     */
    private $connection;

    /**
     * DbalUserRepository constructor.
     *
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * @inheritDoc
     */
    public function put(User $user): void
    {
        $exists = true;
        $userUID = $user->getUID();
        try {
            $this->getByUID($userUID);
        } catch (UserNotFoundException $e) {
            $exists = false;
        }


        if (!$exists) {

            $this
                ->connection
                ->createQueryBuilder()
                ->insert('users')
                ->values(
                    [
                        'id' => '?',
                        'name' => '?',
                        'age' => '?',
                    ]
                )
                ->setParameters(
                    [
                        $userUID->getId(),
                        $user->getName(),
                        $user->getAge(),
                    ]
                )
                ->execute();

            return;
        }

        $this
            ->connection
            ->createQueryBuilder()
            ->update('users')
            ->set('name', ':name')
            ->set('age', ':age')
            ->where('id = :id')
            ->setParameters([
                'id' => $userUID->getId(),
                'name' => $user->getName(),
                'age' => $user->getAge()
            ])
            ->execute();
    }

    /**
     * @inheritDoc
     */
    public function getByUID(UserUID $userUUID): User
    {
        $data = $this
            ->connection
            ->createQueryBuilder()
            ->select('*')
            ->from('users', 'u')
            ->where('u.id = :id')
            ->setParameter('id', $userUUID->getId())
            ->execute()
            ->fetch();

        if (empty($data)) {
            throw new UserNotFoundException('User not found.');
        }

        return new User(
            new UserUID((string) $data['id']),
            (string) $data['name'],
            (int) $data['age']
        );
    }

    /**
     * @inheritDoc
     */
    public function delete(UserUID $userUid): void
    {
        $result = $this
            ->connection
            ->createQueryBuilder()
            ->delete('users')
            ->where('id = :id')
            ->setParameter('id', $userUid->getId())
            ->execute();

        if (0 === $result) {
            throw new UserNotFoundException('User not found.');
        }
    }
}
