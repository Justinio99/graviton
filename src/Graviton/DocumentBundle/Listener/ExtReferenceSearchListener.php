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
use Graviton\Rql\Event\VisitNodeEvent;
use Graviton\Rql\Node\ElemMatchNode;
use Graviton\RqlParser\Node\Query\AbstractArrayOperatorNode;
use Graviton\RqlParser\Node\Query\AbstractScalarOperatorNode;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class ExtReferenceSearchListener
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
     * construct
     *
     * @param ExtReferenceConverterInterface $converter Extref converter
     * @param array                          $fields    map of fields to process
     */
    public function __construct(ExtReferenceConverterInterface $converter, array $fields)
    {
        $this->converter = $converter;
        $this->fields = $fields;
    }

    /**
     * @param VisitNodeEvent $event node event to visit
     *
     * @return VisitNodeEvent
     */
    public function onVisitNode(VisitNodeEvent $event)
    {
        $node = $event->getNode();
        $documentClassName = $event->getClassName();
        if ($node instanceof AbstractScalarOperatorNode &&
            $this->isExtrefField($documentClassName, $node->getField(), $event->getContext())) {
            $event->setNode($this->processScalarNode($node));
        } elseif ($node instanceof AbstractArrayOperatorNode &&
            $this->isExtrefField($documentClassName, $node->getField(), $event->getContext())) {
            $event->setNode($this->processArrayNode($node));
        }

        return $event;
    }

    /**
     * Process scalar condition
     *
     * @param AbstractScalarOperatorNode $node Query node
     * @return AbstractScalarOperatorNode
     */
    private function processScalarNode(AbstractScalarOperatorNode $node)
    {
        $copy = clone $node;
        $copy->setValue($this->getDbRefValue($node->getValue()));
        return $copy;
    }

    /**
     * Process array condition
     *
     * @param AbstractArrayOperatorNode $node Query node
     * @return AbstractArrayOperatorNode
     */
    private function processArrayNode(AbstractArrayOperatorNode $node)
    {
        $copy = clone $node;
        $copy->setValues(array_map([$this, 'getDbRefValue'], $node->getValues()));
        return $copy;
    }

    /**
     * Get DbRef from extref URL
     *
     * @param string $url Extref URL representation
     *
     * @return \Graviton\DocumentBundle\Entity\ExtReference extref
     */
    private function getDbRefValue($url)
    {
        if ($url === null) {
            return null;
        }

        try {
            return $this->converter->getExtReference($url);
        } catch (\InvalidArgumentException $e) {
            //make up some invalid refs to ensure we find nothing if an invalid url was given
            return [];
        }
    }

    /**
     * Get document field name by query name
     *
     * @param string    $documentClassName document class name
     * @param string    $searchName        Exposed field name from RQL query
     * @param \SplStack $nodeContext       Current node context
     * @return bool
     */
    private function isExtrefField(string $documentClassName, string $searchName, \SplStack $nodeContext)
    {
        if (!isset($this->fields[$documentClassName])) {
            throw new \LogicException(sprintf('Missing "%s" from extref fields map.', $documentClassName));
        }

        $fieldName = $searchName;
        foreach ($nodeContext as $parentNode) {
            if ($parentNode instanceof ElemMatchNode) {
                $fieldName = $parentNode->getField().'..'.$fieldName;
            }
        }

        return in_array(
            strtr($fieldName, ['..' => '.0.']),
            $this->fields[$documentClassName],
            true
        );
    }
}
