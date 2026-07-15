CREATE TABLE tt_content (
	tx_schema_type varchar(255) DEFAULT '' NOT NULL,
	tx_schema_is_main_entity smallint(5) unsigned DEFAULT '0' NOT NULL,
	tx_schema_properties json
);
