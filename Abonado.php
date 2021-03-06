<?php
/*+**********************************************************************************
 * The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 ************************************************************************************/
require_once 'data/CRMEntity.php';
require_once 'data/Tracker.php';

class Abonado extends CRMEntity {
	public $db;
	public $log;

	public $table_name = 'vtiger_abonado';
	public $table_index= 'abonadoid';
	public $column_fields = array();

	/** Indicator if this is a custom module or standard module */
	public $IsCustomModule = true;
	public $HasDirectImageField = false;
	/**
	 * Mandatory table for supporting custom fields.
	 */
	public $customFieldTable = array('vtiger_abonadocf', 'abonadoid');
	// related_tables variable should define the association (relation) between dependent tables
	// FORMAT: related_tablename => array(related_tablename_column[, base_tablename, base_tablename_column[, related_module]] )
	// Here base_tablename_column should establish relation with related_tablename_column
	// NOTE: If base_tablename and base_tablename_column are not specified, it will default to modules (table_name, related_tablename_column)
	// Uncomment the line below to support custom field columns on related lists
	// var $related_tables = array('vtiger_MODULE_NAME_LOWERCASEcf' => array('MODULE_NAME_LOWERCASEid', 'vtiger_MODULE_NAME_LOWERCASE', 'MODULE_NAME_LOWERCASEid', 'MODULE_NAME_LOWERCASE'));

	/**
	 * Mandatory for Saving, Include tables related to this module.
	 */
	public $tab_name = array('vtiger_crmentity', 'vtiger_abonado', 'vtiger_abonadocf');

	/**
	 * Mandatory for Saving, Include tablename and tablekey columnname here.
	 */
	public $tab_name_index = array(
		'vtiger_crmentity' => 'crmid',
		'vtiger_abonado'   => 'abonadoid',
		'vtiger_abonadocf' => 'abonadoid',
	);

	/**
	 * Mandatory for Listing (Related listview)
	 */
	public $list_fields = array(
		/* Format: Field Label => array(tablename => columnname) */
		// tablename should not have prefix 'vtiger_'
		'Abonado No'=> array('abonado' => 'abonado_no'),
		'Numabonado'=> array('abonado' => 'numabonado'),
		'Account Id'=> array('abonado' => 'accid'),
		'Active'=> array('abonado' => 'active'),
		'Assigned To' => array('crmentity' => 'smownerid')
	);
	public $list_fields_name = array(
		/* Format: Field Label => fieldname */
		'Abonado No'=> 'abonado_no',
		'Numabonado'=> 'numabonado',
		'Account Id'=> 'accid',
		'Active'=> 'active',
		'Assigned To' => 'smownerid'
	);

	// Make the field link to detail view from list view (Fieldname)
	public $list_link_field = 'numabonado';

	// For Popup listview and UI type support
	public $search_fields = array(
		/* Format: Field Label => array(tablename => columnname) */
		// tablename should not have prefix 'vtiger_'
		'Abonado No'=> array('abonado' => 'abonado_no'),
		'Numabonado'=> array('abonado' => 'numabonado'),
		'Account Id'=> array('abonado' => 'accid'),
		'Active'=> array('abonado' => 'active'),
		'Assigned To'=> array('crmentity' => 'smownerid'),
	);
	public $search_fields_name = array(
		/* Format: Field Label => fieldname */
		'Abonado No'=> 'abonado_no',
		'Numabonado'=> 'numabonado',
		'Account Id'=> 'accid',
		'Active'=> 'active',
		'Assigned To' => 'smownerid'
	);

	// For Popup window record selection
	public $popup_fields = array('numabonado', 'accid', 'active', 'smownerid');

	// Placeholder for sort fields - All the fields will be initialized for Sorting through initSortFields
	public $sortby_fields = array();

	// For Alphabetical search
	public $def_basicsearch_col = 'numabonado';

	// Column value to use on detail view record text display
	public $def_detailview_recname = 'numabonado';

	// Required Information for enabling Import feature
	public $required_fields = array('numabonado'=>1);

	// Callback function list during Importing
	public $special_functions = array('set_import_assigned_user');

	public $default_order_by = 'numabonado';
	public $default_sort_order='ASC';
	// Used when enabling/disabling the mandatory fields for the module.
	// Refers to vtiger_field.fieldname values.
	public $mandatory_fields = array('numabonado', 'accid', 'active');

	public function save_module($module) {
		global $adb;
		if ($this->HasDirectImageField) {
			$this->insertIntoAttachment($this->id, $module);
		}
		if (empty($this->column_fields['optel'])) {
			$rs = $adb->pquery('select phone_work from vtiger_users where id = ?', array($this->column_fields['assigned_user_id']));
			$this->column_fields['optel'] = $adb->query_result($rs, 0, 'phone_work');
			$adb->pquery('update vtiger_abonado set optel=? where abonadoid=?', array($this->column_fields['optel'], $this->id));
		}
	}

	/**
	 * Invoked when special actions are performed on the module.
	 * @param String Module name
	 * @param String Event Type (module.postinstall, module.disabled, module.enabled, module.preuninstall)
	 */
	public function vtlib_handler($modulename, $event_type) {
		require_once 'include/utils/utils.php';
		global $adb;

		if ($event_type == 'module.postinstall') {
			//Add Abonado Module to Customer Portal
			global $adb;
			$this->setModuleSeqNumber('configure', $modulename, 'abn-', '0000001');

			include_once 'vtlib/Vtiger/Module.php';

			//Showing Abonado module in the related modules in the More Information Tab
			$abonadoInstance = Vtiger_Module::getInstance('Abonado');
			$abonadoLabel = 'Abonado';

			$accountInstance = Vtiger_Module::getInstance('Accounts');
			$accountInstance->setRelatedlist($abonadoInstance, $abonadoLabel, array('ADD'), 'get_dependents_list');

			$productInstance = Vtiger_Module::getInstance('Products');
			$productInstance->setRelatedlist($abonadoInstance, $abonadoLabel, array('ADD'), 'get_dependents_list');

			$assetInstance = Vtiger_Module::getInstance('Assets');
			$assetInstance->setRelatedlist($abonadoInstance, $abonadoLabel, array('ADD'), 'get_dependents_list');

			$cbZoneInstance = Vtiger_Module::getInstance('cbZone');
			if ($cbZoneInstance) {
				$blockInstance = VTiger_Block::getInstance('LBL_ZONE_INFORMATION', $cbZoneInstance);
				$field = new Vtiger_Field();
				$field->name = 'abonadoid';
				$field->label= 'Abonado';
				$field->table = $cbZoneInstance->basetable;
				$field->column = 'abonadoid';
				$field->columntype = 'INT(11)';
				$field->uitype = 10;
				$field->displaytype = 1;
				$field->typeofdata = 'V~O';
				$field->presence = 0;
				$blockInstance->addField($field);
				$field->setRelatedModules(array('Abonado'));
				$abonadoInstance->setRelatedList($cbZoneInstance, 'cbZone', array('ADD'), 'get_dependents_list');
			}

			$contactsInstance = Vtiger_Module::getInstance('Contacts');
			if ($contactsInstance) {
				$blockInstance = VTiger_Block::getInstance('LBL_CONTACT_INFORMATION', $contactsInstance);
				$field = new Vtiger_Field();
				$field->name = 'abonadoid';
				$field->label= 'Abonado';
				$field->table = $contactsInstance->basetable;
				$field->column = 'abonadoid';
				$field->columntype = 'INT(11)';
				$field->uitype = 10;
				$field->displaytype = 1;
				$field->typeofdata = 'V~O';
				$field->presence = 0;
				$blockInstance->addField($field);
				$field->setRelatedModules(array('Abonado'));
				$abonadoInstance->setRelatedList($contactsInstance, 'Contacts', array('ADD'), 'get_dependents_list');
			}
		} elseif ($event_type == 'module.disabled') {
			// TODO Handle actions when this module is disabled.
		} elseif ($event_type == 'module.enabled') {
			// TODO Handle actions when this module is enabled.
		} elseif ($event_type == 'module.preuninstall') {
			// TODO Handle actions when this module is about to be deleted.
		} elseif ($event_type == 'module.preupdate') {
			// TODO Handle actions before this module is updated.
		} elseif ($event_type == 'module.postupdate') {
			// TODO Handle actions after this module is updated.
		}
	}

	/**
	 * Handle saving related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	// public function save_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle deleting related module information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//public function delete_related_module($module, $crmid, $with_module, $with_crmid) { }

	/**
	 * Handle getting related list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//public function get_related_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }

	/**
	 * Handle getting dependents list information.
	 * NOTE: This function has been added to CRMEntity (base class).
	 * You can override the behavior by re-defining it here.
	 */
	//public function get_dependents_list($id, $cur_tab_id, $rel_tab_id, $actions=false) { }
}
?>
