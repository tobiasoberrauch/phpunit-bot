<?php

namespace Tob\PhpUnitBot\Io;

/**
 * Class SourceFile
 *
 * PHP Version 7
 *
 * @category  PHP
 * @package   Tob\PhpUnitBot\Io
 * @author    Simplicity Trade GmbH <development@simplicity.ag>
 * @copyright 2014-2018 Simplicity Trade GmbH
 * @license   Proprietary http://www.simplicity.ag
 */
class SourceFile
{
    /** @var string */
    protected $sourceFilePath;

    /**
     * SourceFile constructor.
     *
     * @param string $sourceFilePath
     */
    public function __construct(string $sourceFilePath)
    {
        $this->sourceFilePath = $sourceFilePath;
    }

    /**
     * @return string
     */
    public function getClassName() : string
    {
        $fp = fopen($this->sourceFilePath, 'rb');
        $buffer = '';

        while (!feof($fp)) {
            $buffer .= fread($fp, 512);
            if (preg_match('/class\s+(\w+)?/', $buffer, $matches)) {

                return $matches[1];
            }
        }

    }

    /**
     * @return string
     */
    public function getNamespace() : string
    {
        $fp = fopen($this->sourceFilePath, 'rb');
        $buffer = '';
        while (!feof($fp)) {
            $buffer .= fread($fp, 512);
            if (preg_match('/namespace\s+(.*)?\;/', $buffer, $matches)) {

                return $matches[1];
            }
        }
    }

    /**
     * @return string
     */
    public function getFullClassName() : string
    {
        return $this->getNamespace() . "\\" . $this->getClassName();
    }
}