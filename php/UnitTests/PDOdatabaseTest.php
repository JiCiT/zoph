<?php
/**
 * Test the database classes
 *
 * This file is part of Zoph.
 *
 * Zoph is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 * 
 * Zoph is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with Zoph; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 *
 * @package ZophUnitTest
 * @author Jeroen Roos
 */

/**
 * Test class that tests the database classes
 * @package ZophUnitTest
 * @author Jeroen Roos
 */
class PDOdatabaseTest extends ZophDataBaseTestCase {
    /**
     * Create queries
     * @dataProvider getQueries();
     */
    public function testCreateQuery($table, $fields, $exp_sql) {
        if(is_array($fields)) {
            $sql=(string) new query($table, $fields);
        } else {
            $sql=(string) new query($table);
        }
        $this->assertEquals($exp_sql, $sql);
    }

    /**
     * Run queries
     * @dataProvider getQueries();
     */
    public function testRunQuery($table, $fields, $exp_sql) {
        // not used
        $exp_sql=null;
        if(is_array($fields)) {
            $result=db::query(new query($table, $fields));
        } else {
            $result=db::query(new query($table));
        }
        $this->assertInstanceOf("PDOStatement", $result);
    }

    public function getQueries() {
        return array(
            array("photos", array("photo_id"), "SELECT zoph_photos.photo_id FROM zoph_photos;"),
            array("photos", null, "SELECT * FROM zoph_photos;"),
            array("photos", array("photo_id", "name"), 
                "SELECT zoph_photos.photo_id, zoph_photos.name FROM zoph_photos;")
        );
    }

}