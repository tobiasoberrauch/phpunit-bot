<?php

namespace Tob\PhpUnitBot\Config;

use Zend\Config\Config;

/**
 * Class BotConfig
 *
 * PHP Version 7
 *
 * @category  PHP
 * @package   Tob\PhpUnitBot\Config
 * @author    Simplicity Trade GmbH <development@simplicity.ag>
 * @copyright 2014-2017 Simplicity Trade GmbH
 * @license   Proprietary http://www.simplicity.ag
 */
class BotConfig extends Config
{
    /**
     * @return string
     */
    public function getAuthor() : string
    {
        return $this->get('author');
    }

    /**
     * @return string
     */
    public function getCopyright() : string
    {
        return $this->get('copyright');
    }

    /**
     * @return string
     */
    public function getLicence() : string
    {
        return $this->get('licence');
    }
}