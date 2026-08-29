<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use App\Domain\Shared\TransactionManagerInterface;
use PDO;
use Throwable;

final class PdoTransactionManager implements TransactionManagerInterface
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    public function run(callable $callback): mixed
    {
        $this->pdo->beginTransaction();

        try {
            $result = $callback();
            $this->pdo->commit();

            return $result;
        } catch (Throwable $throwable) {
            if ($this->pdo->inTransaction()) {
                $this->pdo->rollBack();
            }

            throw $throwable;
        }
    }
}
