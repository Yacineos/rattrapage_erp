<?php

$res = 0;
if (! $res && file_exists("../../main.inc.php")) $res = @include("../../main.inc.php");
if (! $res && file_exists("../../../main.inc.php")) $res = @include("../../../main.inc.php");
if (! $res && file_exists("../../../../main.inc.php")) $res = @include("../../../../main.inc.php");
if (! $res) die("Échec de l'inclusion du fichier main.inc.php");

require_once DOL_DOCUMENT_ROOT.'/core/lib/admin.lib.php';
require_once DOL_DOCUMENT_ROOT.'/core/lib/functions.lib.php';

if (!$user->admin) {
	accessforbidden();
}

$langs->load("admin");
$langs->load("stockreorder");

$action = GETPOST('action', 'alpha');

if ($action == 'update') {
	$n_days = GETPOST('STOCKREORDER_DAYS_N', 'int');
	if ($n_days <= 0) {
		setEventMessages($langs->trans("ErrorFieldRequired", $langs->trans("STOCKREORDER_DAYS_N")), null, 'errors');
	} else {
		dolibarr_set_const($db, 'STOCKREORDER_DAYS_N', $n_days, 'chaine', 0, '', $conf->entity);
		setEventMessages($langs->trans("RecordSaved"), null, 'mesgs');
	}
}

llxHeader('', $langs->trans("StockReorderSetup"));

$linkback = '<a href="'.DOL_URL_ROOT.'/admin/modules.php?restore_lastsearch_values=1">'.$langs->trans("BackToModuleList").'</a>';
print load_fiche_titre($langs->trans("StockReorderSetup"), $linkback, 'title_setup');

print '<form method="post" action="'.$_SERVER["PHP_SELF"].'">';
print '<input type="hidden" name="token" value="'.newToken().'">';
print '<input type="hidden" name="action" value="update">';

print '<table class="noborder centpercent">';
print '<tr class="liste_titre">';
print '<td>'.$langs->trans("Parameter").'</td>';
print '<td class="center">'.$langs->trans("Value").'</td>';
print '</tr>';

print '<tr class="oddeven">';
print '<td>'.$langs->trans("STOCKREORDER_DAYS_N").' (N) <a href="#" title="'.$langs->trans("STOCKREORDER_DAYS_N_Tooltip").'">'.img_info().'</a></td>';
print '<td class="center">';
print '<input type="number" name="STOCKREORDER_DAYS_N" value="'.(empty($conf->global->STOCKREORDER_DAYS_N) ? 30 : $conf->global->STOCKREORDER_DAYS_N).'" min="1" class="valignmiddle">';
print '</td>';
print '</tr>';

print '</table>';

print '<div class="center">';
print '<input type="submit" class="button button-save" value="'.$langs->trans("Save").'">';
print '</div>';

print '</form>';

llxFooter();
$db->close();
