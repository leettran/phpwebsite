<?php

define ("DBPAGER_DEFAULT_LIMIT", 5);

/**
 * DB Pager differs from other paging methods in that it applies
 * limits and store the object results. Other pagers require you
 * to pull all the data at once. 
 * This pager pulls only the data it needs for display.
 */

class DBPager {
  /**
   * Name of the class used
   */
  var $class        = NULL;

  /**
   * The current rows to display.
   */
  var $current_rows = NULL;

  /**
   * Object rows pulled from DB
   */
  var $object_rows  = NULL;

  /**
   * Name of the module using list
   * Needed for template purposes
   */ 
  var $module       = NULL;

  var $toggles      = NULL;
  
  var $_methods     = NULL;

  var $_classVars   = NULL;

  var $extra_tags   = NULL;

  /**
   * Template file name and directory
   */
  var $template     = NULL;

  /**
   * Limit of rows to pull from db
   */
  var $limit        = DBPAGER_DEFAULT_LIMIT;


  var $limitList    = array(5, 10, 25);

  var $searchColumn = NULL;

  /**
   * Which column to order by
   */
  var $orderby      = NULL;

  var $orderby_dir  = NULL;

  var $link         = NULL;

  var $search       = NULL;

  /**
   * Total number of rows in database
   */
  var $total_rows   = NULL;

  /**
   * Total number of pages needed to display data
   */
  var $total_pages  = NULL;

  /**
   * Database object
   */
  var $db           = NULL;

  var $current_page = 1;

  var $methods   = array();

  var $error;

  function DBPager($table, $class){
    $this->db = & new PHPWS_DB($table);
    if (PEAR::isError($this->db)){
      $this->error = $this->db;
      $this->db = NULL;
    }

    if (class_exists($class))
      $this->class = $class;
    else {
      $this->error = PHPWS_Error::get(PHPWS_CLASS_NOT_EXIST, "core", "DB_Pager::DBPager", $class);
      PHPWS_Error::log($this->error);
      return;
    }

    $this->_methods = get_class_methods($class);
    $this->_classVars = array_keys(get_class_vars($class));

    if (isset($_REQUEST['page']))
      $this->current_page = (int)$_REQUEST['page'];

    if (isset($_REQUEST['limit']))
      $this->limit = (int)$_REQUEST['limit'];

    if (isset($_REQUEST['orderby']))
      $this->orderby = preg_replace("/\W/", "", $_REQUEST['orderby']);

    if (isset($_REQUEST['orderby_dir']))
      $this->orderby_dir = preg_replace("/\W/", "", $_REQUEST['orderby_dir']);

    if (isset($_REQUEST['search']) && !empty($_REQUEST['search']))
      $this->search = preg_replace("/\W/", "", $_REQUEST['search']);

  }

  function setSearch($column){
    if (ctype_alnum($column) && $this->db->isTableColumn($column))
      $this->searchColumn = $column;
  }

  function addToggle($toggle){
    $this->toggles[] = $toggle;
  }

  function setLink($link){
    $this->link = $link;
  }

  function setModule($module){
    $this->module = $module;
  }

  function setTemplate($template){
    $this->template = $template;
  }

  function setMethod($column_name, $substitute, $variable=NULL){
    $this->methods[$column_name]['method'] = $substitute;
    $this->methods[$column_name]['variable'] = $variable;
  }

  function getLinkEnd(){
    $values[] = "page=" .  $this->current_page;
    $values[] = "limit=" . $this->limit;
    if (isset($this->orderby)){
      $values[] = "orderby=" . $this->orderby;

      if (isset($this->orderby_dir))
	$values[] = "orderby_dir=" . $this->orderby_dir;
    }
    
    return implode("&amp;", $values);
  }

  function addWhere($column, $value, $operator=NULL, $conj=NULL, $group=NULL){
    return $this->db->addWhere($column, $value, $operator, $conj, $group);
  }

  function addTags($tags){
    $this->extra_tags = $tags;
  }

  function getLimit(){
    $start = ($this->current_page - 1) * $this->limit;
    return $start . "," . $this->limit; 
  }

  function getTotalRows(){
    $test = $this->db->addColumn("*", FALSE, TRUE);
    $result = $this->db->select("one");
    $this->db->resetColumns();
    return $result;
  }

  function initialize(){
    if (isset($this->search) && isset($this->searchColumn))
      $this->addWhere($this->searchColumn, $this->search, "REGEXP");

    $count = $this->getTotalRows();
    if (PEAR::isError($count))
      return $count;

    $this->total_rows = &$count;
    $this->total_pages = ceil($this->total_rows / $this->limit);
    if ($this->current_page > $this->total_pages)
      $this->current_page = $this->total_pages;

    if ($this->limit > 0)
      $this->db->setLimit($this->getLimit());

    if (isset($this->orderby))
      $this->db->addOrder($this->orderby . " " . $this->orderby_dir);

    $result = $this->db->getObjects($this->class);

    if (PEAR::isError($result))
      return $result;

    $this->object_rows = &$result;

  }

  function getPageLinks(){
    if ($this->total_pages < 1)
      return PHPWS_Error::get(DBPAGER_NO_TOTAL_PAGES, "core", "DBPager::getPageLinks");

    for ($i=1; $i <= $this->total_pages; $i++){
      $values = $this->getLinkValues();
      $values['page'] = "page=$i";

      if ($this->current_page != $i)
	$pages[] = "<a href=\"" . $this->link . "&amp;" . implode("&amp;", $values) . "\">$i</a>\n";
      else
	$pages[] = $i;
    }

    return implode(" ", $pages);
  }

  function getSortButtons(&$template){
    foreach ($this->_classVars as $varname){
      $values = $this->getLinkValues();
      $buttonname = $varname . "_SORT";

      $values['orderby'] = "orderby=$varname";

      if ($this->orderby == $varname){
	if ($this->orderby_dir == "desc"){
	  unset($values['orderby_dir']);
	  $button = "<img src=\"images/core/list/up_pointer.png\" border=\"0\" />";
	} elseif ($this->orderby_dir =="asc") {
	  $values['orderby_dir'] = "orderby_dir=desc";
	  $button = "<img src=\"images/core/list/down_pointer.png\" border=\"0\" />";
	} else {
	  $button = "<img src=\"images/core/list/sort_none.png\" border=\"0\" />";
	  $values['orderby_dir'] = "orderby_dir=asc";
	}

      } else {
	$button = "<img src=\"images/core/list/sort_none.png\" border=\"0\" />";
	$values['orderby_dir'] = "orderby_dir=asc";
      }

      $link = "<a href=\"" . $this->link . "&amp;" . implode("&amp;", $values) . "\">$button</a>";

      $template[strtoupper($buttonname)] = $link;
    }
    return $template;
  }

  function getLinkValues(){
    $values['page'] = "page=" . $this->current_page;
    $values['limit'] = "limit=" . $this->limit;

    if (isset($this->search))
      $values['search'] = "search=" . $this->search;

    if (isset($this->orderby)){
      $values['orderby'] = "orderby=" . $this->orderby;
      if (isset($this->orderby_dir))
	$values['orderby_dir'] = "orderby_dir=" . $this->orderby_dir;
    }

    return $values;
  }

  function getLimitList(){
    foreach ($this->limitList as $limit){
      $values = $this->getLinkValues();
      $values['limit'] = "limit=$limit";
      $links[] = "<a href=\"" . $this->link . "&amp;" . implode("&amp;", $values) . "\">$limit</a>";
    }

    return implode(" ", $links);
  }


  function getPageRows(){
    $count = 0;

    if (!isset($this->object_rows))
      return NULL;

    foreach ($this->object_rows as $object){
      foreach ($this->_classVars as $varname){
	if (isset($this->methods[$varname])){
	  $funcName = strtolower($this->methods[$varname]['method']);
	  if (isset($this->methods[$varname]['variable']))
	    $variable = $this->methods[$varname]['variable'];
	  else
	    $variable = NULL;
	}
	else {
	 $variable =  $funcName = NULL;
	}

	if (in_array($funcName, $this->_methods)){
	  if (isset($variable))
	    $template[$count][strtoupper($varname)] = $object->{$funcName}($variable);
	  else
	    $template[$count][strtoupper($varname)] = $object->{$funcName}();
	}
	else
	  $template[$count][strtoupper($varname)] = $object->{$varname};
      }

      if (isset($this->rowTags)){
	foreach ($this->rowTags as $tagName=>$methodCall){
	  $result = call_user_func(array($methodCall['class'], $methodCall['method']), $object);
	  $template[$count][strtoupper($tagName)] = $result;
	}
      }

      $count++;
    }

    return $template;
  }

  function addRowTag($tag, $class, $method){
    if (!class_exists($class))
      exit("Class does not exist.");

    $classMethods = get_class_methods($class);

    if (!in_array(strtolower($method), $classMethods))
      exit("Method not in class");

    $this->rowTags[$tag] = array("class"=>$class, "method"=>$method);
  }

  function getPageDrop(){
    for ($i = 1; $i <= $this->total_pages; $i++)
      $page_list[$i] = $i;

    $form = & new PHPWS_Form("page_list");
    $form->setMethod("get");
    $this->_setHiddenVars($form);
    $form->addSelect("page", $page_list);
    $form->setExtra("page", "onchange=\"this.form.submit()\"");
    if (isset($_REQUEST['page']))
      $form->setMatch("page", (int)$_REQUEST['page']);
    if (!javascriptEnabled())
      $form->addSubmit("go", _("Go"));
    $template = $form->getTemplate();
    return implode("\n", $template);
  }


  function getSearchBox(){
    $form = & new PHPWS_Form("search_list");
    $form->setMethod("get");
    $this->_setHiddenVars($form, FALSE);
    $form->addText("search", $this->search);
    $form->setLabel("search", _("Search"));
    $template = $form->getTemplate();
    return implode("\n", $template);
  }

  function _setHiddenVars(&$form, $addSearch=TRUE){
    $link = str_replace("index.php?", "", $this->link);
    $link_list = explode("&", html_entity_decode($link));
    foreach ($link_list as $var){
      $i = explode("=", $var);
      if ($i[0] == "authkey")
	continue;
      $form->addHidden($i[0], $i[1]);
    }

    $form->addHidden("limit", $this->limit);
    if ($addSearch == TRUE && isset($this->search))
      $form->addHidden("search", $this->search);
  }

  function get(){
    $result = $this->initialize();

    if (PEAR::isError($result))
      return $result;

    if (!isset($this->module))
      return PHPWS_Error::get(DBPAGER_MODULE_NOT_SET, "core", "DBPager::get()");

    if (!isset($this->template))
      return PHPWS_Error::get(DBPAGER_TEMPLATE_NOT_SET, "core", "DBPager::get()");

    if ($this->total_rows < 1)
      return NULL;

  
    $template['PAGE_LABEL']  = _("Page");
    $template['LIMIT_LABEL'] = _("Limit");
    $template['PAGE_DROP'] = $this->getPageDrop();

    $total_row = $this->total_rows;

    $start_row = ( ($this->current_page - 1) * $this->limit ) + 1;
    $end_row   = $this->current_page * $this->limit;
    if ($end_row > $total_row)
      $end_row = $total_row;

    $template['TOTAL_ROWS']  = "$start_row - $end_row " . _("of") . " $total_row";

    $pages = $this->getPageLinks();
    if (PEAR::isError($pages))
      return $pages;

    $template['PAGES']     = $pages;

    $rows = $this->getPageRows();
    $template['LIMITS']    = $this->getLimitList();

    if (isset($this->searchColumn))
      $template['SEARCH']    = $this->getSearchBox();

    $tpl = new PHPWS_Template($this->module);
    $result = $tpl->setFile($this->template);
    if (PEAR::isError($result))
      return $result;

    if (isset($this->toggles))
      $max_tog = count($this->toggles);

    $count = 0;
    if (isset($rows)){
      foreach ($rows as $rowitem){
	if (isset($max_tog)){
	  $rowitem['TOGGLE'] = $this->toggles[$count];
	  $count++;
	  
	  if ($count >= $max_tog)
	    $count = 0;
	} else
	  $rowitem['TOGGLE'] = NULL;
	
	$tpl->setCurrentBlock("listrows");
	$tpl->setData($rowitem);
	$tpl->parseCurrentBlock();
      }
      
      $this->getSortButtons($template);
    }

    
    if (isset($this->extra_tags)){
      foreach ($this->extra_tags as $key=>$value)
	$template[$key] = $value;
    }

    $tpl->setData($template);
    return $tpl->get();

  }

}

?>