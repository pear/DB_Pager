<?php
//
//	Pear DB Pager - Retrieve and return information of database
//  result sets
//
//	Copyright (C) 2001  Tomas Von Veschler Cox <cox@idecnet.com>
//
//  This library is free software; you can redistribute it and/or
//  modify it under the terms of the GNU Lesser General Public
//  License as published by the Free Software Foundation; either
//  version 2.1 of the License, or (at your option) any later version.
//
//  This library is distributed in the hope that it will be useful,
//  but WITHOUT ANY WARRANTY; without even the implied warranty of
//  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
//  Lesser General Public License for more details.
//
//  You should have received a copy of the GNU Lesser General Public
//  License along with this library; if not, write to the Free Software
//  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307 USA
//
//
// $Id$

require_once 'PEAR.php';
require_once 'DB.php';

/**
* This class handles all the stuff needed for displaying paginated results
* from a database query of Pear DB, in a very easy way.
* Documentation and examples of use, can be found in:
* http://vulcanonet.com/soft/pager/
*
* @version 0.5.2
* @author Tomas V.V.Cox <cox@idecnet.com>
* @see http://vulcanonet.com/soft/pager/
*/

class DB_Pager extends PEAR
{

    /**
    * Constructor
    *
    * @param object $res  A DB_result object from Pear_DB
    * @param int    $from  The row to start fetching
    * @param int    $limit  How many results per page
    * @param int    $numrows Pager will automatically
    *    find this param if is not given. If your Pear_DB backend extension
    *    doesn't support numrows(), you can manually calculate it
    *    and supply later to the constructor
    */
    function Pager (&$res, $from, $limit, $numrows = null)
    {
        $this->res = $res;
        $this->from = $from;
        $this->limit = $limit;
        $this->numrows = $numrows;
    }

    /**
    * Calculates all the data needed by Pager to work
    *
    * @return mixed An assoc array with all the data (see getData)
    *    or DB_Error on error
    * @see Pager::getData
    */
    function build ()
    {
        // if there is no numrows given, calculate it
        if ($this->numrows === null) {
            $this->numrows = $this->res->numrows();
            if (DB::isError($this->numrows)) {
                return $this->numrows;
            }
        }
        $data = $this->getData($this->from, $this->limit, $this->numrows);
        if (DB::isError($data)) {
            return $data;
        }
        $this->current = $this->from - 1;
        $this->top = $data['to'];
        return $data;
    }

    function fetchRow($mode=DB_FETCHMODE_DEFAULT)
    {
        $this->current++;
        if ($this->current >= $this->top) {
            return null;
        }
        return $this->res->fetchRow($mode, $this->current);
    }

    function fetchInto(&$arr, $mode=DB_FETCHMODE_DEFAULT)
    {
        $this->current++;
        if ($this->current >= $this->top) {
            return null;
        }
        return $this->res->fetchInto($arr, $mode, $this->current);
    }

    /*
    * Gets all the data needed to paginate results
    * This is an associative array with the following
    * values filled in:
    *
    * array(
    *    'current' => X,    // current page you are
    *    'numrows' => X,    // total number of results
    *    'next'    => X,    // row number where next page starts
    *    'prev'    => X,    // row number where prev page starts
    *    'remain'  => X,    // number of results remaning *in next page*
    *    'numpages'=> X,   // total number of pages
    *    'from'    => X,    // the row to start fetching
    *    'to'      => X,      // the row to stop fetching
    *    'limit'   => X,   // How many results per page
    *    'pages'   => array(    // assoc with page "number => start row"
    *                1 => X,
    *                2 => X,
    *                3 => X
    *                )
    *    );
    * @param int $from    The row to start fetching
    * @param int $limit   How many results per page
    * @param int $numrows Number of results from query
    *
    * @return array associative array with data or DB_error on error
    *
    */
    function &getData($from, $limit, $numrows)
    {
        if (empty($numrows) || ($numrows < 0)) {
            return null;
        }
        $from = (empty($from)) ? 0 : $from;

        if ($limit <= 0) {
            return PEAR::raiseError (null, 'wrong "limit" param', null,
                                null, null, 'DB_Error', true);
        }

        // Total number of pages
        $pages = ceil($numrows/$limit);
        $data['numpages'] = $pages;

        // Build pages array
        $data['pages'] = array();
        for ($i=1; $i <= $pages; $i++) {
            $offset = $limit * ($i-1);
            $data['pages'][$i] = $offset;
            // $from must point to one page
            if ($from == $offset) {
                // The current page we are
                $data['current'] = $i;
            }
        }
        if (!isset($data['current'])) {
            return PEAR::raiseError (null, 'wrong "from" param', null,
                                null, null, 'DB_Error', true);
        }

        // Prev link
        $prev = $from - $limit;
        $data['prev'] = ($prev >= 0) ? $prev : null;

        // Next link
        $next = $from + $limit;
        $data['next'] = ($next < $numrows) ? $next : null;

        // Results remaining in next page & Last row to fetch
        if ($data['current'] == $pages) {
            $data['remain'] = 0;
            $data['to'] = $numrows;
        } else {
            if ($data['current'] == ($pages - 1)) {
                $data['remain'] = $numrows - ($limit*($pages-1));
            } else {
                $data['remain'] = $limit;
            }
            $data['to'] = $data['current'] * $limit;
        }
        $data['numrows'] = $numrows;
        $data['from'] = $from + 1;
        $data['limit'] = $limit;

        return $data;
    }
}
?>
