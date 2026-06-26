<?php

class modStockReorder extends DolibarrModules
{
	public function __construct($db)
	{
		$this->db = $db;
		$this->numero = 523100;
		$this->name = preg_replace('/^mod/i', '', get_class($this));
		$this->rights_class = 'stockreorder';
		$this->const_name = 'MAIN_MODULE_'.strtoupper($this->name);
		$this->module_parts = array('triggers' => 1);
		$this->family = "stock";
		$this->description = "StockReorderDescription";
		$this->editor_name = "Yacine Oukkal";
		$this->editor_url = "";
		$this->version = "1.0.0";
		$this->const_active = 1;
		$this->picto = "stock";
		$this->dirs = array();
		$this->config_page_url = array("setup.php@stockreorder");
		$this->depends = array("modStock", "modFournisseur");
		$this->requiredby = array();
		$this->phpmin = array(7, 3);
		$this->need_instanciation = 0;
		$this->const = array();
		$this->const[0] = array(
			0 => 'STOCKREORDER_DAYS_N',
			1 => 'chaine',
			2 => '30',
			3 => 'Number of days to check for sales quantity',
			4 => 1
		);
		$this->tabs = array();
	}

	public function init($options = '')
	{
		$sql = array();
		$result = $this->_init($sql, $options);
		return $result;
	}

	public function remove($options = '')
	{
		$sql = array();
		$result = $this->_remove($sql, $options);
		return $result;
	}
}
