<?php
/**
 * Graviton\I18nBundle\Document\Translation
 */

namespace Graviton\I18nBundle\Document;

/**
 * @author   List of contributors <https://github.com/libgraviton/graviton/graphs/contributors>
 * @license  https://opensource.org/licenses/MIT MIT License
 * @link     http://swisscom.ch
 */
class Translation
{
    private $id;
    private $language;
    private $original;
    private $localized;

    /**
     * get Id
     *
     * @return mixed Id
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * set Id
     *
     * @param mixed $id id
     *
     * @return void
     */
    public function setId($id)
    {
        $this->id = $id;
    }

    /**
     * get Language
     *
     * @return mixed Language
     */
    public function getLanguage()
    {
        return $this->language;
    }

    /**
     * set Language
     *
     * @param mixed $language language
     *
     * @return void
     */
    public function setLanguage($language)
    {
        $this->language = $language;
    }

    /**
     * get Original
     *
     * @return mixed Original
     */
    public function getOriginal()
    {
        return $this->original;
    }

    /**
     * set Original
     *
     * @param mixed $original original
     *
     * @return void
     */
    public function setOriginal($original)
    {
        $this->original = $original;
    }

    /**
     * get Localized
     *
     * @return mixed Localized
     */
    public function getLocalized()
    {
        return $this->localized;
    }

    /**
     * set Localized
     *
     * @param mixed $localized localized
     *
     * @return void
     */
    public function setLocalized($localized)
    {
        $this->localized = $localized;
    }
}
