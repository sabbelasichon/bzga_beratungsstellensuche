<?php


namespace Bzga\BzgaBeratungsstellensuche\Domain\Serializer\Normalizer;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */
use Bzga\BzgaBeratungsstellensuche\Domain\Model\Entry;
use Bzga\BzgaBeratungsstellensuche\Domain\Model\ValueObject\ImageLink;
use Bzga\BzgaBeratungsstellensuche\Domain\Repository\CategoryRepository;
use Bzga\BzgaBeratungsstellensuche\Domain\Serializer\NameConverter\EntryNameConverter;
use SJBR\StaticInfoTables\Domain\Repository\CountryZoneRepository;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactoryInterface;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;
use TYPO3\CMS\Extbase\Persistence\RepositoryInterface;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * @author Sebastian Schreiber
 */
class EntryNormalizer extends GetSetMethodNormalizer
{
    /**
     * @var CountryZoneRepository
     *
     */
    protected $countryZoneRepository;

    /**
     * @var CategoryRepository
     *
     */
    protected $categoryRepository;

    /**
     * EntryNormalizer constructor.
     *
     * @param ClassMetadataFactoryInterface|null $classMetadataFactory
     * @param Dispatcher|null $signalSlotDispatcher
     */
    public function __construct(ClassMetadataFactoryInterface $classMetadataFactory = null, Dispatcher $signalSlotDispatcher = null)
    {
        parent::__construct($classMetadataFactory, new EntryNameConverter([], true, $signalSlotDispatcher));
    }

    /**
     * @param array|object $data
     * @return array
     */
    protected function prepareForDenormalization($data)
    {
        $stateCallback = function ($externalId) {
            return $this->countryZoneRepository->findOneByExternalId($externalId);
        };

        $categoriesCallback = function () {
            return self::convertToObjectStorage($this->categoryRepository, func_get_args());
        };

        $logoCallback = function ($logo) {
            return new ImageLink($logo);
        };

        $this->setDenormalizeCallbacks(
            [
                'state' => $stateCallback,
                'categories' => $categoriesCallback,
                'image' => $logoCallback,
            ]
        );

        return parent::prepareForDenormalization($data);
    }

    /**
     * @param RepositoryInterface $repository
     * @param array $array
     * @param string $method
     * @return ObjectStorage
     */
    public static function convertToObjectStorage(
        RepositoryInterface $repository,
        array $array,
        $method = 'findOneByExternalId'
    ) {
        $objectStorage = new ObjectStorage();
        if (is_array($array[0])) {
            foreach ($array[0] as $key => $item) {
                if (is_array($item)) {
                    foreach ($item as $id) {
                        $object = $repository->{$method}($id);
                        if ($object !== null) {
                            $objectStorage->attach($object);
                        }
                    }
                }
            }
        }

        return $objectStorage;
    }

    public function injectCategoryRepository(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public function injectCountryZoneRepository(CountryZoneRepository $countryZoneRepository)
    {
        $this->countryZoneRepository = $countryZoneRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function supportsDenormalization($data, $type, $format = null)
    {
        return $type === Entry::class;
    }
}
