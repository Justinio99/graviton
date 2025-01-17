<?php
/**
 * Listener for validation exceptions
 */

namespace Graviton\ExceptionBundle\Listener;

use Graviton\JsonSchemaBundle\Exception\ValidationException;
use Graviton\JsonSchemaBundle\Exception\ValidationExceptionError;
use Graviton\SchemaBundle\Constraint\ConstraintUtils;
use JsonSchema\Entity\JsonPointer;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpFoundation\Response;

/**
 * Listener for validation exceptions
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class ValidationExceptionListener
{

    /**
     * @var ConstraintUtils
     */
    private $constraintUtils;

    /**
     * set constraint utils
     *
     * @param ConstraintUtils $utils utils
     *
     * @return void
     */
    public function setConstraintUtils(ConstraintUtils $utils)
    {
        $this->constraintUtils = $utils;
    }

    /**
     * Handle the exception and send the right response
     *
     * @param ExceptionEvent $event Event
     *
     * @return void
     */
    public function onKernelException(ExceptionEvent $event)
    {
        if (($exception = $event->getThrowable()) instanceof ValidationException) {
            $content = $this->getErrorMessages($exception->getErrors());
            $event->setResponse(
                new JsonResponse($content, Response::HTTP_BAD_REQUEST)
            );
        }
    }

    /**
     * @param ValidationExceptionError[] $errors errors
     *
     * @return array
     */
    private function getErrorMessages(array $errors)
    {
        $content = [];
        foreach ($errors as $error) {
            $property = $error->getProperty();
            if ($property instanceof JsonPointer && $this->constraintUtils instanceof ConstraintUtils) {
                $property = $this->constraintUtils->getNormalizedPathFromPointer($property);
            }
            $content[] = [
                'propertyPath' => $property,
                'message' => $error->getMessage(),
            ];
        }
        return $content;
    }
}
