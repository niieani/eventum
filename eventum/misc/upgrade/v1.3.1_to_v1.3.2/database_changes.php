<?php
include_once("../../../config.inc.php");
include_once(APP_INC_PATH . "db_access.php");


$stmt = "desc eventum_project_priority";
$stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
$columns = $GLOBALS["db_api"]->dbh->getAll($stmt, DB_FETCHMODE_ASSOC);
if (PEAR::isError($columns)) {
    echo "<pre>";var_dump($columns);echo "</pre>";
    exit;
}
$stmts = array();
// need to handle problems where the auto_increment key was not added to pri_id
if (!strstr($columns[0]['Extra'], 'auto_increment')) {
    $stmts[] = "ALTER TABLE eventum_project_priority CHANGE COLUMN pri_id pri_id tinyint(1) unsigned NOT NULL auto_increment";
}
if (!strstr($columns[0]['Key'], 'PRI')) {
    $stmts[] = "ALTER TABLE eventum_project_priority DROP PRIMARY KEY";
    $stmts[] = "ALTER TABLE eventum_project_priority ADD PRIMARY KEY(pri_id)";
}

$stmt = "desc eventum_customer_note";
$stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
$columns = $GLOBALS["db_api"]->dbh->getCol($stmt);
if (PEAR::isError($columns)) {
    echo "<pre>";var_dump($columns);echo "</pre>";
    exit;
}
// need to handle the problem in which upgrades from 1.2.2 to 1.3 didn't get the cno_prj_id field
if (!in_array('cno_prj_id', $columns)) {
    $stmts[] = "ALTER TABLE eventum_customer_note ADD COLUMN cno_prj_id int(11) unsigned NOT NULL";
}

$stmts[] = 'ALTER TABLE eventum_issue DROP COLUMN iss_lock_usr_id';

$stmts[] = 'CREATE TABLE eventum_link_filter (
  lfi_id int(11) unsigned NOT NULL auto_increment,
  lfi_pattern varchar(255) NOT NULL,
  lfi_replacement varchar(255) NOT NULL,
  lfi_usr_role tinyint(9) NOT NULL DEFAULT 0,
  lfi_description varchar(255) NULL,
  PRIMARY KEY  (lfi_id)
)';
$stmts[] = 'CREATE TABLE eventum_project_link_filter (
  plf_prj_id int(11) NOT NULL,
  plf_lfi_id int(11) NOT NULL,
  PRIMARY KEY  (plf_prj_id, plf_lfi_id)
)';

$stmts[] = 'ALTER TABLE eventum_reminder_field ADD column rmf_allow_column_compare tinyint(1) DEFAULT 0';
$stmts[] = "UPDATE eventum_reminder_field SET rmf_allow_column_compare = 1 WHERE rmf_title LIKE '%date%'";
$stmts[] = 'ALTER TABLE eventum_reminder_level_condition ADD COLUMN rlc_comparison_rmf_id tinyint(3) unsigned';

// add a project ID to irc notifications table so notifications aren't tied to issues.
$stmts[] = 'ALTER TABLE eventum_irc_notice ADD COLUMN ino_prj_id int(11) NOT NULL';

$stmts[] = 'ALTER TABLE eventum_custom_field ADD COLUMN fld_list_display tinyint(1) NOT NULL DEFAULT 0';

foreach ($stmts as $stmt) {
    $stmt = str_replace('eventum_', APP_TABLE_PREFIX, $stmt);
    $res = $GLOBALS["db_api"]->dbh->query($stmt);
    if (PEAR::isError($res)) {
        echo "<pre>";var_dump($res);echo "</pre>";
        exit;
    }
}

?>
done