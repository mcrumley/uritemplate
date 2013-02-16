<?php
/*
 * This file is part of the UriTemplate package.
 *
 * (c) Michael Crumley <m@michaelcrumley.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace UriTemplate;

use Exception;

class UriTemplateException extends Exception
{
    private $errorStrings;
    public function __construct($message, array $messageArgs = array(), array $errorStrings = array())
    {
        parent::__construct(vsprintf($message, $messageArgs));
        $this->errorStrings = $errorStrings;
    }

    /**
     * Gets the errors that caused a template expansion to fail.
     *
     * @return  array  List of parser errors
     */
    public function getErrors() {
        return $this->errorStrings;
    }
}
