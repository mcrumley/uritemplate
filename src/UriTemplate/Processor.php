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

/**
 * RFC 6570 URI Template processor
 * http://tools.ietf.org/html/rfc6570
 */
class Processor
{
    private $template;
    private $data;
    private $cache;

    /**
     * Create a new template processor.
     *
     * @param string URI Template (optional)
     * @param array  Data (optional)
     */
    public function __construct($template = '', array $data = array())
    {
        $this->setTemplate($template);
        $this->setData($data);
    }

    /**
     * Sets the URI template.
     * @param  string    $template Uri Template
     * @return Processor           Returns $this for method chaining
     */
    public function setTemplate($template)
    {
        $this->template = $template;
        $this->cache = null;
    }
    /**
     * Gets the current URI template.
     * @return string
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * Sets template data.
     * @param  string    $template Uri Template
     * @return Processor           Returns $this for method chaining
     */
    public function setData(array $data)
    {
        $this->data = $data;
        $this->cache = null;
    }
    /**
     * Gets the current template data.
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Processes the template
     * @return string
     */
    public function process()
    {
        if ($this->cache !== null) {
            return $this->cache;
        } else {
            return ($this->cache = UriTemplate::expand($this->template, $this->data));
        }
    }
}
