<?php
/**
 * Pagesets are a set of pages.
 * You can associate an album, category, person or place with a pageset.
 * This means that the first page in the set is shown when calling this album, etc.
 * Through a pagination link, one can go to the other pages.
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
 * @author Jeroen Roos
 * @package Zoph
 */

/**
 * The pageset class groups a set of pages in a certain order
 * @author Jeroen Roos
 * @package Zoph
 */
class pageset extends zophTable {
    /** @var string The name of the database table */
    protected static $tableName="pageset";
    /** @var array List of primary keys */
    protected static $primaryKeys=array("pageset_id");
    /** @var array Fields that may not be empty */
    protected static $notNull=array("title");
    /** @var bool keep keys with insert. In most cases the keys are set
     *  by the db with auto_increment */
    protected static $keepKeys = false;
    /** @var string URL for this class */
    protected static $url="pageset.php?pageset_id=";

    /**
     * Create pageset
     * @param int If existing pageset is to be pulled from the db, the id has to be given
     * @return pageset
     */
    public function __construct($id = 0) {
        parent::__construct($id);
        $this->set("date", "now()");
    }

    /**
     * Update existing pageset in db
     */
    public function update() {
        $this->set("timestamp", "now()");
        parent::update();
        $this->lookup();
    }

    /**
     * Delete pageset from db
     * Also delete page-pageset relations
     */
    public function delete() {
        if (!$this->get("pageset_id")) {
            return;
        }
        parent::delete(array("pages_pageset"));
    }

    /**
     * Get an array of information to be displayed for this pageset
     */
    public function getDisplayArray() {
        return array(
            translate("title") => $this->get("title"),
            translate("date") => $this->get("date"),
            translate("updated") => $this->get("timestamp"),
            translate("created by", false) => $this->getUser()->getLink(),
            translate("show original page") => translate($this->get("show_orig"), 0),
            translate("position of original") => translate($this->get("orig_pos"), 0)
        );
    }

    /**
     * Get a dropdown to select what to do with the original (zoph default) page to
     * be displayed
     */
    public function getOriginalDropdown() {
        return template::createPulldown("show_orig", $this->get("show_orig"),
            array(
                "never" => translate("Never", 0),
                "first" => translate("On first page", 0),
                "last" => translate("On last page", 0),
                "all" => translate("On all pages", 0)
            )
        );
    }

    /**
     * Get the pages in this pageset
     * @param int Specific page to get instead of all
     */
    public function getPages($pagenum=null) {
        $sql = "select page_id from " . DB_PREFIX . "pages_pageset" .
            " where pageset_id = " . $this->getId() .
            " order by page_order";
        if ($pagenum) {
            $sql.=" limit " . escape_string($pagenum) . ", 1";
        }
        return page::getRecordsFromQuery($sql);
    }

    /**
     * Get the number of pages in this pageset
     * @param page Page to remove
     */
    public function getPageCount() {
        $sql = "select count(page_id) from " . DB_PREFIX . "pages_pageset" .
            " where pageset_id = " . $this->get("pageset_id");
        return static::getCountFromQuery($sql);
    }

    /**
     * Add a page to this set
     * @param page Page to add
     * @todo If the page already exists in this pageset, it fails silently
     *       because, at this moment a page cannot be more than once in a pageset
     *       Someday, this should either give a nice error or this limitation
     *       should be removed.
     */
    public function addPage(page $page) {
        if (!$page->getOrder($this)) {
            $sql = "insert into " . DB_PREFIX . "pages_pageset " .
                "values(" . $this ->get("pageset_id") . ", " .
                escape_string($page->getId()) . ", " .
                ($this->getMaxOrder() + 1) . ")";
            query($sql, "Could not add page to pageset");
        }
    }

    /**
     * Remove a page from this pageset
     * @param page Page to remove
     */
    public function removePage(page $page) {
        $sql = "delete from " . DB_PREFIX . "pages_pageset " .
            "where pageset_id=" . $this ->getId() . " and " .
            "page_id=" . $page->getId();
        query($sql, "Could not remove page from pageset");
    }

    /**
     * Move a page up in the order list
     * @param page Page to move up
     */
    public function moveUp(page $page) {
        $order=$page->getOrder($this);
        if ($order>=2) {
            $prevorder=$this->getPrevOrder($order);

            /** @todo This messes up ALL page orders, not just for this pageset! */
            $sql="update zoph_pages_pageset set page_order=" . $order .
                " where page_order=" . $prevorder;
            query($sql, "Could not change order");
            $sql="update zoph_pages_pageset set page_order=" . $prevorder .
                " where page_id=" . $page->getId();
            query($sql, "Could not change order");
        }
    }

    /**
     * Move a page down in the order list
     * @param page Page to move down
     */
    public function moveDown(page $page) {
        $order=$page->getOrder($this);
        $max=$this->getMaxOrder();
        if ($order!=0 && $order<$max) {
            $nextorder=$this->getNextOrder($order);
            /** @todo This messes up ALL page orders, not just for this pageset! */
            $sql="update zoph_pages_pageset set page_order=" . $order .
                " where page_order=" . $nextorder;
            query($sql, "Could not change order");
            $sql="update zoph_pages_pageset set page_order=" . $nextorder .
                " where page_id=" . $page->GetId();
            query($sql, "Could not change order");

        }
    }

    /**
     * Get the highest used page_order value for this pageset
     * @return int maximum page_order
     */
    private function getMaxOrder() {
        $sql = "select max(page_order) from " . DB_PREFIX . "pages_pageset" .
            " where pageset_id=" . $this->getId();
        $result=query($sql, "Could not get max order");
        return intval(result($result, 0));
    }

    /**
     * Get Next order
     * If pages have been deleted, the page_order field may no longer
     * be nicely numbered 1, 2, 3, etc. but there may be holes in the list
     * so this function and getPrevOrder() determine the next or previous
     * value of page_order.
     */
    private function getNextOrder($order) {
        $sql = "select min(page_order) from " . DB_PREFIX . "pages_pageset" .
            " where pageset_id=" . $this->getId() .
            " and page_order>" . $order;
        $result=query($sql, "Could not get max order");
        return intval(result($result, 0));
    }

    /**
     * Get previous order
     * If pages have been deleted, the page_order field may no longer
     * be nicely numbered 1, 2, 3, etc. but there may be holes in the list
     * so this function and getiNextOrder() determine the next or previous
     * value of page_order.
     */
    private function getPrevOrder($order) {
        $sql = "select max(page_order) from " . DB_PREFIX . "pages_pageset" .
            " where pageset_id=" . $this->getId() .
            " and page_order<" . $order;
        $result=query($sql, "Could not get max order");
        return intval(result($result, 0));
    }

    /**
     * Get the user who created this pageset
     * @return user the user
     */
    public function getUser() {
        $user = new user($this->get("user"));
        $user->lookup();
        return $user;
    }

    /**
     * Get table of pagesets
     * @param array pagesets to put in the table (default: all)
     * @return block template block with all pagesets
     */
    public static function getTable(array $pagesets=null) {
        if (!$pagesets) {
           $pagesets=pageset::getAll();
        }
        $lpagesets=array();
        foreach ($pagesets as $pageset) {
            $pageset->lookup();
            $lpagesets[]=$pageset;
        }
        return new block("pagesets", array(
            "pagesets" => $lpagesets
        ));
    }
}