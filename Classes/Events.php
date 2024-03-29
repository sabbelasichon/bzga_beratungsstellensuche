<?php

declare(strict_types=1);

/*
 * This file is part of the "bzga_beratungsstellensuche" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Bzga\BzgaBeratungsstellensuche;

/**
 * @author Sebastian Schreiber
 */
final class Events
{
    /**
     * Signal is emitted before the import starts.
     * So you are able to extend the importer with your own logic beforehand
     *
     * @var string
     */
    public const PRE_IMPORT_SIGNAL = 'preImport';

    /**
     * Signal is emitted after the import process.
     *
     * @var string
     */
    public const POST_IMPORT_SIGNAL = 'postImport';

    /**
     * Signal is emitted before the mapping of the converter starts.
     * So you are able to add new key => value pairs to the mapping process
     *
     * @var string
     */
    public const SIGNAL_MAP_NAMES = 'mapNames';

    /**
     * Signal is emitted in the entry controller. So you can extend the view with your own variables.
     *
     * @var string
     */
    public const LIST_ACTION_SIGNAL = 'entry.list.action';

    /**
     * Signal is emitted in the entry controller. So you can extend the view with your own variables.
     *
     * @var string
     */
    public const SHOW_ACTION_SIGNAL = 'entry.show.action';

    /**
     * Signal is emitted in the entry controller. So you can extend the view with your own variables.
     *
     * @var string
     */
    public const FORM_ACTION_SIGNAL = 'entry.form.action';

    /**
     * Signal is emitted in the entry controller. This signal is especially useful if you would like to extend the demand object
     * and change some configuration for the PropertyMapper
     *
     * @var string
     */
    public const INITIALIZE_ACTION_SIGNAL = 'entry.initialize.action';

    /**
     * Signal is emitted in the serializer constructor so you can add some custom normalizers to the serializer
     *
     * @var string
     */
    public const ADDITIONAL_NORMALIZERS_SIGNAL = 'serializer.normalizers';

    /**
     * Signal is emitted in the normalizer classes, so you can add some more callback functions to the normalizer.
     * Especially useful if you would like to add more relations for entries or filter something out etc.
     * Have a look at the EntryNormalizer to see the callbacks in action.
     *
     * @see \Bzga\BzgaBeratungsstellensuche\Domain\Serializer\Normalizer\EntryNormalizer::prepareForDenormalization()
     * @var string
     */
    public const DENORMALIZE_CALLBACKS_SIGNAL = 'denormalizer.callbacks';

    /**
     * Signal is emitted in the repository class if truncation is going on.
     * Have a look at AbstractBaseRepository to see what is going on.
     *
     * @see \Bzga\BzgaBeratungsstellensuche\Domain\Repository\AbstractBaseRepository::truncateAll()
     * @var string
     */
    public const TABLE_TRUNCATE_ALL_SIGNAL = 'repository.truncateall';

    /**
     * Signal is emitted in the entry repository class if the method deleteByUid is called
     * Have a look at EntryRepository to see what is going on.
     *
     * @see \Bzga\BzgaBeratungsstellensuche\Domain\Repository\EntryRepository::deleteByUid()
     * @var string
     */
    public const REMOVE_ENTRY_FROM_DATABASE_SIGNAL = 'repository.remove.entry';
}
