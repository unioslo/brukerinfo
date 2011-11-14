<?php
// Copyright 2011 University of Oslo, Norway
// 
// This file is part of Cerebrum.
// 
// Cerebrum is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// 
// Cerebrum is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.
// 
// You should have received a copy of the GNU General Public License
// along with Cerebrum. If not, see <http://www.gnu.org/licenses/>.

/**
 * BofhForm specific to UiO's brukerinfo.
 */

class BofhFormUiO extends BofhForm
{
    public function __construct($name = null, $method = 'POST', $action = null,
                                $target = null, $attr = null, $track = true)
    {
        parent::__construct($name, $method, $action, $target, $attr, $track);
        if (!$this->getAttribute('class')) {
            $this->setAttribute('class', 'app-form');
        }
    }
}
?>
