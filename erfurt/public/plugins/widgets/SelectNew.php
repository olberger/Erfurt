<?php

/**
  * Erfurt select widget
  *
  * @author Norman Heino <norman@feedface.de>
  * @version $Id$
  */
// TODO: rename to Select
class SelectNew extends Erfurt_Plugin_Widget {
	
	/**
	  * @var currently selected item
	  */
	var $_selected;
	
	public function __construct($elementName = null, $selected = null, $values = null, $config = array()) {
		$defaultConfig = array(
			'cardinalityMax' => 1, 
			'multi'          => false,
			'multiSize'      => 5,
			'emptyLabel'     => 'None'
		);
		parent::__construct($elementName, 
			                $values, 
							// config
							array_merge($defaultConfig, $config)
		);
		
		$this->_selected = $selected;
	}
	
	public function __toString() {
		if (isset($this->_config['start'])) {
			$count = $this->_config['start'] - 1;
			$this->_id = $this->_id . $this->_config['start'];
		} else {
			$count = 0;
		}
		$ret = '<span id="select-container-' . $this->_id . '"';
		if (!empty($this->_config['class'])) {
			$ret .= ' class="' . $this->_config['class'] . '">' . PHP_EOL;
		} else {
			$ret .= ' class="SelectNew">' . PHP_EOL;
		}
		$ret .= $this->getSingleValueHtml();
		$ret .= '</span>' . PHP_EOL;
		
		return $ret;
	}
	
	private function getSingleValueHtml() {
		
		$ret = '<select name="' . $this->_elementName . '"';
		if (empty($this->_config['cardinalityMax']) || $this->_config['cardinalityMax'] > 1) {
			$ret .= ' multiple="multiple" size="' . $this->_config['multiSize'] . '"';
		}
		if (!empty($this->_config['onChange'])) {
			$ret .= ' onchange="' . $this->_config['onChange'] . '"';
		}
		if (!empty($this->_config['readOnly'])) {
			$ret .= ' disabled="disabled"';
		}
		if (!empty($this->_config['class'])) {
			$ret .= ' class="' . $this->_config['class'] . '"';
		} else {
			$ret .= ' class="SelectNew"';
		}
		$ret .= ' id="dtype-' . $this->_id . '">' . PHP_EOL;
		
		foreach ($this->_values as $key => $val) {
			if (is_int($key)) {
				$option = $val;
			} else {
				$option = $key;
			}
			$ret .= '<option value="' . $option . '"';
			if (
			    ($key === $this->_selected)) {
				$ret .= ' selected="selected"';
			}
			$ret .= '>' . $val . '</option>' . PHP_EOL;
		}
		
		$ret .= '</select>' . PHP_EOL;
		
		return $ret;
	}
}

?>