<?php

/**
 * Exception thrown when a message is published under an unknown routing key is used.
 */

namespace Graviton\MessageBundle\Exception;

use Exception;

/**
 * Exception thrown when a message is published under an unknown routing key is used.
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.ch
 */
class UnknownRoutingKeyException extends Exception
{

    /**
     * @param string $routingKey The routing key
     */
    public function __construct($routingKey)
    {
        $this->message =
            'Tried to send a message with routing key "' . $routingKey . '", which is unknown.'
            . ' Add it to the list of allowed routing keys to publish such messages.';
    }
}
