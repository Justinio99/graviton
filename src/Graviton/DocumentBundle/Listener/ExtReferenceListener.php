<?php
/**
 * generate url from raw db data
 *
 * Here we get the raw structure that has been hydrated for $ref link cases
 * by doctrine and replace it with a route generated by the symfony router.
 * We do this in it's own listener due to the fact that there is no way that
 * we can inject anything useable into the default odm hydrator and it looks
 * rather futile to hack it so we can use our own custom hydration code.
 */

namespace Graviton\DocumentBundle\Listener;

use Graviton\DocumentBundle\Service\ExtReferenceConverterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  http://opensource.org/licenses/gpl-license.php GNU Public License
 * @link     http://swisscom.ch
 */
class ExtReferenceListener
{
    /**
     * @var ExtReferenceConverterInterface
     */
    private $converter;

    /**
     * @var array
     */
    private $fields;

    /**
     * @var Request
     */
    private $request;

    /**
     * construct
     *
     * @param ExtReferenceConverterInterface $converter Extref converter
     * @param array                          $fields    map of fields to process
     * @param RequestStack                   $requests  request
     */
    public function __construct(ExtReferenceConverterInterface $converter, array $fields, RequestStack $requests)
    {
        $this->converter = $converter;
        $this->fields = $fields;
        $this->request = $requests->getCurrentRequest();
    }

    /**
     * add a rel=self Link header to the response
     *
     * @param FilterResponseEvent $event response listener event
     *
     * @return void
     */
    public function onKernelResponse(FilterResponseEvent $event)
    {
        $content = trim($event->getResponse()->getContent());

        if (!$event->isMasterRequest() || empty($content)) {
            return;
        }

        $type = $event->getResponse()->headers->get('Content-Type');
        if (substr(strtolower($type), 0, 16) !== 'application/json') {
            return;
        }

        $data = json_decode($event->getResponse()->getContent());
        if (is_array($data)) {
            $data = array_map([$this, 'mapItem'], $data);
        } elseif (is_object($data)) {
            $data = $this->mapItem($data);
        }

        $event->getResponse()->setContent(json_encode($data));
    }

    /**
     * apply single mapping
     *
     * @param mixed $item item to apply mapping to
     *
     * @return array
     */
    private function mapItem($item)
    {
        if (!array_key_exists($this->request->attributes->get('_route'), $this->fields)) {
            return $item;
        }
        foreach ($this->fields[$this->request->attributes->get('_route')] as $field) {
            $item = $this->mapField($item, $field);
        }

        return $item;
    }

    /**
     * recursive mapper for embed-one fields
     *
     * @param mixed  $item  item to map
     * @param string $field name of field to map
     *
     * @return array
     */
    private function mapField($item, $field)
    {
        if (is_array($item)) {
            if ($field === '0') {
                $item = array_map([$this, 'convertToUrl'], $item);
            } elseif (strpos($field, '0.') === 0) {
                $subField = substr($field, 2);

                $item = array_map(
                    function ($subItem) use ($subField) {
                        return $this->mapField($subItem, $subField);
                    },
                    $item
                );
            }
        } elseif (is_object($item)) {
            if (($pos = strpos($field, '.')) !== false) {
                $topLevel = substr($field, 0, $pos);
                $subField = substr($field, $pos + 1);

                if (isset($item->$topLevel)) {
                    $item->$topLevel = $this->mapField($item->$topLevel, $subField);
                }
            } elseif (isset($item->$field)) {
                $item->$field = $this->convertToUrl($item->$field);
            }
        }

        return $item;
    }

    /**
     * Convert extref to URL
     *
     * @param string $ref JSON encoded extref
     * @return string
     */
    private function convertToUrl($ref)
    {
        try {
            $ref = json_decode($ref, true);
            return $this->converter->getUrl($ref);
        } catch (\InvalidArgumentException $e) {
            return '';
        }
    }
}
