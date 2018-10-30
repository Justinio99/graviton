<?php
/**
 * Document for representing Apps.
 */

namespace Graviton\CoreBundle\Document;

/**
 * App
 *
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class App
{
    /**
     * @var string app id
     */
    protected $id;

    /**
     * @var string app Name
     */
    protected $name;

    /**
     * @var boolean show app in menu
     */
    protected $showInMenu;

    /**
     * @var int sort order
     */
    protected $order;

    /**
     * Set id
     *
     * @param string $id id for new document
     *
     * @return self
     */
    public function setId($id)
    {
        $this->id = $id;

        return $this;
    }

    /**
     * Get id
     *
     * @return string $id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name name used for display
     *
     * @return self
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string $name
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set showInMenu
     *
     * @param boolean $showInMenu show app in menu
     *
     * @return self
     */
    public function setShowInMenu($showInMenu)
    {
        $this->showInMenu = $showInMenu;

        return $this;
    }

    /**
     * Get showInMenu
     *
     * @return boolean $showInMenu
     */
    public function getShowInMenu()
    {
        return $this->showInMenu;
    }

    /**
     * Get order
     *
     * @return int order
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Set order
     *
     * @param int $order order
     *
     * @return void
     */
    public function setOrder($order)
    {
        $this->order = $order;
    }
}
