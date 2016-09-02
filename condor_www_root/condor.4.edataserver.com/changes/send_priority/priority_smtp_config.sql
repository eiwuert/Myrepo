CREATE TABLE priority_smtp_config (
	priority_smtp_config INT UNSIGNED AUTO_INCREMENT,
	account_id INT UNSIGNED,
	minimum_priority TINYINT UNSIGNED,
	server VARCHAR(255),
	port SMALLINT UNSIGNED
	PRIMARY KEY (priority_smtp_config)
);