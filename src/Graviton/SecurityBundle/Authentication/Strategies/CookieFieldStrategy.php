<?php
/**
 * authentification strategy based on a username cookie
 */

namespace Graviton\SecurityBundle\Authentication\Strategies;

use Graviton\SecurityBundle\Entities\SecurityUser;
use Symfony\Component\HttpFoundation\Request;

/**
 * Class CookieFieldStrategy
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class CookieFieldStrategy extends AbstractHttpStrategy
{
    /** @var string  */
    const COOKIE_FIELD_NAME = 'username';

    /** @var string  */
    const COOKIE_VALUE_CORE_ID = 'finnova_id';

    /** @var string  */
    const CONFIGURATION_PARAMETER_CORE_ID = 'graviton.security.core_id';

    /** @var string  */
    const CONFIGURATION_PARAMETER_USER_ID = 'graviton.security.user_id';

    /** @var string */
    protected $field;

    /**
     * @param string $field cookie field to be examined
     */
    public function __construct($field)
    {
        $this->field = $field;
    }

    /**
     * Applies the defined strategy on the provided request.
     * Value may contain a coma separated string values, we use first as identifier.
     *
     * @param Request $request request to handle
     *
     * @return string
     */
    public function apply(Request $request)
    {
        $bagValue = $this->extractFieldInfo($request->cookies, $this->field);

        // this needs to be available in a later state of the application
        $this->extractAdUsername($request, $bagValue);

        return $this->extractCoreId($request, $bagValue);
    }

    /**
     * Provides the list of registered roles.
     *
     * @return string[] roles
     */
    public function getRoles()
    {
        return [SecurityUser::ROLE_USER];
    }

    /**
     * Finds and extracts the ad username from the cookie.
     *
     * @param Request $request Request stack that controls the lifecycle of requests
     * @param string  $value   The string the value of self::COOKIE_FIELD_NAME shall be extracted from.
     *
     * @return string
     */
    protected function extractAdUsername(Request $request, $value)
    {
        $pattern = "/((?m)(?<=\b".self::COOKIE_FIELD_NAME."=)[^;]*)/i";
        preg_match($pattern, $value, $matches);

        if ($matches) {
            $request->attributes->set(self::CONFIGURATION_PARAMETER_USER_ID, $matches[0]);

            return $matches[0];
        }

        return $value;
    }

    /**
     * Finds and extracts the core system id from tha cookie.
     *
     * @param Request $request Request stack that controls the lifecycle of requests
     * @param string  $text    String to be examined for the core id.
     *
     * @return string
     */
    protected function extractCoreId(Request $request, $text)
    {
        $pattern = "/((?m)(?<=\b".self::COOKIE_VALUE_CORE_ID."=)[^;]*)/i";
        preg_match($pattern, $text, $matches);

        if ($matches) {
            $request->attributes->set(self::CONFIGURATION_PARAMETER_CORE_ID, $matches[0]);

            return $matches[0];
        }

        return $text;
    }
}
