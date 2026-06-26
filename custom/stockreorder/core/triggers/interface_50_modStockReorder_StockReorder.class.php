<?php

require_once DOL_DOCUMENT_ROOT.'/core/triggers/dolibarrtriggers.class.php';

class InterfaceStockReorder extends DolibarrTriggers
{
	public function __construct($db)
	{
		parent::__construct($db);
		$this->family = "stock";
		$this->description = "StockReorder triggers";
		$this->version = "1.0.0";
		$this->picto = "stock";
	}

	public function runTrigger($action, $object, User $user, Translate $langs, Conf $conf)
	{
		if (empty($conf->stockreorder->enabled)) {
			return 0;
		}

		if ($action === 'STOCK_MOVEMENT') {
			if ($object->qty < 0) {
				$productId = (int) (empty($object->product_id) ? $object->fk_product : $object->product_id);
				require_once DOL_DOCUMENT_ROOT . '/product/class/product.class.php';
				$product = new Product($this->db);
				if ($product->fetch($productId) > 0) {
					$product->load_stock();
					$realStock = $product->stock_reel;
					$desiredStock = $product->desiredstock;

					if ($desiredStock > 0 && $realStock < $desiredStock) {
						$sql = "SELECT rowid, fk_soc, price, unitprice, ref_fourn, tva_tx ";
						$sql .= "FROM " . MAIN_DB_PREFIX . "product_fournisseur_price ";
						$sql .= "WHERE fk_product = " . $productId . " ";
						$sql .= "ORDER BY unitprice ASC, price ASC LIMIT 1";

						$resql = $this->db->query($sql);
						if ($resql) {
							$num = $this->db->num_rows($resql);
							if ($num > 0) {
								$row = $this->db->fetch_object($resql);
								$fk_soc = (int) $row->fk_soc;
								$fk_prod_fourn_price = (int) $row->rowid;
								$ref_fourn = $row->ref_fourn;
								$unitprice = (double) $row->unitprice;
								$tva_tx = (double) $row->tva_tx;

								$sqlCheck = "SELECT COUNT(cf.rowid) as nb ";
								$sqlCheck .= "FROM " . MAIN_DB_PREFIX . "commande_fournisseur as cf ";
								$sqlCheck .= "JOIN " . MAIN_DB_PREFIX . "commande_fournisseurdet as cfd ON cf.rowid = cfd.fk_commande ";
								$sqlCheck .= "WHERE cf.fk_soc = " . $fk_soc . " ";
								$sqlCheck .= "AND cfd.fk_product = " . $productId . " ";
								$sqlCheck .= "AND cf.fk_statut IN (0, 1, 2, 3, 4)";

								$resqlCheck = $this->db->query($sqlCheck);
								if ($resqlCheck) {
									$rowCheck = $this->db->fetch_object($resqlCheck);
									$pendingCount = (int) $rowCheck->nb;
									if ($pendingCount === 0) {
										$n_days = (int) (empty($conf->global->STOCKREORDER_DAYS_N) ? 30 : $conf->global->STOCKREORDER_DAYS_N);
										$date_limit = date('Y-m-d H:i:s', time() - ($n_days * 24 * 60 * 60));

										$sqlSales = "SELECT SUM(fd.qty) as total_qty ";
										$sqlSales .= "FROM " . MAIN_DB_PREFIX . "facture as f ";
										$sqlSales .= "JOIN " . MAIN_DB_PREFIX . "facturedet as fd ON f.rowid = fd.fk_facture ";
										$sqlSales .= "WHERE fd.fk_product = " . $productId . " ";
										$sqlSales .= "AND f.fk_statut IN (1, 2) ";
										$sqlSales .= "AND f.datef >= '" . $this->db->escape($date_limit) . "'";

										$resqlSales = $this->db->query($sqlSales);
										if ($resqlSales) {
											$rowSales = $this->db->fetch_object($resqlSales);
											$total_qty = (double) $rowSales->total_qty;
											if ($total_qty > 0) {
												require_once DOL_DOCUMENT_ROOT . '/fourn/class/fournisseur.commande.class.php';
												$order = new CommandeFournisseur($this->db);
												$order->socid = $fk_soc;
												$order->date_commande = dol_now();
												$order->statut = 0;
												$order->note_public = "Commande automatique de réapprovisionnement (Stock optimal non respecté)";

												$orderId = $order->create($user);
												if ($orderId > 0) {
													$order->addline(
														$product->label,
														$unitprice,
														$total_qty,
														$tva_tx,
														0.0,
														0.0,
														$productId,
														$fk_prod_fourn_price,
														$ref_fourn,
														0.0,
														'HT'
													);
												}
											}
										}
									}
								}
							}
						}
					}
				}
			}
		}

		return 0;
	}
}
