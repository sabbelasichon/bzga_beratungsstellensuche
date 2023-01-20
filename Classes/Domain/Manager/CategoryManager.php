<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche\Domain\Manager;

use Bzga\BzgaBeratungsstellensuche\Domain\Repository\AbstractBaseRepository;
use Bzga\BzgaBeratungsstellensuche\Domain\Repository\CategoryRepository;

/**
 * @author Sebastian Schreiber
 */
class CategoryManager extends AbstractManager
{
    /**
     * @var CategoryRepository
     */
    protected $repository;

    /**
     * @return CategoryRepository
     */
    public function getRepository(): AbstractBaseRepository
    {
        return $this->repository;
    }

    public function injectRepository(CategoryRepository $repository): void
    {
        $this->repository = $repository;
    }
}
