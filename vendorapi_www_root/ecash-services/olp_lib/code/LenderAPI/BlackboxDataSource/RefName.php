<?php
/**
 * Delegate of Reference's First Name or Last Name.
 *
 * @package VendorAPI
 * @author Demin Yin <Demin.Yin@SellingSource.com>
 */
abstract class LenderAPI_BlackboxDataSource_RefName extends LenderAPI_BlackboxDataSource_BaseDelegate
{
	/**
	 *
	 * @return mixed Usually a string value, to be used in XSLT transforms.
	 * @see LenderAPI_BlackboxDataSource_IDelegate::value()
	 */
	public function value()
	{
		return $this->getName();
	}

	/**
	 * You shouldn't set a reference's first name or last name directly.
	 * @param string $value
	 * @return void
	 */
	public function setValue($value)
	{
	}

	/**
	 * Get first name or last name based on given object's type.
	 * 
	 * e.g.:
	 * $obj is a LenderAPI_BlackboxDataSource_Ref01NameLast object => return last name of reference 1;
	 * $obj is a LenderAPI_BlackboxDataSource_Ref03NameFirst object => return first name of reference 3.
	 * 
	 * @return string First name or last name based on given class name. 
	 */
	protected function getName()
	{
		$class_name = get_class($this);
		$index = preg_replace('/[a-z_-]/i', '', $class_name);
		$index = intval($index);

		$field_name = "ref_0{$index}_name_full";
		$name_full = $this->data->$field_name;

		if (stripos($class_name, 'First') !== FALSE)
		{
			return $this->getFirstName($name_full);
		}
		else if (stripos($class_name, 'Last') !== FALSE)
		{
			return $this->getLastName($name_full);
		}
		else
		{
			return NULL;
		}
	}

	/**
	 * Get first name of given full name.
	 * 
	 * @param string $name_full Full name.
	 * @return string First name of given full name.
	 */
	protected function getFirstName($name_full)
	{
		if (!empty($name_full))
		{
			$name_full = trim($name_full);

			if (strpos($name_full, ' ') !== FALSE)
			{
				return substr($name_full, 0, strpos($name_full, ' '));
			}
			else
			{
				return $name_full;
			}
		}

		return NULL;
	}

	/**
	 * Get last name of given full name.
	 * 
	 * @param string $name_full Full name.
	 * @return string Last name of given full name.
	 */
	protected function getLastName($name_full)
	{
		if (!empty($name_full))
		{
			$name_full = trim($name_full);

			if (strpos($name_full, ' ') !== FALSE)
			{
				return substr($name_full, strrpos($name_full, ' ') + 1);
			}
		}

		return NULL;
	}
}
