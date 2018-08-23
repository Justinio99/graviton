<?php
/**
 * Schema constraint that validates the rules of recordOrigin (and possible exceptions)
 */

namespace Graviton\SchemaBundle\Constraint;

use Graviton\JsonSchemaBundle\Validator\Constraint\Event\ConstraintEventSchema;
use Symfony\Component\PropertyAccess\PropertyAccess;
use JsonSchema\Rfc3339;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class RecordOriginConstraint
{

    /**
     * @var string
     */
    private $recordOriginField;

    /**
     * @var array
     */
    private $recordOriginBlacklist;

    /**
     * @var array
     */
    private $exceptionFieldMap;

    /**
     * @var array
     */
    private $changedObjectPaths = [];

    /**
     * RecordOriginConstraint constructor.
     *
     * @param ConstraintUtils $utils                 Utils
     * @param string          $recordOriginField     name of the recordOrigin field
     * @param array           $recordOriginBlacklist list of recordOrigin values that cannot be modified
     * @param array           $exceptionFieldMap     field map from compiler pass with excluded fields
     */
    public function __construct(
        ConstraintUtils $utils,
        $recordOriginField,
        array $recordOriginBlacklist,
        array $exceptionFieldMap
    ) {
        $this->utils = $utils;
        $this->recordOriginField = $recordOriginField;
        $this->recordOriginBlacklist = $recordOriginBlacklist;
        $this->exceptionFieldMap = $exceptionFieldMap;
    }

    /**
     * Checks the recordOrigin rules and sets error in event if needed
     *
     * @param ConstraintEventSchema $event event class
     *
     * @return void
     */
    public function checkRecordOrigin(ConstraintEventSchema $event)
    {
        $currentRecord = $this->utils->getCurrentEntity([$this->recordOriginField]);
        $data = $event->getElement();

        // if no recordorigin set on saved record; we let it through
        if (is_null($currentRecord) || !isset($currentRecord->{$this->recordOriginField})) {
            // we have no current record.. but make sure user doesn't want to send the banned recordOrigin
            if (isset($data->{$this->recordOriginField}) &&
                !is_null($data->{$this->recordOriginField}) &&
                in_array($data->{$this->recordOriginField}, $this->recordOriginBlacklist)
            ) {
                $event->addError(
                    sprintf(
                        'Creating documents with the %s field having a value of %s is not permitted.',
                        $this->recordOriginField,
                        implode(', ', $this->recordOriginBlacklist)
                    ),
                    $this->recordOriginField
                );
                return;
            }

            return;
        }

        $recordOrigin = $currentRecord->{$this->recordOriginField};

        // not in the blacklist? can also go through..
        if (!in_array($recordOrigin, $this->recordOriginBlacklist)) {
            return;
        }

        // ok, user is trying to modify an object with blacklist recordorigin.. let's check fields
        $schema = $event->getSchema();
        $isAllowed = true;

        if (!isset($schema->{'x-documentClass'})) {
            // this should never happen but we need to check. if schema has no information to *check* our rules, we
            // MUST deny it in that case..
            $event->addError(
                'Internal error, not enough schema information to validate recordOrigin rules.',
                $this->recordOriginField
            );
            return;
        }

        $documentClass = $schema->{'x-documentClass'};

        if (!isset($this->exceptionFieldMap[$documentClass])) {
            // if he wants to edit on blacklist, but we have no exceptions, also deny..
            $isAllowed = false;
        } else {
            // so to check our exceptions, we remove it from both documents (the stored and the clients) and compare
            $exceptions = $this->exceptionFieldMap[$documentClass];

            $accessor = PropertyAccess::createPropertyAccessorBuilder()
                ->enableMagicCall()
                ->getPropertyAccessor();

            // now really get the whole object
            $storedObject = $this->utils->getCurrentEntity();
            $userObject = clone $data;

            // convert all datetimes to UTC so we compare eggs with eggs
            $userObject = $this->convertDatetimeToUTC($userObject, $schema, new \DateTimeZone('UTC'));
            $storedObject = $this->convertDatetimeToUTC($storedObject, $schema, new \DateTimeZone('UTC'));

            foreach ($exceptions as $fieldName) {
                if ($accessor->isWritable($storedObject, $fieldName)) {
                    $accessor->setValue($storedObject, $fieldName, null);
                } else {
                    $this->addProperties($fieldName, $storedObject);
                }
                if ($accessor->isWritable($userObject, $fieldName)) {
                    $accessor->setValue($userObject, $fieldName, null);
                } else {
                    $this->addProperties($fieldName, $userObject);
                }
            }

            // so now all unimportant fields were set to null on both - they should match if rest is untouched ;-)
            if ($userObject != $storedObject) {
                $isAllowed = false;
                $this->changedObjectPaths = [];
                $this->getChangedObjectPaths($userObject, $storedObject);
                $this->getChangedObjectPaths($storedObject, $userObject);
                $this->changedObjectPaths = array_keys($this->changedObjectPaths);
            }
        }

        if (!$isAllowed) {
            $error = sprintf(
                'Prohibited modification attempt on record with %s of %s.',
                $this->recordOriginField,
                implode(', ', $this->recordOriginBlacklist)
            );
            // if there are recordCoreExceptions we can be more explicit
            if (isset($this->exceptionFieldMap[$documentClass]) && !empty($this->changedObjectPaths)) {
                $error.= sprintf(
                    ' You tried to change (%s), but you can only change (%s) by recordOriginException.',
                    implode(', ', $this->changedObjectPaths),
                    implode(', ', $this->exceptionFieldMap[$documentClass])
                );
            }
            $event->addError($error, $this->recordOriginField);
        }

        return;
    }

    /**
     * Recursive convert date time to UTC
     * @param object        $object   Form data to be verified
     * @param object        $schema   Entity schema
     * @param \DateTimeZone $timezone to be converted to
     * @return object
     */
    private function convertDatetimeToUTC($object, $schema, \DateTimeZone $timezone)
    {
        foreach ($schema->properties as $field => $property) {
            if (isset($property->format) && $property->format == 'date-time' && isset($object->{$field})) {
                $dateTime = Rfc3339::createFromString($object->{$field});
                $dateTime->setTimezone($timezone);
                $object->{$field} = $dateTime->format(\DateTime::ISO8601);
            } elseif (isset($property->properties) && isset($object->{$field})) {
                $object->{$field} = $this->convertDatetimeToUTC($object->{$field}, $property, $timezone);
            }
        }
        return $object;
    }

    /**
     * recursive helperfunction that walks through two arrays/objects of the same structure,
     * compares the values and writes the paths containining differences into the $this->changedObjectPaths
     *
     * @param mixed $object  the first of the datastructures to compare
     * @param mixed $compare the second of the datastructures to compare
     * @param array $path    array of current path
     * @return void
     */
    private function getChangedObjectPaths($object, $compare, $path = [])
    {
        $compare = (array) $compare;
        $object = (array) $object;
        foreach ($object as $fieldName => $value) {
            $path[] = $fieldName;
            if ((is_object($value) || is_array($value)) && array_key_exists($fieldName, $compare)) {
                $this->getChangedObjectPaths($value, $compare[$fieldName], $path);
            } elseif (!array_key_exists($fieldName, $compare) || $value!=$compare[$fieldName]) {
                $this->changedObjectPaths[implode('.', $path)] = $value;
            }
            array_pop($path);
        }
    }

    /**
     * if the user provides properties that are in the exception list but not on the currently saved
     * object, we try here to synthetically add them to our representation. and yes, this won't support
     * exclusions in an array structure for the moment, but that is also not needed for now.
     *
     * @param string $expression the expression
     * @param object $obj        the object
     *
     * @return object the modified object
     */
    private function addProperties($expression, $obj)
    {
        $val = &$obj;
        $parts = explode('.', $expression);
        $numParts = count($parts);

        if ($numParts == 1) {
            $val->{$parts[0]} = null;
        } else {
            $iteration = 1;
            foreach ($parts as $part) {
                if ($iteration < $numParts) {
                    if (!isset($val->{$part}) || !is_object($val->{$part})) {
                        $val->{$part} = new \stdClass();
                    }
                    $val = &$val->{$part};
                } else {
                    $val->{$part} = null;
                }
                $iteration++;
            }
        }

        return $val;
    }
}
