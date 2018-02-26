<?php
/**
 * OriginRecordInterface
 */

namespace Graviton\RestBundle\Model;

/**
 * OriginRecordInterface
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
interface RecordOriginInterface
{
    /**
     * Get recordOrigin
     *
     * @return string $recordOrigin
     */
    public function getRecordOrigin();

    /**
     * Can record origin be modified
     *
     * @return bool true|false
     */
    public function isRecordOriginModifiable();
}
