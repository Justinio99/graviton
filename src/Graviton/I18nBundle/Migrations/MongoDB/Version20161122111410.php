<?php
/**
 * Index migration
 */

namespace Graviton\I18nBundle\Migrations\MongoDB;

use AntiMattr\MongoDB\Migrations\AbstractMigration;
use MongoDB\Database;

/**
 * Migrate domain_1_locale_1_original_1 index
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class Version20161122111410 extends AbstractMigration
{
    /**
     * @var string
     */
    private $collection = 'Translatable';

    /**
     * @return string
     */
    public function getDescription()
    {
        return 'Build id for the new sha1 implementation';
    }

    /**
     * recreate index without unique flag
     *
     * @param Database $db database to migrate
     *
     * @return void
     */
    public function up(Database $db)
    {
    }

    /**
     * re-add unique flag to index
     *
     * @param Database $db database to migrate
     *
     * @return void
     */
    public function down(Database $db)
    {
    }
}
