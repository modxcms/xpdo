<?php

/**
 * This file is part of the xPDO package.
 *
 * Copyright (c) Jason Coward <jason@opengeek.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace xPDO\Migrations;

final class MigratorResult
{
    public const REPOSITORY_OK = 'ok';
    public const REPOSITORY_MISSING = 'missing';

    /** @var string */
    public $repositoryState;

    /** @var string[] */
    public $applied = [];

    /** @var string[] */
    public $pending = [];

    /** @var string[] applied versions with missing files */
    public $orphaned = [];

    /** @var int|null */
    public $batch = null;

    /** @var string[] */
    public $rolledBack = [];

    /** @var string|null */
    public $created = null;

    /** @var string[] */
    public $messages = [];

    public function __construct(string $repositoryState = self::REPOSITORY_OK)
    {
        $this->repositoryState = $repositoryState;
    }

    public function toArray(): array
    {
        return [
            'repository' => $this->repositoryState,
            'applied' => $this->applied,
            'pending' => $this->pending,
            'orphaned' => $this->orphaned,
            'batch' => $this->batch,
            'rolledBack' => $this->rolledBack,
            'created' => $this->created,
            'messages' => $this->messages,
        ];
    }
}
