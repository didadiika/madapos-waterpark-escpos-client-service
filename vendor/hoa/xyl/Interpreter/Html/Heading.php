<?php

/**
 * Hoa
 *
 *
 * @license
 *
 * New BSD License
 *
 * Copyright © 2007-2017, Hoa community. All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *     * Redistributions of source code must retain the above copyright
 *       notice, this list of conditions and the following disclaimer.
 *     * Redistributions in binary form must reproduce the above copyright
 *       notice, this list of conditions and the following disclaimer in the
 *       documentation and/or other materials provided with the distribution.
 *     * Neither the name of the Hoa nor the names of its contributors may be
 *       used to endorse or promote products derived from this software without
 *       specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDERS AND CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 */

namespace Hoa\Xyl\Interpreter\Html;

use Hoa\Xyl;

/**
 * Class \Hoa\Xyl\Interpreter\Html\Heading.
 *
 * The <h1 /> to <h6 /> components.
 *
 * @copyright  Copyright © 2007-2017 Hoa community
 * @license    New BSD License
 */
class Heading extends GenericPhrasing implements Xyl\Element\Executable
{
    /**
     * Attributes description.
     *
     * @var array
     */
    protected static $_attributes        = [
        'for' => parent::ATTRIBUTE_TYPE_LIST
    ];

    /**
     * Attributes mapping between XYL and HTML.
     *
     * @var array
     */
    protected static $_attributesMapping = null;

    /**
     * Pre-execute an element.
     *
     * @return  void
     */
    public function preExecute()
    {
        $this->computeFor();

        return;
    }

    /**
     * Post-execute an element.
     *
     * @return  void
     */
    public function postExecute()
    {
        return;
    }

    /**
     * Compute @for.
     *
     * @return  void
     */
    protected function computeFor()
    {
        if (false === $this->abstract->attributeExists('for')) {
            return;
        }

        $tocs = $this->xpath(
            '//__current_ns:tableofcontents[@id="' .
            implode('" or @id="', $this->abstract->readAttributeAsList('for')) .
            '"]'
        );

        if (empty($tocs)) {
            return;
        }

        foreach ($tocs as $toc) {
            $this->getConcreteElement($toc)->addHeading($this);
        }

        return;
    }

    /**
     * Get the heading level.
     *
     * @return  int
     */
    public function getLevel()
    {
        return (int) substr($this->getName(), -1);
    }
}