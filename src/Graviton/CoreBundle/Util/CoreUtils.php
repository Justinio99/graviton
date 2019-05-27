<?php
/**
 * some utility functions
 */

namespace Graviton\CoreBundle\Util;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class CoreUtils
{

    /**
     * Parses a field list like 'hans,int:hans2,bool:hans3' into an array
     * structure like {{type: string, name: hans},{type: int, name: hans2},{type: bool, name: hans3}}
     *
     * @param string|null $fieldList field list
     *
     * @return array parsed field list
     */
    public static function parseStringFieldList(?string $fieldList)
    {
        if (is_null($fieldList) || empty($fieldList)) {
            return [];
        }

        $list = [];
        $fields = array_map('trim', explode(',', trim($fieldList)));

        foreach ($fields as $field) {
            $valueParts = array_map(
                'trim',
                explode(':', $field)
            );
            if (count($valueParts) == 1) {
                $list[$valueParts[0]] = [
                    'type' => 'string',
                    'name' => $valueParts[0]
                ];
            } elseif (count($valueParts) == 2) {
                $list[$valueParts[1]] = [
                    'type' => $valueParts[0],
                    'name' => $valueParts[1]
                ];
            } else {
                throw new \LogicException('Wrong field list item: '.$field);
            }
        }

        return $list;
    }
}