<?php

/** @noinspection PhpElementIsNotAvailableInCurrentPhpVersionInspection */

declare(strict_types=1);

namespace OpenTelemetry\Context;

use function assert;
use function class_exists;
use const E_USER_WARNING;
use Fiber;
use function trigger_error;

/**
 * @internal
 *
 * @phan-file-suppress PhanUndeclaredClassReference
 * @phan-file-suppress PhanUndeclaredClassMethod
 */
final class FiberBoundContextStorage implements ContextStorageInterface, ExecutionContextAwareInterface
{
    public function __construct(private readonly ContextStorageInterface&ExecutionContextAwareInterface $storage)
    {
    }

    public function fork(int|string $id): void
    {
        $this->storage->fork($id);
    }

    public function switch(int|string $id): void
    {
        $this->storage->switch($id);
    }

    public function destroy(int|string $id): void
    {
        $this->storage->destroy($id);
    }

    public function scope(): ?ContextStorageScopeInterface
    {
        $this->checkFiberMismatch();

        if (($scope = $this->storage->scope()) === null) {
            return null;
        }

        return new FiberBoundContextStorageScope($scope);
    }

    public function current(): ContextInterface
    {
        $this->checkFiberMismatch();

        return $this->storage->current();
    }

    public function attach(ContextInterface $context): ContextStorageScopeInterface
    {
        $scope = $this->storage->attach($context);
        assert(class_exists(Fiber::class, false));
        $scope[Fiber::class] = Fiber::getCurrent();

        return new FiberBoundContextStorageScope($scope);
    }

    private function checkFiberMismatch(): void
    {
        $scope = $this->storage->scope();
        assert(class_exists(Fiber::class, false));
        if ($scope && $scope[Fiber::class] !== Fiber::getCurrent()) {
            trigger_error('Fiber context switching not supported', E_USER_WARNING);
        }
    }
}
