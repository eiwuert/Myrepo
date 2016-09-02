<?php
/**
 * This class is model (database/queries) for the admin access of the campaign to inquiry rules.
 *
 * @author Randy Klepetko <randy.klepetko@sbcglobal.net>
 */

class ECash_UnderwritingRules
{
	protected $db;

	public function __construct(DB_Database_1 $db){
		$this->db = $db;
	}

	public function Get_Underwriting_Rules()
	{
		$query = "
			SELECT
				`cg`.`campaign_group` AS `campaign_group`,
				`cg`.`campaign_group_id` AS `campaign_group_id`,
				`cg`.`campaign_group_risk` AS `campaign_group_risk`,
				`cp`.`campaign_publisher_name` AS `campaign_publisher_name`,
				`cp`.`campaign_publisher_id` AS `campaign_publisher_id`,
				`cm`.`campaign_name` AS `campaign_name`,
				`cm`.`campaign_id` AS `campaign_id`,
				`cm`.`income_source` AS `income_source`,
				`cm`.`income_frequency` AS `income_frequency`,
				`ui`.`uw_inquiry_name` AS `uw_inquiry_name`,
				`ui`.`uw_inquiry_id` AS `uw_inquiry_id`,
				`up`.`uw_provider_name` AS `uw_provider_name`,
				`up`.`uw_provider_id` AS `uw_provider_id`,
				`ci`.`campaign_inquiry_id` AS `campaign_inquiry_id`,
				`ci`.`count` AS `count`,
				((`ci`.`count` / `cis`.`cnt_sum`) * 100) AS `percentage`
			FROM ((((((`campaigns` `cm`
				join `campaign_publishers` `cp` on((`cm`.`campaign_publisher_id` = `cp`.`campaign_publisher_id`)))
				join `campaign_groups` `cg` on((`cm`.`campaign_group_id` = `cg`.`campaign_group_id`)))
				join `campaign_inquiry` `ci` on((`cm`.`campaign_id` = `ci`.`campaign_id`)))
				join `uw_inquiries` `ui` on((`ci`.`uw_inquiry_id` = `ui`.`uw_inquiry_id`)))
				join `uw_providers` `up` on((`ui`.`uw_provider_id` = `up`.`uw_provider_id`)))
				join `campaign_sum_count` `cis` on((`cm`.`campaign_id` = `cis`.`campaign_id`)))
			ORDER BY `cp`.`campaign_publisher_name`,`cm`.`campaign_name`,`ui`.`uw_inquiry_name`
		";
		$st = $this->db->query($query);
		$rtn = $st->fetchAll(PDO::FETCH_OBJ);
		return $rtn;
	}

	public function Get_Campaign_Groups()
	{
		$query = "
			SELECT
				campaign_group_id,
				campaign_group,
				campaign_group_risk
			FROM campaign_groups
			ORDER BY campaign_group_id
		";
		$st = $this->db->query($query);
		return $st->fetchAll(PDO::FETCH_OBJ);
	}

	public function Get_Campaign_Publishers()
	{
		$query = "
			SELECT
				campaign_publisher_id,
				campaign_publisher_name
			FROM campaign_publishers
			ORDER BY campaign_publisher_id
		";
		$st = $this->db->query($query);
		return $st->fetchAll(PDO::FETCH_OBJ);
	}

	public function Get_Campaigns()
	{
		$query = "
			SELECT
				cm.campaign_id AS campaign_id,
				cm.campaign_name AS campaign_name,
				cg.campaign_group AS campaign_group,
				cm.campaign_publisher_id AS campaign_publisher_id,
				cm.campaign_group_id AS campaign_group_id,
				cg.campaign_group_risk AS campaign_group_risk,
				cp.campaign_publisher_name AS campaign_publisher_name,
				cm.income_source AS income_source,
				cm.income_frequency AS income_frequency,
				IF (`cis`.`cnt_sum` IS NULL,0,`cis`.`cnt_sum`) AS count
			FROM (((`campaigns` `cm`
				join `campaign_publishers` `cp` on((`cm`.`campaign_publisher_id` = `cp`.`campaign_publisher_id`)))
				join `campaign_groups` `cg` on((`cm`.`campaign_group_id` = `cg`.`campaign_group_id`)))
				left join `campaign_sum_count` `cis` on((`cm`.`campaign_id` = `cis`.`campaign_id`)))
			order by cg.campaign_group,`cm`.`campaign_name`
		";
		$st = $this->db->query($query);
		return $st->fetchAll(PDO::FETCH_OBJ);
	}

	public function Get_Inquiries()
	{
		$query = "
			SELECT
				`ui`.`uw_inquiry_id` AS `uw_inquiry_id`,
				`ui`.`uw_inquiry_name` AS `uw_inquiry_name`,
				`ui`.`uw_provider_id` AS `uw_provider_id`,
				`up`.`uw_provider_name` AS `uw_provider_name`,
				IF (`us`.`uw_store_id` IS NULL,-1,`us`.`uw_store_id`) AS `uw_store_id`,
				`us`.`store_id` AS `store_id`,
				`us`.`uw_store_id` AS `uw_store_id`,
				`us`.`username` AS `username`
			FROM `uw_inquiries` `ui`
				join `uw_providers` `up` on (`ui`.`uw_provider_id` = `up`.`uw_provider_id`)
				left join `uw_store` `us` on (`ui`.`uw_store_id` = `us`.`uw_store_id`)
			ORDER BY `ui`.`uw_inquiry_name`
		";
		$st = $this->db->query($query);
		return $st->fetchAll(PDO::FETCH_OBJ);
	}

	public function Get_Providers()
	{
		$query = "
			SELECT
				`up`.`uw_provider_id` AS `uw_provider_id`,
				`up`.`uw_provider_name` AS `uw_provider_name`,
				`up`.`uw_name_short` AS `name_short`
			FROM `uw_providers` `up` 
			ORDER BY `up`.`uw_provider_name`;
		";
		$st = $this->db->query($query);
		return $st->fetchAll(PDO::FETCH_OBJ);
	}

	public function Get_Stores()
	{
		$query = "
			SELECT
				`us`.`uw_store_id` AS `uw_store_id`,
				`us`.`store_id` AS `store_id`,
				`us`.`group_id` AS `store_group_id`,
				`us`.`merchant` AS `merchant`,
				`us`.`username` AS `username`,
				`us`.`uw_provider_id` AS `uw_provider_id`,
				`up`.`uw_provider_name` AS `uw_provider_name`
			FROM `uw_store` `us`
				left join `uw_providers` `up` on(`us`.`uw_provider_id` = `up`.`uw_provider_id`)
			ORDER BY `us`.`store_id`;
		";
		$st = $this->db->query($query);
		$rtn = $st->fetchAll(PDO::FETCH_OBJ);
		
		return $rtn;
	}

	public function Add_Campaign_Group($group_name, $risk_value)
	{
		$query = "
			INSERT INTO campaign_groups
			(created_date, campaign_group, campaign_group_risk)
			VALUES (NOW(),?,?)
		";
		$this->db->queryPrepared($query, array($group_name, $risk_value));

		return $this->db->lastInsertId();
	}

	public function Add_Campaign_Publisher($publisher_name)
	{
		$query = "
			INSERT INTO campaign_publishers
			(created_date, campaign_publisher_name)
			VALUES (NOW(),?)
		";
		$this->db->queryPrepared($query, array($publisher_name));

		return $this->db->lastInsertId();
	}

	public function Add_Campaign($name, $publisher_id, $group_id, $source, $frequency)
	{
		$query = "
			INSERT INTO campaigns
			(created_date, campaign_name, campaign_publisher_id, campaign_group_id, income_source, income_frequency)
			VALUES (NOW(),?,?,?,?,?)
		";
		$this->db->queryPrepared($query, array($name, $publisher_id, $group_id, $source, $frequency));

		return $this->db->lastInsertId();
	}

	public function Add_Inquiry($name, $provider_id, $store_id)
	{
		$query = "
			INSERT INTO uw_inquiries
			(created_date, uw_inquiry_name, uw_provider_id, uw_store_id)
			VALUES (NOW(),?,?,?)
		";
		$this->db->queryPrepared($query, array($name, $provider_id, $store_id));

		return $this->db->lastInsertId();
	}

	public function Add_Provider($name, $short)
	{
		$query = "
			INSERT INTO uw_providers
			(created_date, uw_provider_name, uw_name_short)
			VALUES (NOW(),?,?)
		";
		$this->db->queryPrepared($query, array($name, $short));

		return $this->db->lastInsertId();
	}

	public function Add_Store($store_id, $provider_id, $group_id, $merchant, $username, $password)
	{
		$query = "
			INSERT INTO uw_store
			(created_date, uw_provider_id, group_id, store_id, merchant, username, password)
			VALUES (NOW(),?,?,?,?,?,?)
		";
		$this->db->queryPrepared($query, array($provider_id, $group_id, $store_id, $merchant, $username, $password));

		return $this->db->lastInsertId();
	}

	public function Update_Campaign_Group($group_id, $group_name, $risk_value)
	{
		$query = "
			UPDATE campaign_groups
			SET
				campaign_group = ?,
				campaign_group_risk = ?
			WHERE campaign_group_id = ?
		";

		$args = array(
			$group_name,
			$risk_value,
			$group_id
		);

		$this->db->queryPrepared($query, $args);
		return TRUE;
	}

	public function Update_Campaign_Publisher($publisher_id, $publisher_name)
	{
		$query = "
			UPDATE campaign_publishers
			SET
				campaign_publisher_name = ?
			WHERE campaign_publisher_id = ?
		";

		$args = array(
			$publisher_name,
			$publisher_id
		);

		$this->db->queryPrepared($query, $args);
		return TRUE;
	}

	public function Update_Campaign($campaign_id, $name, $publisher_id, $group_id, $source, $frequency)
	{
		$query = "
			UPDATE campaigns
			SET
				campaign_name = ?,
				campaign_publisher_id = ?,
				campaign_group_id = ?,
				income_source = ?,
				income_frequency = ?
			WHERE campaign_id = ?
		";

		$args = array(
			$name,
			$publisher_id,
			$group_id,
			$source,
			$frequency,
			$campaign_id
		);

		$this->db->queryPrepared($query, $args);
		return TRUE;
	}

	public function Update_Inquiry($inquiry_id, $name, $provider_id, $store_id)
	{
		$query = "
			UPDATE uw_inquiries
			SET
				uw_inquiry_name = ?,
				uw_provider_id = ?,
				uw_store_id = ?
			WHERE uw_inquiry_id = ?
		";

		$args = array(
			$name,
			$provider_id,
			$store_id,
			$inquiry_id
		);

		$this->db->queryPrepared($query, $args);
		return TRUE;
	}

	public function Update_Provider($provider_id, $name, $short)
	{
		$query = "
			UPDATE uw_providers
			SET
				uw_provider_name = ?,
				uw_name_short = ?
			WHERE uw_provider_id = ?
		";

		$args = array(
			$name,
			$short,
			$provider_id
		);

		$this->db->queryPrepared($query, $args);
		return TRUE;
	}

	public function Update_Store($ui_store_id, $store_id, $provider_id, $group_id, $merchant, $username, $password)
	{
		$query = "
			UPDATE uw_store
			SET
				uw_provider_id = ?,
				group_id = ?,
				store_id = ?,
				merchant = ?,
				username = ?,
				password = ?
			WHERE uw_store_id = ?
		";

		$args = array(
			$provider_id,
			$group_id,
			$store_id,
			$merchant,
			$username,
			$password,
			$ui_store_id
		);

		$this->db->queryPrepared($query, $args);
		return TRUE;
	}

	public function Set_Campaign_Inquiry($campaign_id, $inquiry_id, $count)
	{
		$query = "
			SELECT
				`ci`.`campaign_inquiry_id` AS `campaign_inquiry_id`,
				`ci`.`count` AS `count`
			FROM `campaign_inquiry` `ci`
			WHERE campaign_id = ? AND uw_inquiry_id = ?
		";
		
		$args = array($campaign_id, $inquiry_id);
		$st = $this->db->queryPrepared($query, $args);
		$rows = $st->fetchAll(PDO::FETCH_OBJ);
		
		if (($rows) && ($rows[0]->campaign_inquiry_id > 0)) {
			$query = "
				UPDATE campaign_inquiry
				SET
					count = ?
				WHERE campaign_id = ? AND uw_inquiry_id = ?
			";

			$args = array($count, $campaign_id, $inquiry_id	);
	
			$this->db->queryPrepared($query, $args);
			$rtn = $rows[0]->campaign_inquiry_id;
		} else {
			$query = "
				INSERT INTO campaign_inquiry
				(created_date, campaign_id, uw_inquiry_id, count)
				VALUES (NOW(),?,?,?)
			";

			$this->db->queryPrepared($query, array($campaign_id, $inquiry_id, $count));

			$rtn = $this->db->lastInsertId();
		}

		return $rtn;
	}

	public function Delete_Campaign_Group($group_id)
	{
		$query = "
			SELECT campaign_group_id FROM campaigns
			WHERE campaign_group_id = ?
		";

		$args = array(
			$group_id
		);

		$st = $this->db->queryPrepared($query, $args);
		$rows = $st->fetchAll(PDO::FETCH_OBJ);
		if (($rows) && ($rows[0]->campaign_group_id >= 0)) return null;
		
		$query = "
			DELETE FROM campaign_groups
			WHERE campaign_group_id = ?
		";

		$args = array(
			$group_id
		);

		$this->db->queryPrepared($query, $args);
		return TRUE;
	}

	public function Delete_Campaign_Publisher($publisher_id)
	{
		$query = "
			SELECT campaign_publisher_id FROM campaigns
			WHERE campaign_publisher_id = ?
		";

		$args = array(
			$group_id
		);

		$st = $this->db->queryPrepared($query, $args);
		$rows = $st->fetchAll(PDO::FETCH_OBJ);
		if (($rows) && ($rows[0]->campaign_publisher_id >= 0)) return null;

		$query = "
			DELETE FROM campaign_publishers
			WHERE campaign_publisher_id = ?
		";

		$args = array(
			$publisher_id
		);

		$this->db->queryPrepared($query, $args);
		return TRUE;
	}

	public function Delete_Campaign($campaign_id)
	{
		$query = "
			SELECT campaign_id FROM campaign_inquiry
			WHERE campaign_id = ?
		";

		$args = array(
			$campaign_id
		);

		$st = $this->db->queryPrepared($query, $args);
		$rows = $st->fetchAll(PDO::FETCH_OBJ);
		if (($rows) && ($rows[0]->campaign_id >= 0)) return null;

		$query = "
			DELETE FROM campaigns
			WHERE campaign_id = ?
		";

		$args = array(
			$campaign_id
		);

		$this->db->queryPrepared($query, $args);
		return TRUE;
	}

	public function Delete_Inquiry($inquiry_id)
	{
		$query = "
			SELECT uw_inquiry_id FROM campaign_inquiry
			WHERE uw_inquiry_id = ?
		";

		$args = array(
			$inquiry_id
		);

		$st = $this->db->queryPrepared($query, $args);
		$rows = $st->fetchAll(PDO::FETCH_OBJ);
		if (($rows) && ($rows[0]->inquiry_id >= 0)) return null;

		$query = "
			DELETE FROM uw_inquiries
			WHERE uw_inquiry_id = ?
		";

		$args = array(
			$inquiry_id
		);

		$this->db->queryPrepared($query, $args);
		return TRUE;
	}

	public function Delete_Provider($provider_id)
	{
		$query = "
			SELECT uw_provider_id FROM uw_inquiries
			WHERE uw_provider_id = ?
		";

		$args = array(
			$provider_id
		);

		$st = $this->db->queryPrepared($query, $args);
		$rows = $st->fetchAll(PDO::FETCH_OBJ);
		if (($rows) && ($rows[0]->provider_id >= 0)) return null;

		$query = "
			SELECT uw_provider_id FROM uw_store
			WHERE uw_provider_id = ?
		";

		$args = array(
			$provider_id
		);

		$st = $this->db->queryPrepared($query, $args);
		$rows = $st->fetchAll(PDO::FETCH_OBJ);
		if (($rows) && ($rows[0]->provider_id >= 0)) return null;

		$query = "
			DELETE FROM  uw_providers
			WHERE uw_provider_id = ?
		";

		$args = array(
			$provider_id
		);

		$this->db->queryPrepared($query, $args);
		return TRUE;
	}

	public function Delete_Store($ui_store_id)
	{
		$query = "
			SELECT uw_store_id FROM uw_inquiries
			WHERE uw_store_id = ?
		";

		$args = array(
			$ui_store_id
		);

		$st = $this->db->queryPrepared($query, $args);
		$rows = $st->fetchAll(PDO::FETCH_OBJ);
		if (($rows) && ($rows[0]->uw_store_id >= 0)) return null;

		$query = "
			DELETE FROM uw_store
			WHERE uw_store_id = ?
		";

		$args = array(
			$ui_store_id
		);

		$this->db->queryPrepared($query, $args);
		return TRUE;
	}

	public function Delete_Campaign_Inquiry($campaign_inquiry_id)
	{
		$query = "
			DELETE FROM campaign_inquiry
			WHERE campaign_inquiry_id = ?
		";
		
		$args = array($campaign_inquiry_id);
		
		$this->db->queryPrepared($query, $args);
		return TRUE;
	}

}

?>
